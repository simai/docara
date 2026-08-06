<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

use Simai\Docara\ComponentCatalog\TypedComponentDefinitionRepository;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;
use Simai\Docara\Markdown\AuthoringAttributeParser;
use Simai\Docara\Portable\PortableConfigurationException;

final class MarkdownCompiler
{
    private int $smartOrdinal = 0;

    private int $compileDepth = 0;

    private int $directiveCount = 0;

    public function __construct(
        private readonly ComponentAliasRegistry $aliases = new ComponentAliasRegistry,
        private readonly AuthoringAttributeParser $attributes = new AuthoringAttributeParser,
        private readonly ?TypedComponentDefinitionRepository $typedComponents = null,
        private readonly ?SmartComponentGateway $smarts = null,
    ) {}

    public function compile(string $markdown, string $source): DocumentIr
    {
        if ($this->compileDepth === 0) {
            $this->smartOrdinal = 0;
            $this->directiveCount = 0;
        }
        $this->compileDepth++;
        try {
            $lines = preg_split('/\r\n|\n|\r/u', $markdown);
            if (! is_array($lines) || $source === '' || trim($markdown) === '') {
                throw new PortableConfigurationException('DOCUMENT_IR_INPUT_INVALID', "Cannot compile [$source].");
            }

            $nodes = [];
            $smartOrdinal = 0;
            for ($index = 0, $count = count($lines); $index < $count;) {
                $line = $lines[$index];
                if (trim($line) === '') {
                    $index++;

                    continue;
                }
                if (str_starts_with(ltrim($line), '<!--')) {
                    $start = $index;
                    do {
                        $closed = str_contains($lines[$index], '-->');
                        $index++;
                    } while (! $closed && $index < $count);
                    if (! $closed) {
                        throw new PortableConfigurationException(
                            'DOCUMENT_HTML_COMMENT_UNCLOSED',
                            "Unclosed HTML comment at [$source:" . ($index + 1) . ':1].',
                        );
                    }
                    $nodes[] = new SourceNode(
                        'html_comment',
                        implode("\n", array_slice($lines, $start, $index - $start)),
                        new SourceLocation($source, $start + 1, 1, $index),
                    );

                    continue;
                }
                if (preg_match('/^:::(?:example)(?:\s|\{|$)/', $line) === 1) {
                    [$node, $index] = $this->example($lines, $index, $source);
                    $nodes[] = $node;

                    continue;
                }
                if (preg_match('/^(?<fence>:{3,})(?<smart>[a-z][a-z0-9-]*\.[a-z][a-z0-9.-]*)\s*$/D', $line, $smart) === 1) {
                    $this->countDirective($source, $index + 1);
                    [$node, $index] = $this->smartComponent(
                        $lines,
                        $index,
                        $source,
                        (string) $smart['smart'],
                        ++$this->smartOrdinal,
                    );
                    $nodes[] = $node;

                    continue;
                }
                if (preg_match('/^(?<fence>:{3,})(?<alias>[a-z][a-z0-9_-]*)(?:\s+\{(?<attributes>[^}]*)\})?\s*$/', $line, $directive) === 1) {
                    $this->countDirective($source, $index + 1);
                    [$node, $index] = array_key_exists((string) $directive['alias'], $this->aliases->aliases())
                        ? $this->componentBlock($lines, $index, $source)
                        : $this->typedDirectiveBlock($lines, $index, $source);
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
                if (preg_match('/^\s*(?:[-+*]|\d+[.)])\s+/u', $line) === 1) {
                    $start = $index;
                    do {
                        $index++;
                    } while ($index < $count && (
                        trim($lines[$index]) === ''
                        || preg_match('/^\s+(?:[-+*]|\d+[.)])\s+|^\s{2,}\S/u', $lines[$index]) === 1
                        || preg_match('/^\s*(?:[-+*]|\d+[.)])\s+/u', $lines[$index]) === 1
                    ));
                    while ($index > $start && trim($lines[$index - 1]) === '') {
                        $index--;
                    }
                    $nodes[] = new SourceNode(
                        'list',
                        implode("\n", array_slice($lines, $start, $index - $start)),
                        new SourceLocation($source, $start + 1, 1, $index),
                        ['ordered' => preg_match('/^\s*\d+[.)]\s+/u', $line) === 1],
                    );

                    continue;
                }
                if (preg_match('/^\s*>/u', $line) === 1) {
                    $start = $index;
                    do {
                        $index++;
                    } while ($index < $count && (
                        preg_match('/^\s*>/u', $lines[$index]) === 1
                        || preg_match('/^\s*\{(?:author|source|url)=/u', $lines[$index]) === 1
                    ));
                    $nodes[] = new SourceNode(
                        'blockquote',
                        implode("\n", array_slice($lines, $start, $index - $start)),
                        new SourceLocation($source, $start + 1, 1, $index),
                    );

                    continue;
                }
                if (preg_match('/^!\[(?<alt>[^]]+)]\((?<url>[^)]+)\)(?:\{(?<attributes>[^}]*)})?\s*$/u', $line, $image) === 1) {
                    $nodes[] = new SourceNode(
                        'image',
                        $line,
                        new SourceLocation($source, $index + 1, 1, $index + 1),
                        ['alt' => $image['alt'], 'url' => $image['url']],
                    );
                    $index++;

                    continue;
                }

                $start = $index;
                do {
                    $index++;
                } while ($index < $count
                    && trim($lines[$index]) !== ''
                    && preg_match('/^(?:#{1,6}\s|```|~~~|:::[a-z]|\s*(?:[-+*]|\d+[.)])\s+|\s*>|!\[)/', $lines[$index]) !== 1
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
        } finally {
            $this->compileDepth--;
        }
    }

    /** @param list<string> $lines @return array{0:SmartComponentNode,1:int} */
    private function smartComponent(array $lines, int $start, string $source, string $smart, int $ordinal): array
    {
        preg_match('/^(?<fence>:{3,})/', $lines[$start], $opening);
        $fence = (string) ($opening['fence'] ?? ':::');
        $end = $start + 1;
        while (isset($lines[$end]) && trim($lines[$end]) !== $fence) {
            if (preg_match('/^:{3,}[a-z]/', $lines[$end]) === 1) {
                throw new PortableConfigurationException('DOCUMENT_SMART_NESTED_FORBIDDEN', "$smart at $source:" . ($end + 1));
            }
            $end++;
        }
        $location = new SourceLocation($source, $start + 1, 1, $start + 1);
        if (! isset($lines[$end])) {
            throw $this->locatedException('DOCUMENT_SMART_UNCLOSED', "$smart is not closed.", $location);
        }
        $payload = trim(implode("\n", array_slice($lines, $start + 1, $end - $start - 1)));
        try {
            $props = $payload === '' ? [] : json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw $this->locatedException('DOCUMENT_SMART_PROPS_JSON_INVALID', "$smart requires valid JSON props.", $location, $exception);
        }
        if (! is_array($props) || ($props !== [] && array_is_list($props))) {
            throw $this->locatedException('DOCUMENT_SMART_PROPS_INVALID', "$smart requires object props.", $location);
        }
        $view = $props['view'] ?? 'default';
        unset($props['view']);
        if (! is_string($view) || preg_match('/^[a-z][a-z0-9_-]*$/D', $view) !== 1) {
            throw $this->locatedException('DOCUMENT_SMART_VIEW_INVALID', "$smart has an invalid view.", $location);
        }

        return [new SmartComponentNode(
            $smart,
            $view,
            $props,
            $ordinal,
            implode("\n", array_slice($lines, $start, $end - $start + 1)),
            new SourceLocation($source, $start + 1, 1, $end + 1),
        ), $end + 1];
    }

    /** @param list<string> $lines @return array{0:SourceNode|ContainerNode,1:int} */
    private function typedDirectiveBlock(array $lines, int $start, string $source): array
    {
        preg_match(
            '/^(?<fence>:{3,})(?<alias>[a-z][a-z0-9_-]*)(?:\s+\{(?<attributes>[^}]*)\})?\s*$/',
            $lines[$start],
            $opening,
        );
        $alias = (string) ($opening['alias'] ?? '');
        $location = new SourceLocation($source, $start + 1, 1, $start + 1);
        $definition = ($this->typedComponents ?? TypedComponentDefinitionRepository::bundled())->findByName($alias);
        if ($definition === null) {
            throw new PortableConfigurationException(
                'DOCUMENT_COMPONENT_ALIAS_UNKNOWN',
                "Unknown component alias [$alias] at [{$location->label()}].",
            );
        }
        $fence = (string) ($opening['fence'] ?? ':::');
        $end = $start + 1;
        while (isset($lines[$end]) && trim($lines[$end]) !== $fence) {
            if (! $this->isContainerDefinition($definition)
            && preg_match('/^:{3,}[a-z]/', $lines[$end]) === 1
            ) {
                throw $this->locatedException(
                    'DOCUMENT_TYPED_DIRECTIVE_NESTED_FORBIDDEN',
                    'A non-container typed directive cannot contain another directive.',
                    new SourceLocation($source, $end + 1, 1, $end + 1),
                );
            }
            $end++;
        }
        if (! isset($lines[$end])) {
            throw $this->locatedException(
                'DOCUMENT_TYPED_DIRECTIVE_UNCLOSED',
                "Typed directive [$alias] is not closed.",
                $location,
            );
        }

        if ($this->isContainerDefinition($definition)) {
            $bodyLines = array_slice($lines, $start + 1, $end - $start - 1);
            $body = implode("\n", array_merge(array_fill(0, $start + 1, ''), $bodyLines));
            if (trim($body) === '') {
                throw new PortableConfigurationException(
                    'DOCUMENT_CONTAINER_CHILD_COUNT_MIN',
                    "Container [$alias] is empty at [{$location->label()}].",
                    diagnosticPath: $source,
                    diagnosticPointer: '/document/container',
                    diagnosticLine: $location->line,
                    diagnosticColumn: $location->column,
                );
            }
            $children = $this->compile($body, $source)->nodes;
            $node = new ContainerNode(
                $alias,
                (string) $definition['id'],
                (string) $definition['renderer'],
                $this->locatedAttributes((string) ($opening['attributes'] ?? ''), $alias, $location),
                implode("\n", array_slice($lines, $start, $end - $start + 1)),
                new SourceLocation($source, $start + 1, 1, $end + 1),
                $children,
            );
            (new ContainerContractValidator(
                $this->typedComponents ?? TypedComponentDefinitionRepository::bundled(),
                $this->smarts ?? SmartComponentGateway::content(),
            ))->validate($alias, $children, $node->location());

            return [$node, $end + 1];
        }

        return [
            new SourceNode(
                'typed_directive',
                implode("\n", array_slice($lines, $start, $end - $start + 1)),
                new SourceLocation($source, $start + 1, 1, $end + 1),
                [
                    'alias' => $alias,
                    'component' => (string) $definition['id'],
                    'renderer' => (string) $definition['renderer'],
                    'props' => $this->locatedAttributes((string) ($opening['attributes'] ?? ''), $alias, $location),
                ],
            ),
            $end + 1,
        ];
    }

    /** @return array<string,string> */
    private function locatedAttributes(string $source, string $component, SourceLocation $location): array
    {
        try {
            return $this->attributes->parse($source, $component);
        } catch (PortableConfigurationException $exception) {
            throw new PortableConfigurationException(
                $exception->errorCode,
                $exception->getMessage() . ' Source [' . $location->label() . '].',
                $exception,
                $location->file,
                '/document/attributes',
                $location->line,
                $location->column,
            );
        }
    }

    private function countDirective(string $source, int $line): void
    {
        $this->directiveCount++;
        if ($this->directiveCount <= 128) {
            return;
        }
        throw new PortableConfigurationException(
            'DOCUMENT_DIRECTIVE_LIMIT_EXCEEDED',
            'A compiled Markdown document may contain at most 128 admitted directive nodes.',
            diagnosticPath: $source,
            diagnosticPointer: '/document/directives',
            diagnosticLine: $line,
            diagnosticColumn: 1,
        );
    }

    private function locatedException(
        string $code,
        string $message,
        SourceLocation $location,
        ?\Throwable $previous = null,
    ): PortableConfigurationException {
        return new PortableConfigurationException(
            $code,
            $message . ' Source [' . $location->label() . '].',
            $previous,
            $location->file,
            '/document/directive',
            $location->line,
            $location->column,
        );
    }

    /** @param array<string,mixed> $definition */
    private function isContainerDefinition(array $definition): bool
    {
        return is_array($definition['container_contract'] ?? null)
            || is_array($definition['nesting_contract'] ?? null);
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
                    if (preg_match('/^:::(?<alias>[a-z][a-z0-9_-]*)(?:\s+\{(?<attributes>[^}]*)\})?\s*$/', $lines[$componentLine], $directive) === 1) {
                        [$component, $componentLine] = array_key_exists((string) $directive['alias'], $this->aliases->aliases())
                            ? $this->componentBlock($lines, $componentLine, $source)
                            : $this->typedDirectiveBlock($lines, $componentLine, $source);
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
        if (preg_match('/^(?<fence>`{3,}|~{3,})(?<language>.*)$/', $lines[$start], $opening) !== 1) {
            throw new PortableConfigurationException(
                'DOCUMENT_IR_CODE_BLOCK_OPENING_INVALID',
                "Invalid code block opening at [$source:" . ($start + 1) . ':1].',
            );
        }
        $fence = (string) $opening['fence'];
        $language = trim((string) $opening['language']);
        $end = $start + 1;
        while (isset($lines[$end]) && trim($lines[$end]) !== $fence) {
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
