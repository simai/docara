<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class SafeElementRenderer
{
    private const TAGS = ['div', 'p', 'span', 'a'];

    public function __construct(
        private FrameworkUtilityRegistry $utilities = new FrameworkUtilityRegistry,
    ) {}

    /** @param array<string, mixed> $element */
    public function render(array $element): string
    {
        $tag = $element['tag'] ?? null;
        $text = $element['text'] ?? null;
        $classes = $element['utilities'] ?? [];
        if (array_diff(array_keys($element), ['tag', 'text', 'href', 'aria_label', 'utilities']) !== []
            || ! is_string($tag)
            || ! in_array($tag, self::TAGS, true)
            || ! is_string($text)
            || trim($text) === ''
            || mb_strlen($text) > 500
            || ! is_array($classes)
            || ! array_is_list($classes)
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_ELEMENT_INVALID',
                'Declarative element data is invalid.',
            );
        }
        $this->utilities->assertAllowed($classes);
        $attributes = [];
        if ($classes !== []) {
            $attributes['class'] = implode(' ', $classes);
        }
        $href = $element['href'] ?? null;
        if ($tag === 'a') {
            if (! is_string($href) || ! $this->safeHref($href)) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_ELEMENT_HREF_INVALID',
                    'Declarative link requires a safe local href.',
                );
            }
            $attributes['href'] = $href;
        } elseif ($href !== null) {
            throw new PortableConfigurationException(
                'DECLARATIVE_ELEMENT_HREF_FORBIDDEN',
                'Only declarative link elements can define href.',
            );
        }
        $ariaLabel = $element['aria_label'] ?? null;
        if ($ariaLabel !== null) {
            if (! is_string($ariaLabel) || trim($ariaLabel) === '' || mb_strlen($ariaLabel) > 160) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_ELEMENT_ARIA_LABEL_INVALID',
                    'Declarative element aria label is invalid.',
                );
            }
            $attributes['aria-label'] = $ariaLabel;
        }
        $serialized = '';
        foreach ($attributes as $name => $value) {
            $serialized .= ' ' . $name . '="' . $this->escape($value) . '"';
        }

        return '<' . $tag . $serialized . '>' . $this->escape($text) . '</' . $tag . '>';
    }

    private function safeHref(string $href): bool
    {
        return ($href[0] ?? '') === '#'
            ? preg_match('/^#[a-z][a-z0-9_.:-]*$/Di', $href) === 1
            : str_starts_with($href, '/')
                && ! str_starts_with($href, '//')
                && preg_match('/[\x00-\x20"\'<>\\\\]/', $href) !== 1;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
