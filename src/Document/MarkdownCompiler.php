<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

use Simai\Docara\Markdown\AuthoringAttributeParser;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class MarkdownCompiler
{
    public function __construct(
        private ComponentAliasRegistry $aliases = new ComponentAliasRegistry,
        private AuthoringAttributeParser $attributes = new AuthoringAttributeParser,
    ) {}

    public function compile(string $markdown, string $source): DocumentIr
    {
        $lines = preg_split('/\r\n|\n|\r/u', $markdown);
        if (! is_array($lines) || $source === '' || trim($markdown) === '') {
            throw new PortableConfigurationException('DOCUMENT_IR_INPUT_INVALID', "Cannot compile [$source].");
        }

        $nodes = [];
        for ($index = 0, $count = count($lines); $index < $count;) {
            $line = $lines[$index];
            if (trim($line) === '') {
                $index++;

                continue;
            }
            if (preg_match('/^:::(?:example)(?:\s|\{|$)/', $line) === 1) {
                [$node, $index] = $this->example($lines, $index, $source);
                $nodes[] = $node;

                continue;
            }
            if (preg_match('/^:::(?<alias>[a-z][a-z0-9_-]*)(?:\s+\{(?<attributes>[^}]*)\})?\s*$/', $line) === 1) {
                [$node, $index] = $this->componentBlock($lines, $index, $source);
                $nodes[] = $node;

                continue;
            }
            if (preg_match('/^(#{1,6})\s+(.+)$/u', $line, $heading) === 1) {
                $nodes[] = new SourceNode(
                    'heading',
                    $line,
                    new SourceLocation($source, $index + 1, 1, $index + 1),
                    ['level' => strlen($heading[1]), 'text' => trim($heading[2])],
                );
                $index++;

                continue;
            }
            if (str_starts_with($line, '```') || str_starts_with($line, '~~~')) {
                [$node, $index] = $this->codeBlock($lines, $index, $source);
                $nodes[] = $node;

                continue;
            }
            if (str_starts_with(trim($line), '|') && isset($lines[$index + 1])
                && preg_match('/^\s*\|?\s*:?-{3,}/', $lines[$index + 1]) === 1
            ) {
                $start = $index;
                do {
                    $index++;
                } while ($index < $count && str_starts_with(trim($lines[$index]), '|'));
                $raw = implode("\n", array_slice($lines, $start, $index - $start));
                $nodes[] = new SourceNode(
                    'table',
                    $raw,
                    new SourceLocation($source, $start + 1, 1, $index),
                    ['rows' => max(0, $index - $start - 2)],
                );

                continue;
            }

            $start = $index;
            do {
                $index++;
            } while ($index < $count
                && trim($lines[$index]) !== ''
                && preg_match('/^(?:#{1,6}\s|```|~~~|:::[a-z])/', $lines[$index]) !== 1
                && ! str_starts_with(trim($lines[$index]), '|')
            );
            $raw = implode("\n", array_slice($lines, $start, $index - $start));
            $nodes[] = new SourceNode(
                'paragraph',
                $raw,
                new SourceLocation($source, $start + 1, 1, $index),
                ['text' => trim(preg_replace('/\s+/u', ' ', $raw) ?? $raw)],
            );
        }

        return new DocumentIr($source, $nodes);
    }

    /** @param list<string> $lines @return array{0:ComponentBlockNode,1:int} */
    private function componentBlock(array $lines, int $start, string $source): array
    {
        preg_match(
            '/^:::(?<alias>[a-z][a-z0-9_-]*)(?:\s+\{(?<attributes>[^}]*)\})?\s*$/',
            $lines[$start],
            $opening,
        );
        $alias = (string) ($opening['alias'] ?? '');
        $location = new SourceLocation($source, $start + 1, 1, $start + 1);
        $component = $this->aliases->resolve($alias, $location);
        $end = $start + 1;
        while (isset($lines[$end]) && trim($lines[$end]) !== ':::') {
            if (preg_match('/^:::[a-z]/', $lines[$end]) === 1) {
                throw new PortableConfigurationException(
                    'DOCUMENT_COMPONENT_BLOCK_NESTED_FORBIDDEN',
                    "Nested component block at [$source:" . ($end + 1) . ':1].',
                );
            }
            $end++;
        }
        if (! isset($lines[$end])) {
            throw new PortableConfigurationException(
                'DOCUMENT_COMPONENT_BLOCK_UNCLOSED',
                "Unclosed component [$alias] at [{$location->label()}].",
            );
        }
        $body = implode("\n", array_slice($lines, $start + 1, $end - $start - 1));
        $children = $this->componentBlockChildren($lines, $start + 1, $end, $source, $alias);
        if (trim($body) === '' || $children === []) {
            throw new PortableConfigurationException(
                'DOCUMENT_COMPONENT_BLOCK_CONTENT_REQUIRED',
                "Component [$alias] requires visible block content at [{$location->label()}].",
            );
        }

        return [
            new ComponentBlockNode(
                $alias,
                $component,
                $this->attributes->parse((string) ($opening['attributes'] ?? ''), $alias),
                implode("\n", array_slice($lines, $start, $end - $start + 1)),
                $body,
                new SourceLocation($source, $start + 1, 1, $end + 1),
                $children,
            ),
            $end + 1,
        ];
    }

    /**
     * @param  list<string>  $lines
     * @return list<DocumentNode>
     */
    private function componentBlockChildren(
        array $lines,
        int $start,
        int $end,
        string $source,
        string $alias,
    ): array {
        $children = [];
        for ($index = $start; $index < $end;) {
            $line = $lines[$index];
            if (trim($line) === '') {
                $index++;

                continue;
            }
            if (preg_match('/^(#{1,6})\s+(.+)$/u', $line, $heading) === 1) {
                $children[] = new SourceNode(
                    'heading',
                    $line,
                    new SourceLocation($source, $index + 1, 1, $index + 1),
                    ['level' => strlen($heading[1]), 'text' => trim($heading[2])],
                );
                $index++;

                continue;
            }
            if (str_starts_with($line, '```') || str_starts_with($line, '~~~')) {
                [$code, $next] = $this->codeBlock($lines, $index, $source);
                if ($next > $end) {
                    throw new PortableConfigurationException(
                        'DOCUMENT_COMPONENT_BLOCK_CHILD_INVALID',
                        "Component [$alias] contains a code block outside its boundary at [$source:" . ($index + 1) . ':1].',
                    );
                }
                $children[] = $code;
                $index = $next;

                continue;
            }
            if (str_starts_with(trim($line), '|') && isset($lines[$index + 1])
                && preg_match('/^\s*\|?\s*:?-{3,}/', $lines[$index + 1]) === 1
            ) {
                $childStart = $index;
                do {
                    $index++;
                } while ($index < $end && str_starts_with(trim($lines[$index]), '|'));
                $children[] = new SourceNode(
                    'table',
                    implode("\n", array_slice($lines, $childStart, $index - $childStart)),
                    new SourceLocation($source, $childStart + 1, 1, $index),
                    ['rows' => max(0, $index - $childStart - 2)],
                );

                continue;
            }

            $childStart = $index;
            do {
                $index++;
            } while ($index < $end
                && trim($lines[$index]) !== ''
                && preg_match('/^(?:#{1,6}\s|```|~~~|:::[a-z])/', $lines[$index]) !== 1
                && ! str_starts_with(trim($lines[$index]), '|')
            );
            $raw = implode("\n", array_slice($lines, $childStart, $index - $childStart));
            $children[] = new SourceNode(
                'paragraph',
                $raw,
                new SourceLocation($source, $childStart + 1, 1, $index),
                ['text' => trim(preg_replace('/\s+/u', ' ', $raw) ?? $raw)],
            );
        }

        return $children;
    }

    /** @param list<string> $lines @return array{0:SourceNode,1:int} */
    private function example(array $lines, int $start, string $source): array
    {
        $end = $start + 1;
        $fence = null;
        while (isset($lines[$end])) {
            if ($fence !== null) {
                if (str_starts_with($lines[$end], $fence)) {
                    $fence = null;
                }
                $end++;

                continue;
            }
            if (str_starts_with($lines[$end], '```') || str_starts_with($lines[$end], '~~~')) {
                $fence = substr($lines[$end], 0, 3);
                $end++;

                continue;
            }
            if (trim($lines[$end]) === ':::') {
                break;
            }
            $end++;
        }
        if (! isset($lines[$end])) {
            throw new PortableConfigurationException(
                'DOCUMENT_IR_EXAMPLE_UNCLOSED',
                "Unclosed example at [$source:" . ($start + 1) . ':1].',
            );
        }
        $rawLines = array_slice($lines, $start, $end - $start + 1);
        $children = [];
        for ($index = $start + 1; $index < $end; $index++) {
            if (! str_starts_with($lines[$index], '```') && ! str_starts_with($lines[$index], '~~~')) {
                continue;
            }
            [$code, $next] = $this->codeBlock($lines, $index, $source);
            $children[] = $code;
            if (($code->data['language'] ?? '') === 'markdown') {
                for ($componentLine = $index + 1; $componentLine < $next - 1;) {
                    if (preg_match('/^:::(?<alias>[a-z][a-z0-9_-]*)(?:\s+\{(?<attributes>[^}]*)\})?\s*$/', $lines[$componentLine]) === 1) {
                        [$component, $componentLine] = $this->componentBlock($lines, $componentLine, $source);
                        if ($componentLine > $next - 1) {
                            throw new PortableConfigurationException(
                                'DOCUMENT_IR_EXAMPLE_COMPONENT_BOUNDARY_INVALID',
                                "Example component crosses its Markdown fence at [$source:" . ($index + 1) . ':1].',
                            );
                        }
                        $children[] = $component;

                        continue;
                    }
                    array_push($children, ...$this->components(
                        $lines[$componentLine],
                        $componentLine + 1,
                        $source,
                    ));
                    $componentLine++;
                }
            }
            $index = $next - 1;
        }

        return [
            new SourceNode(
                'example',
                implode("\n", $rawLines),
                new SourceLocation($source, $start + 1, 1, $end + 1),
                ['label' => $this->exampleLabel($lines[$start])],
                $children,
            ),
            $end + 1,
        ];
    }

    /** @param list<string> $lines @return array{0:SourceNode,1:int} */
    private function codeBlock(array $lines, int $start, string $source): array
    {
        $fence = substr($lines[$start], 0, 3);
        $language = trim(substr($lines[$start], 3));
        $end = $start + 1;
        while (isset($lines[$end]) && ! str_starts_with($lines[$end], $fence)) {
            $end++;
        }
        if (! isset($lines[$end])) {
            throw new PortableConfigurationException(
                'DOCUMENT_IR_CODE_BLOCK_UNCLOSED',
                "Unclosed code block at [$source:" . ($start + 1) . ':1].',
            );
        }

        return [
            new SourceNode(
                'code_block',
                implode("\n", array_slice($lines, $start, $end - $start + 1)),
                new SourceLocation($source, $start + 1, 1, $end + 1),
                ['language' => $language],
            ),
            $end + 1,
        ];
    }

    /** @return list<ComponentNode> */
    private function components(string $line, int $lineNumber, string $source): array
    {
        $matches = [];
        preg_match_all(
            '/(?<![\\\pL\pN_]):(?<alias>[a-z][a-z0-9_-]*)\[(?<label>[^\]\r\n]*)\](?:\{(?<attributes>[^}\r\n]*)\})?/u',
            $line,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE,
        );
        $nodes = [];
        foreach ($matches as $match) {
            $alias = $match['alias'][0];
            $location = new SourceLocation($source, $lineNumber, $match[0][1] + 1, $lineNumber);
            $attributeSource = isset($match['attributes']) && $match['attributes'][1] >= 0
                ? $match['attributes'][0]
                : '';
            if (trim($match['label'][0]) === '') {
                throw new PortableConfigurationException(
                    'DOCUMENT_COMPONENT_SLOT_REQUIRED',
                    "Component [$alias] requires visible content at [{$location->label()}].",
                );
            }
            $nodes[] = new ComponentNode(
                $alias,
                $this->aliases->resolve($alias, $location),
                $match['label'][0],
                $this->attributes->parse($attributeSource, $alias),
                $match[0][0],
                $location,
            );
        }

        return $nodes;
    }

    private function exampleLabel(string $opening): string
    {
        return preg_match('/\blabel=(?:"([^"]+)"|([^\s}]+))/', $opening, $match) === 1
            ? (string) ($match[1] !== '' ? $match[1] : $match[2])
            : 'Example';
    }
}
