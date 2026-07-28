<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class ViewTreeRenderer
{
    private const TAGS = ['article', 'section', 'div', 'header', 'main', 'aside', 'footer', 'nav'];

    private const ATTRIBUTES = ['role', 'aria-label'];

    public function __construct(
        private FrameworkUtilityRegistry $utilities = new FrameworkUtilityRegistry,
    ) {}

    /**
     * @param  array<string, mixed>  $tree
     * @param  array<string, string>  $regions
     * @param  array<string, string>  $slots
     * @param  array<string, string>  $identity
     */
    public function render(array $tree, array $regions = [], array $slots = [], array $identity = []): string
    {
        return $this->node($tree, $regions, $slots, $identity);
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, string>  $regions
     * @param  array<string, string>  $slots
     * @param  array<string, string>  $identity
     */
    private function node(array $node, array $regions, array $slots, array $identity): string
    {
        $kind = $node['kind'] ?? null;
        if ($kind === 'slot') {
            $slot = $node['slot'] ?? null;
            if (! is_string($slot) || ! array_key_exists($slot, $slots)) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_VIEW_SLOT_UNRESOLVED',
                    "View Tree slot [$slot] was not resolved.",
                );
            }

            return $slots[$slot];
        }
        if ($kind === 'region') {
            $region = $node['region'] ?? null;
            if (! is_string($region) || ! array_key_exists($region, $regions)) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_VIEW_REGION_UNRESOLVED',
                    "View Tree region [$region] was not resolved.",
                );
            }
            if (($identity['enabled:' . $region] ?? 'false') !== 'true') {
                return '';
            }

            return $this->element(
                $node,
                $regions[$region],
                ['data-docara-region' => $region],
            );
        }
        if ($kind !== 'element') {
            throw new PortableConfigurationException(
                'DECLARATIVE_VIEW_NODE_INVALID',
                'View Tree contains an unsupported node.',
            );
        }

        $content = '';
        foreach ($node['children'] ?? [] as $child) {
            if (! is_array($child)) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_VIEW_NODE_INVALID',
                    'View Tree contains an invalid child.',
                );
            }
            $content .= $this->node($child, $regions, $slots, $identity);
        }
        $attributes = [];
        if (($node['identity'] ?? null) === 'page') {
            $attributes = [
                'data-docara-declarative-page' => $identity['page_key'] ?? '',
                'data-docara-page-title' => $identity['page_title'] ?? '',
            ];
        } elseif (($node['identity'] ?? null) === 'section') {
            $attributes = [
                'id' => $identity['section_id'] ?? '',
                'data-docara-section' => $identity['section_key'] ?? '',
                'data-docara-region-owner' => $identity['section_region'] ?? '',
            ];
        }

        return $this->element($node, $content, $attributes);
    }

    /** @param array<string, mixed> $node @param array<string, string> $dynamicAttributes */
    private function element(array $node, string $content, array $dynamicAttributes): string
    {
        $tag = $node['tag'] ?? null;
        if (! is_string($tag) || ! in_array($tag, self::TAGS, true)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_VIEW_TAG_NOT_ALLOWED',
                "View Tree tag [$tag] is not allowed.",
            );
        }
        $utilities = $node['utilities'] ?? [];
        if (! is_array($utilities) || ! array_is_list($utilities)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_VIEW_UTILITY_INVALID',
                'View Tree utilities must be a list.',
            );
        }
        $this->utilities->assertAllowed($utilities);
        $attributes = $node['attributes'] ?? [];
        if (! is_array($attributes)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_VIEW_ATTRIBUTE_INVALID',
                'View Tree attributes must be an object.',
            );
        }
        foreach ($attributes as $name => $value) {
            if (! is_string($name)
                || ! is_string($value)
                || (! in_array($name, self::ATTRIBUTES, true) && preg_match('/^data-[a-z0-9-]+$/D', $name) !== 1)
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_VIEW_ATTRIBUTE_NOT_ALLOWED',
                    "View Tree attribute [$name] is not allowed.",
                );
            }
        }
        if ($utilities !== []) {
            $attributes['class'] = implode(' ', $utilities);
        }
        $attributes = $dynamicAttributes + $attributes;
        $serialized = '';
        foreach ($attributes as $name => $value) {
            if ($value === '') {
                throw new PortableConfigurationException(
                    'DECLARATIVE_VIEW_IDENTITY_INVALID',
                    "View Tree attribute [$name] resolved to an empty value.",
                );
            }
            $serialized .= ' ' . $name . '="' . $this->escape($value) . '"';
        }

        return '<' . $tag . $serialized . '>' . $content . '</' . $tag . '>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
