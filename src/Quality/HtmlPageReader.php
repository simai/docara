<?php

declare(strict_types=1);

namespace Simai\Docara\Quality;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;

/**
 * Reads the facts the quality report needs from one built HTML page: the
 * readable content (the article, or the main region of a landing page)
 * without live examples, code, scripts and controls; its headings, links,
 * images, tables and prose; the lang of the page; the ids a fragment link can
 * point to; and the links of the site menus.
 */
final class HtmlPageReader
{
    /** Elements whose text is code, not prose. */
    private const CODE_TAGS = ['pre', 'code', 'kbd', 'samp', 'var'];

    /** Elements that are not readable content. */
    private const REMOVED = '//script | //style | //template | //svg | //button | //nav | //*[@data-docara-example] | //*[@aria-hidden="true"]';

    /**
     * @return array{
     *   lang:?string,
     *   noindex:bool,
     *   headings:list<array{level:int,text:string}>,
     *   links:list<array{href:string,text:string}>,
     *   images:list<array{src:string,alt:?string}>,
     *   tables:list<bool>,
     *   blocks:list<string>,
     *   text:string,
     *   terms:list<string>,
     *   ids:array<string,true>,
     *   navigation:list<string>
     * }
     */
    public function read(string $html): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($document);

        $html = $xpath->query('//html')?->item(0);
        $lang = $html instanceof DOMElement && $html->hasAttribute('lang') ? trim($html->getAttribute('lang')) : null;
        $robots = $xpath->query('//head/meta[@name="robots"]')?->item(0);
        $noindex = $robots instanceof DOMElement && str_contains(strtolower($robots->getAttribute('content')), 'noindex');

        $ids = [];
        foreach ($xpath->query('//*[@id]') ?: [] as $node) {
            if ($node instanceof DOMElement && $node->getAttribute('id') !== '') {
                $ids[$node->getAttribute('id')] = true;
            }
        }
        $navigation = [];
        foreach ($xpath->query('//nav[@data-docara-smart="docara.navigation"]//a[@href]') ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $navigation[] = $node->getAttribute('href');
            }
        }

        $content = $this->content($xpath);
        $facts = [
            'lang' => $lang === '' ? null : $lang,
            'noindex' => $noindex,
            'headings' => [], 'links' => [], 'images' => [], 'tables' => [], 'blocks' => [],
            'text' => '', 'terms' => [], 'ids' => $ids, 'navigation' => array_values(array_unique($navigation)),
        ];
        if ($content === null) {
            return $facts;
        }
        foreach (iterator_to_array($xpath->query('.' . $this->relative(self::REMOVED), $content) ?: []) as $node) {
            $node->parentNode?->removeChild($node);
        }

        foreach ($xpath->query('.//h1 | .//h2 | .//h3 | .//h4 | .//h5 | .//h6', $content) ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $facts['headings'][] = ['level' => (int) substr($node->nodeName, 1), 'text' => $this->squash($node->textContent)];
            }
        }
        foreach ($xpath->query('.//a[@href]', $content) ?: [] as $node) {
            if ($node instanceof DOMElement && ! $this->insideCode($node)) {
                $text = $this->squash($node->textContent);
                if ($text === '') {
                    $text = $this->squash($node->getAttribute('aria-label'));
                }
                if ($text === '') {
                    foreach ($xpath->query('.//img[@alt]', $node) ?: [] as $image) {
                        $text = $image instanceof DOMElement ? $this->squash($image->getAttribute('alt')) : '';
                    }
                }
                $facts['links'][] = ['href' => $node->getAttribute('href'), 'text' => $text];
            }
        }
        foreach ($xpath->query('.//img', $content) ?: [] as $node) {
            if (! $node instanceof DOMElement || in_array($node->getAttribute('role'), ['presentation', 'none'], true)) {
                continue;
            }
            $facts['images'][] = ['src' => $node->getAttribute('src'), 'alt' => $node->hasAttribute('alt') ? $node->getAttribute('alt') : null];
        }
        foreach ($xpath->query('.//table', $content) ?: [] as $node) {
            $facts['tables'][] = ($xpath->query('.//th', $node)?->length ?? 0) > 0;
        }
        foreach ($xpath->query('.//p | .//li | .//dd | .//blockquote', $content) ?: [] as $node) {
            if ($node instanceof DOMElement && ! $this->insideCode($node)) {
                $block = $this->squash($this->blockText($node));
                if ($block !== '') {
                    $facts['blocks'][] = $block;
                }
            }
        }
        foreach ($xpath->query('.//strong | .//b | .//dt | .//dfn | .//th | .//tr/td[1]', $content) ?: [] as $node) {
            $term = $this->squash($node->textContent);
            if ($term !== '') {
                $facts['terms'][] = $term;
            }
        }
        $facts['text'] = $this->squash($this->proseText($content));

        return $facts;
    }

    private function content(DOMXPath $xpath): ?DOMElement
    {
        foreach ([
            '//article[contains(concat(" ", normalize-space(@class), " "), " docara-prose ")]',
            '//main[@id="docara-main"]',
            '//main',
            '//body',
        ] as $query) {
            $node = $xpath->query($query)?->item(0);
            if ($node instanceof DOMElement) {
                return $node;
            }
        }

        return null;
    }

    private function relative(string $query): string
    {
        return implode(' | .', array_map('trim', explode('|', $query)));
    }

    private function insideCode(DOMNode $node): bool
    {
        for ($parent = $node->parentNode; $parent !== null; $parent = $parent->parentNode) {
            if (in_array(strtolower($parent->nodeName), self::CODE_TAGS, true)) {
                return true;
            }
        }

        return false;
    }

    /** Text of a block without nested blocks; inline code becomes one token. */
    private function blockText(DOMNode $node): string
    {
        $text = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMText) {
                $text .= $child->textContent;

                continue;
            }
            $name = strtolower($child->nodeName);
            if (in_array($name, ['p', 'ul', 'ol', 'dl', 'li', 'table', 'blockquote', 'pre', 'div', 'section'], true)) {
                $text .= ' ';

                continue;
            }
            $text .= in_array($name, self::CODE_TAGS, true) ? ' ' . TextStatistics::CODE_TOKEN . ' ' : $this->blockText($child);
        }

        return $text;
    }

    /** All readable text outside code, one space between blocks. */
    private function proseText(DOMNode $node): string
    {
        if ($node instanceof DOMText) {
            return $node->textContent;
        }
        $name = strtolower($node->nodeName);
        if (in_array($name, self::CODE_TAGS, true)) {
            return ' ';
        }
        $text = '';
        foreach ($node->childNodes as $child) {
            $text .= $this->proseText($child);
        }

        return in_array($name, ['p', 'li', 'td', 'th', 'dd', 'dt', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'section', 'br'], true) ? ' ' . $text . ' ' : $text;
    }

    private function squash(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
