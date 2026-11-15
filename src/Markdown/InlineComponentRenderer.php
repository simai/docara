<?php

declare(strict_types=1);

namespace Simai\Docara\Markdown;

use Simai\Docara\Declarative\Smart\SmartComponentGateway;
use Simai\Docara\Document\ComponentAliasRegistry;
use Simai\Docara\Document\ComponentNode;
use Simai\Docara\Document\SourceLocation;
use Simai\Docara\Portable\PortableConfigurationException;

final class InlineComponentRenderer
{
    private const KEY_COMPONENT = 'docara.kbd';

    public function __construct(
        private readonly AuthoringAttributeParser $attributes = new AuthoringAttributeParser,
        private readonly ComponentAliasRegistry $aliases = new ComponentAliasRegistry,
        private readonly SmartComponentGateway $components = new SmartComponentGateway,
    ) {}

    /** @return array{markdown:string,replacements:array<string,string>} */
    public function extract(string $markdown, array $literalCodeLines, string $source = '@markdown'): array
    {
        $lines = preg_split('/\r\n|\n|\r/u', $markdown);
        if (! is_array($lines)) {
            throw new PortableConfigurationException('MARKDOWN_INLINE_INPUT_INVALID', 'Markdown could not be split into lines.');
        }

        $replacements = [];
        foreach ($lines as $index => &$line) {
            if (isset($literalCodeLines[$index + 1])) {
                continue;
            }
            $line = $this->extractLine($line, $replacements, $markdown, $source, $index + 1);
        }
        unset($line);

        return ['markdown' => implode("\n", $lines), 'replacements' => $replacements];
    }

    /** @param array<string,string> $replacements */
    private function extractLine(
        string $line,
        array &$replacements,
        string $markdown,
        string $source,
        int $lineNumber,
    ): string {
        $names = implode('|', array_map(
            static fn (string $name): string => preg_quote($name, '/'),
            array_keys($this->aliases->inlineAliases()),
        ));
        $pattern = '/(?<![\\\\\pL\pN_]):(?<name>' . $names . ')\[(?<label>[^\]\r\n]*)\](?:\{(?<attributes>[^}\r\n]*)\})?/u';
        $matches = [];
        if (preg_match_all($pattern, $line, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === false) {
            return $line;
        }

        // Each component outside inline code, rendered in source order; keys
        // joined by `+` (`:kbd[⌘] + :kbd[K]`) then become one combination.
        $rendered = [];
        foreach ($matches as $match) {
            $offset = $match[0][1];
            if ($this->insideInlineCode($line, $offset)) {
                continue;
            }
            $name = $match['name'][0];
            $label = $match['label'][0];
            $attributeSource = isset($match['attributes']) && $match['attributes'][1] >= 0
                ? $match['attributes'][0]
                : '';
            $attributes = $this->attributes->parse($attributeSource, $name);
            $location = new SourceLocation($source, $lineNumber, $offset + 1, $lineNumber);
            $rendered[] = [
                'offset' => $offset,
                'length' => strlen($match[0][0]),
                'key' => $this->aliases->resolve($name, $location) === self::KEY_COMPONENT,
                'seed' => $name . "\0" . $label . "\0" . $attributeSource,
                'html' => $this->render($name, $label, $attributes, $location, $match[0][0]),
            ];
        }

        $runs = [];
        foreach ($rendered as $component) {
            $last = $runs === [] ? null : $runs[array_key_last($runs)];
            $previous = $last === null ? null : $last[array_key_last($last)];
            if ($previous !== null && $previous['key'] && $component['key']) {
                $between = substr($line, $previous['offset'] + $previous['length'], $component['offset'] - $previous['offset'] - $previous['length']);
                if (preg_match('/\A[ \t]*\+[ \t]*\z/D', $between) === 1) {
                    $runs[array_key_last($runs)][] = $component;

                    continue;
                }
            }
            $runs[] = [$component];
        }

        for ($index = count($runs) - 1; $index >= 0; $index--) {
            $run = $runs[$index];
            $first = $run[0];
            $end = $run[count($run) - 1]['offset'] + $run[count($run) - 1]['length'];
            $html = count($run) === 1
                ? $first['html']
                : self::keyCombination(array_column($run, 'html'));
            $placeholder = 'DOCARA_INLINE_' . strtoupper(substr(hash(
                'sha256',
                implode("\0", array_column($run, 'seed')) . "\0" . $first['offset'] . "\0" . count($replacements),
            ), 0, 24));
            while (str_contains($markdown, $placeholder) || isset($replacements[$placeholder])) {
                $placeholder .= 'X';
            }
            $replacements[$placeholder] = $html;
            $line = substr_replace($line, $placeholder, $first['offset'], $end - $first['offset']);
        }

        return $line;
    }

    /**
     * A combination in the SIMAI Framework key markup (component.kbd 1.3):
     * the keys inside one `<kbd class="sf-kbd--group">`, with the author's
     * `+` kept as `.sf-kbd-join`, which the Framework shows only where keys
     * are drawn without a frame.
     *
     * @param  list<string>  $keys
     */
    private static function keyCombination(array $keys): string
    {
        return '<kbd class="sf-kbd--group">'
            . implode('<span class="sf-kbd-join">+</span>', $keys)
            . '</kbd>';
    }

    private function insideInlineCode(string $line, int $offset): bool
    {
        $prefix = substr($line, 0, $offset);
        if ($prefix === false) {
            return false;
        }
        preg_match_all('/(?<!\\\\)`+/u', $prefix, $ticks);

        return count($ticks[0]) % 2 === 1;
    }

    /** @param array<string,string> $attributes */
    private function render(
        string $name,
        string $label,
        array $attributes,
        SourceLocation $location,
        string $raw,
    ): string {
        if (trim($label) === '') {
            throw new PortableConfigurationException(
                'MARKDOWN_INLINE_COMPONENT_LABEL_REQUIRED',
                "Markdown component [$name] requires visible content.",
            );
        }

        return $this->components->renderComponentContract(new ComponentNode(
            $name,
            $this->aliases->resolve($name, $location),
            $label,
            $attributes,
            $raw,
            $location,
        ))->html;
    }
}
