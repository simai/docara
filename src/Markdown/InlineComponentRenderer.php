<?php

declare(strict_types=1);

namespace Simai\Docara\Markdown;

use League\CommonMark\Util\RegexHelper;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;
use Simai\Docara\Document\ComponentAliasRegistry;
use Simai\Docara\Document\ComponentNode;
use Simai\Docara\Document\SourceLocation;
use Simai\Docara\Portable\PortableConfigurationException;

final class InlineComponentRenderer
{
    private const NAMES = ['badge', 'button', 'icon', 'kbd'];

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
        $pattern = '/(?<![\\\\\pL\pN_]):(?<name>badge|button|icon|kbd)\[(?<label>[^\]\r\n]*)\](?:\{(?<attributes>[^}\r\n]*)\})?/u';
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
        if (! in_array($name, self::NAMES, true)) {
            throw new PortableConfigurationException('MARKDOWN_INLINE_COMPONENT_UNSUPPORTED', $name);
        }
        if (trim($label) === '') {
            throw new PortableConfigurationException(
                'MARKDOWN_INLINE_COMPONENT_LABEL_REQUIRED',
                "Markdown component [$name] requires visible content.",
            );
        }

        return match ($name) {
            'badge' => $this->components->renderComponent(new ComponentNode(
                $name,
                $this->aliases->resolve($name, $location),
                $label,
                $attributes,
                $raw,
                $location,
            ))->html,
            'button' => $this->button($label, $attributes),
            'icon' => $this->icon($label, $attributes),
            'kbd' => $this->kbd($label, $attributes),
        };
    }

    /** @param array<string,string> $attributes */
    private function icon(string $label, array $attributes): string
    {
        $this->only(
            $attributes,
            ['size', 'family', 'weight', 'filled', 'label', 'container', 'variant', 'scheme'],
            'icon',
        );
        if (preg_match('/^[a-z][a-z0-9_]*$/D', $label) !== 1) {
            throw new PortableConfigurationException('MARKDOWN_ICON_NAME_INVALID', $label);
        }
        $size = $this->oneOf($attributes['size'] ?? '1/2', ['1/3', '1/2', '1', '2', '3'], 'icon', 'size');
        $family = $this->oneOf($attributes['family'] ?? 'outlined', ['outlined', 'rounded', 'sharp'], 'icon', 'family');
        $weight = $this->oneOf($attributes['weight'] ?? 'regular', ['light', 'regular', 'medium'], 'icon', 'weight');
        $filled = $this->boolean($attributes['filled'] ?? 'false', 'icon', 'filled');
        $container = $this->oneOf(
            $attributes['container'] ?? 'none',
            ['none', 'square', 'circle'],
            'icon',
            'container',
        );
        $variant = $this->oneOf(
            $attributes['variant'] ?? ($container === 'none' ? 'plain' : 'tonal'),
            ['plain', 'main', 'tonal', 'outline'],
            'icon',
            'variant',
        );
        $scheme = $this->oneOf(
            $attributes['scheme'] ?? 'primary',
            ['primary', 'secondary', 'tertiary', 'neutral', 'info', 'success', 'warning', 'danger', 'on-surface'],
            'icon',
            'scheme',
        );
        if ($container === 'none' && $variant !== 'plain') {
            throw new PortableConfigurationException(
                'MARKDOWN_COMPONENT_ATTRIBUTE_COMBINATION_INVALID',
                'Markdown component [icon] requires [container=square|circle] for a non-plain variant.',
            );
        }
        if ($container !== 'none' && $variant === 'plain') {
            throw new PortableConfigurationException(
                'MARKDOWN_COMPONENT_ATTRIBUTE_COMBINATION_INVALID',
                'Markdown component [icon] does not admit a plain variant with a visual container.',
            );
        }
        $accessibleLabel = trim($attributes['label'] ?? '');
        $classes = ['sf-icon', 'sf-icon-loaded', 'sf-icon-' . $weight, 'sf-icon--size-' . $size];
        if ($family === 'rounded') {
            $classes[] = 'sf-icon-rounded';
        } elseif ($family === 'sharp') {
            $classes[] = 'sf-icon-shape';
        }
        if ($filled) {
            $classes[] = 'sf-icon-filled';
        }
        $accessibility = $accessibleLabel === ''
            ? ' aria-hidden="true"'
            : ' role="img" aria-label="' . $this->escape($accessibleLabel) . '"';

        $icon = '<i class="' . implode(' ', $classes) . '"' . $accessibility . '>' . $label . '</i>';

        return '<span class="docara-icon inline-grid" data-docara-icon-container="' . $container
            . '" data-docara-icon-variant="' . $variant . '" data-docara-icon-scheme="' . $scheme
            . '" data-docara-icon-size="' . $size . '">' . $icon . '</span>';
    }

    /** @param array<string,string> $attributes */
    private function button(string $label, array $attributes): string
    {
        $this->only($attributes, ['href', 'type', 'scheme', 'size', 'icon'], 'button');
        $href = $attributes['href'] ?? null;
        if (! is_string($href) || trim($href) === '') {
            throw new PortableConfigurationException('MARKDOWN_BUTTON_HREF_REQUIRED', 'Inline button requires href.');
        }
        if (preg_match(RegexHelper::REGEX_UNSAFE_PROTOCOL, $href) === 1) {
            throw new PortableConfigurationException('MARKDOWN_BUTTON_HREF_UNSAFE', $href);
        }
        $type = $this->oneOf($attributes['type'] ?? 'default', ['default', 'tonal', 'outline', 'link'], 'button', 'type');
        $scheme = $this->oneOf($attributes['scheme'] ?? 'primary', ['primary', 'secondary', 'on-surface'], 'button', 'scheme');
        $size = $this->oneOf($attributes['size'] ?? '1', ['1/2', '1', '2'], 'button', 'size');
        $icon = $attributes['icon'] ?? '';
        if ($icon !== '' && preg_match('/^[a-z][a-z0-9_]*$/D', $icon) !== 1) {
            throw new PortableConfigurationException('MARKDOWN_BUTTON_ICON_INVALID', $icon);
        }

        return '<a class="sf-button sf-button--' . $type . ' sf-button--' . $scheme . ' sf-button--size-' . $size
            . ' inline-flex items-center decoration-none" href="' . $this->escape($href) . '">'
            . '<span class="sf-button-text-container">' . $this->escape($label) . '</span>'
            . ($icon === '' ? '' : '<i class="sf-icon sf-icon-loaded" aria-hidden="true">' . $icon . '</i>')
            . '</a>';
    }

    /** @param array<string,string> $attributes */
    private function kbd(string $label, array $attributes): string
    {
        $this->only($attributes, [], 'kbd');

        return '<kbd class="inline-flex items-center bg-surface-container border border-outline-variant radius-1 p-inline-1/3">'
            . $this->escape($label) . '</kbd>';
    }

    /** @param array<string,string> $attributes @param list<string> $allowed */
    private function only(array $attributes, array $allowed, string $component): void
    {
        $unknown = array_values(array_diff(array_keys($attributes), $allowed));
        if ($unknown !== []) {
            throw new PortableConfigurationException(
                'MARKDOWN_COMPONENT_ATTRIBUTE_UNKNOWN',
                "Markdown component [$component] does not support attribute [{$unknown[0]}].",
            );
        }
    }

    /** @param list<string> $allowed */
    private function oneOf(string $value, array $allowed, string $component, string $attribute): string
    {
        if (! in_array($value, $allowed, true)) {
            throw new PortableConfigurationException(
                'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID',
                "Markdown component [$component] has invalid [$attribute] value [$value].",
            );
        }

        return $value;
    }

    private function boolean(string $value, string $component, string $attribute): bool
    {
        return match ($value) {
            'true' => true,
            'false' => false,
            default => throw new PortableConfigurationException(
                'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID',
                "Markdown component [$component] has invalid [$attribute] value [$value].",
            ),
        };
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
