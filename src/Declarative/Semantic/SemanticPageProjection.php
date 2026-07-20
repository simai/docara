<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Semantic;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class SemanticPageProjection
{
    /**
     * @param  list<string>  $regions
     * @param  list<array{id: string, level: int, text: string}>  $headings
     * @param  list<array{destination: string, label: string}>  $links
     * @param  list<array<string, mixed>>  $smart
     */
    public function __construct(
        public string $title,
        public string $text,
        public array $regions,
        public array $headings,
        public array $links,
        public array $smart,
    ) {}

    /**
     * @param  list<string>  $regions
     * @param  list<array<string, mixed>>  $smartCalls
     */
    public static function fromHtml(
        string $title,
        string $html,
        array $regions,
        array $smartCalls,
    ): self {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><!doctype html><html><body><div id="semantic-root">'
            . $html . '</div></body></html>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($loaded !== true) {
            throw new PortableConfigurationException(
                'DECLARATIVE_SEMANTIC_HTML_INVALID',
                'Rendered page could not be parsed for semantic comparison.',
            );
        }
        $root = $document->getElementById('semantic-root');
        if (! $root instanceof DOMElement) {
            throw new PortableConfigurationException(
                'DECLARATIVE_SEMANTIC_HTML_INVALID',
                'Rendered page has no semantic root.',
            );
        }
        $xpath = new DOMXPath($document);
        $headings = [];
        foreach ($xpath->query(
            '//*[@id="semantic-root"]//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]',
        ) ?: [] as $heading) {
            if (! $heading instanceof DOMElement) {
                continue;
            }
            $headings[] = [
                'id' => $heading->getAttribute('id'),
                'level' => (int) substr(strtolower($heading->tagName), 1),
                'text' => self::normalizeText($heading->textContent),
            ];
        }
        $links = [];
        foreach ($xpath->query('//*[@id="semantic-root"]//a[@href]') ?: [] as $link) {
            if (! $link instanceof DOMElement) {
                continue;
            }
            $links[] = [
                'destination' => $link->getAttribute('href'),
                'label' => self::normalizeText($link->textContent),
            ];
        }
        $smart = [];
        foreach ($smartCalls as $call) {
            $props = $call['props'] ?? null;
            if (! is_array($props)) {
                continue;
            }
            $smart[] = [
                'smart' => (string) ($call['id'] ?? $call['smart'] ?? ''),
                'view' => (string) ($call['view'] ?? 'default'),
                'props' => $props,
            ];
        }

        return new self(
            $title,
            self::normalizeText(self::textWithoutSmart($root)),
            $regions,
            $headings,
            $links,
            $smart,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'text' => $this->text,
            'regions' => $this->regions,
            'headings' => $this->headings,
            'links' => $this->links,
            'smart' => $this->smart,
        ];
    }

    public function canonicalHash(): string
    {
        return hash('sha256', CanonicalJson::encode($this->toArray()));
    }

    private static function textWithoutSmart(DOMElement $root): string
    {
        $clone = $root->cloneNode(true);
        if (! $clone instanceof DOMElement) {
            return '';
        }
        $document = new DOMDocument('1.0', 'UTF-8');
        $imported = $document->importNode($clone, true);
        $document->appendChild($imported);
        $xpath = new DOMXPath($document);
        $remove = [];
        foreach ($xpath->query('//*[starts-with(local-name(), "sf-")]') ?: [] as $node) {
            if ($node instanceof DOMNode) {
                $remove[] = $node;
            }
        }
        foreach ($remove as $node) {
            $node->parentNode?->removeChild($node);
        }

        return $document->textContent;
    }

    private static function normalizeText(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
