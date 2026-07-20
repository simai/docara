<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Document;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Parser\MarkdownParser;
use Simai\Docara\Framework\ComponentDirective;
use Simai\Docara\Framework\ComponentDirectiveParser;
use Simai\Docara\Portable\CanonicalJson;

final class DocumentParser
{
    private ComponentDirectiveParser $directives;

    private MarkdownParser $markdown;

    public function __construct()
    {
        $this->directives = new ComponentDirectiveParser(['ui.alert']);
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 100,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $this->markdown = new MarkdownParser($environment);
    }

    public function parse(string $markdown, string $source): DocumentAst
    {
        if ($source === '' || preg_match('//u', $markdown) !== 1 || trim($markdown) === '') {
            throw new \InvalidArgumentException('DOCUMENT_INPUT_INVALID');
        }

        $parsed = $this->directives->parse($markdown, $source);
        $byPlaceholder = [];
        foreach ($parsed->directives as $directive) {
            $byPlaceholder[$directive->placeholder] = $directive;
        }

        $parts = preg_split(
            '/(DOCARA_COMPONENT_[A-Z0-9_]+)/D',
            $parsed->markdownWithPlaceholders,
            -1,
            PREG_SPLIT_DELIM_CAPTURE,
        );
        if (! is_array($parts)) {
            throw new \InvalidArgumentException('DOCUMENT_INPUT_INVALID');
        }

        $nodes = [];
        $line = 1;
        $ordinal = 0;
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $ordinal++;
            if (isset($byPlaceholder[$part])) {
                $directive = $byPlaceholder[$part];
                $nodes[] = $this->smartNode($directive, $source, $ordinal);
                $line += substr_count($part, "\n");

                continue;
            }

            $endLine = $line + substr_count($part, "\n");
            if (trim($part) !== '') {
                $nodes[] = new MarkdownNode(
                    $this->nodeId($source, 'markdown', $ordinal, $part),
                    $part,
                    new SourceSpan($source, $line, max($line, $endLine)),
                );
            }
            $line = $endLine;
        }
        if ($nodes === []) {
            throw new \InvalidArgumentException('DOCUMENT_AST_EMPTY');
        }

        [$headings, $links] = $this->metadata($parsed->markdownWithPlaceholders, $source);

        return new DocumentAst($source, $nodes, $headings, $links);
    }

    private function smartNode(ComponentDirective $directive, string $source, int $ordinal): SmartCallNode
    {
        $props = $directive->props;
        $view = $props['view'] ?? 'default';
        unset($props['view']);
        if (! is_string($view) || preg_match('/^[a-z][a-z0-9_-]*$/D', $view) !== 1) {
            throw new \InvalidArgumentException('DOCUMENT_SMART_VIEW_INVALID');
        }

        return new SmartCallNode(
            $this->nodeId(
                $source,
                'smart',
                $ordinal,
                CanonicalJson::encode([$directive->component, $view, $props]),
            ),
            $directive->component,
            $view,
            $props,
            $directive->ordinal,
            new SourceSpan($source, $directive->line, $directive->line),
        );
    }

    /** @return array{0: list<DocumentHeading>, 1: list<DocumentLink>} */
    private function metadata(string $markdown, string $source): array
    {
        $document = $this->markdown->parse($markdown);
        $headings = [];
        $links = [];
        $headingIds = [];
        $walker = $document->walker();
        while (($event = $walker->next()) !== null) {
            if (! $event->isEntering()) {
                continue;
            }
            $node = $event->getNode();
            if ($node instanceof Heading) {
                $text = $this->text($node);
                $id = $this->uniqueHeadingId($this->slug($text), $headingIds);
                $headingIds[$id] = true;
                $start = $node->getStartLine() ?? 1;
                $end = $node->getEndLine() ?? $start;
                $headings[] = new DocumentHeading(
                    $id,
                    $node->getLevel(),
                    $text,
                    new SourceSpan($source, $start, $end),
                );
            }
            if ($node instanceof Link) {
                [$start, $end] = $this->lineSpan($node);
                $links[] = new DocumentLink(
                    $node->getUrl(),
                    $this->text($node),
                    new SourceSpan($source, $start, $end),
                );
            }
        }

        return [$headings, $links];
    }

    private function text(object $root): string
    {
        if (! method_exists($root, 'walker')) {
            return '';
        }
        $text = '';
        $walker = $root->walker();
        while (($event = $walker->next()) !== null) {
            if (! $event->isEntering()) {
                continue;
            }
            $node = $event->getNode();
            if ($node instanceof Text || $node instanceof Code) {
                $text .= $node->getLiteral();
            }
        }

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /** @return array{0: int, 1: int} */
    private function lineSpan(object $node): array
    {
        $current = $node;
        while (true) {
            if (method_exists($current, 'getStartLine') && method_exists($current, 'getEndLine')) {
                $start = $current->getStartLine();
                $end = $current->getEndLine();
                if (is_int($start)) {
                    return [$start, is_int($end) ? $end : $start];
                }
            }
            if (! method_exists($current, 'parent')) {
                break;
            }
            $parent = $current->parent();
            if (! is_object($parent)) {
                break;
            }
            $current = $parent;
        }

        return [1, 1];
    }

    private function slug(string $text): string
    {
        $slug = mb_strtolower($text, 'UTF-8');
        $slug = preg_replace('/[^\p{L}\p{N}\p{M}]+/u', '-', $slug);
        $slug = is_string($slug) ? trim($slug, '-') : '';

        return $slug === '' ? 'section' : rtrim(mb_substr($slug, 0, 120, 'UTF-8'), '-');
    }

    /** @param array<string, true> $used */
    private function uniqueHeadingId(string $base, array $used): string
    {
        if (! isset($used[$base])) {
            return $base;
        }
        for ($counter = 1; ; $counter++) {
            $candidate = $base . '-' . $counter;
            if (! isset($used[$candidate])) {
                return $candidate;
            }
        }
    }

    private function nodeId(string $source, string $type, int $ordinal, string $payload): string
    {
        return $type . '-' . substr(hash('sha256', $source . "\0" . $ordinal . "\0" . $payload), 0, 20);
    }
}
