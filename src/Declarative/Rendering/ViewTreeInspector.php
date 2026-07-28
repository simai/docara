<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class ViewTreeInspector
{
    private const TAGS = ['article', 'section', 'div', 'header', 'main', 'aside', 'footer', 'nav'];

    public function __construct(
        private FrameworkUtilityRegistry $utilities = new FrameworkUtilityRegistry,
    ) {}

    /**
     * @param  array<string, mixed>  $tree
     * @return array{regions:list<string>,slots:list<string>,nodes:int,utility_registry:array<string,mixed>}
     */
    public function inspect(array $tree): array
    {
        $regions = [];
        $slots = [];
        $nodes = 0;
        $this->walk($tree, $regions, $slots, $nodes);
        if (count($regions) !== count(array_unique($regions))
            || count($slots) !== count(array_unique($slots))
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_VIEW_TARGET_DUPLICATED',
                'A View Tree cannot place the same region or slot more than once.',
            );
        }

        return [
            'regions' => $regions,
            'slots' => $slots,
            'nodes' => $nodes,
            'utility_registry' => $this->utilities->provenance(),
        ];
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  list<string>  $regions
     * @param  list<string>  $slots
     */
    private function walk(array $node, array &$regions, array &$slots, int &$nodes): void
    {
        $nodes++;
        $kind = $node['kind'] ?? null;
        if ($kind === 'slot') {
            $slot = $node['slot'] ?? null;
            if (! is_string($slot)) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_VIEW_NODE_INVALID',
                    'View Tree slot is invalid.',
                );
            }
            $slots[] = $slot;

            return;
        }
        if ($kind === 'region') {
            $region = $node['region'] ?? null;
            if (! is_string($region)) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_VIEW_NODE_INVALID',
                    'View Tree region is invalid.',
                );
            }
            $regions[] = $region;
        } elseif ($kind !== 'element') {
            throw new PortableConfigurationException(
                'DECLARATIVE_VIEW_NODE_INVALID',
                'View Tree node kind is invalid.',
            );
        }
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
                || (! in_array($name, ['role', 'aria-label'], true)
                    && preg_match('/^data-[a-z0-9-]+$/D', $name) !== 1)
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_VIEW_ATTRIBUTE_NOT_ALLOWED',
                    "View Tree attribute [$name] is not allowed.",
                );
            }
        }
        foreach ($node['children'] ?? [] as $child) {
            if (! is_array($child)) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_VIEW_NODE_INVALID',
                    'View Tree child is invalid.',
                );
            }
            $this->walk($child, $regions, $slots, $nodes);
        }
    }
}
