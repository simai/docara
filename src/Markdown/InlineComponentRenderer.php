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

        for ($index = count($matches) - 1; $index >= 0; $index--) {
            $match = $matches[$index];
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
            $placeholder = 'DOCARA_INLINE_' . strtoupper(substr(hash(
                'sha256',
                $name . "\0" . $label . "\0" . $attributeSource . "\0" . $offset . "\0" . count($replacements),
            ), 0, 24));
            while (str_contains($markdown, $placeholder) || isset($replacements[$placeholder])) {
                $placeholder .= 'X';
            }
            $replacements[$placeholder] = $this->render(
                $name,
                $label,
                $attributes,
                new SourceLocation($source, $lineNumber, $offset + 1, $lineNumber),
                $match[0][0],
            );
            $line = substr_replace($line, $placeholder, $offset, strlen($match[0][0]));
        }

        return $line;
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
