<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Runtime\Context;

use Simai\Docara\Declarative\Rendering\TrustedTemplateRegistry;
use Simai\Docara\Declarative\Rendering\View\NavigationItemTemplateViewModel;
use Simai\Docara\Declarative\Rendering\View\NavigationItemViewModel;
use Simai\Docara\Smart\Runtime\SmartInvocation;

final readonly class NavigationContextAdapter implements SmartContextAdapter
{
    public function __construct(private TrustedTemplateRegistry $templates = new TrustedTemplateRegistry) {}

    public function id(): string
    {
        return 'docara.navigation';
    }

    public function prepare(SmartInvocation $invocation): object
    {
        $maximumDepth = (int) ($invocation->props['maximum_depth'] ?? 0);
        $expandLabel = $this->escape((string) ($invocation->props['expand_label'] ?? ''));
        $collapseLabel = $this->escape((string) ($invocation->props['collapse_label'] ?? ''));
        $containsCurrentLabel = $this->escape((string) ($invocation->props['contains_current_label'] ?? ''));
        $items = $this->items($invocation->props['items'] ?? []);
        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= $this->renderItem($item, $invocation->view, $expandLabel, $collapseLabel, $containsCurrentLabel);
        }

        return new class($maximumDepth, $itemsHtml, $this->escape((string) ($invocation->props['label'] ?? '')), $expandLabel, $collapseLabel, $containsCurrentLabel, $items !== [])
        {
            public function __construct(
                public readonly int $maximumDepth,
                public readonly string $itemsHtml,
                public readonly string $label,
                public readonly string $expandLabel,
                public readonly string $collapseLabel,
                public readonly string $containsCurrentLabel,
                public readonly bool $hasItems,
            ) {}
        };
    }

    /** @param list<array<string, mixed>> $nodes @return list<NavigationItemViewModel> */
    private function items(array $nodes, int $depth = 1): array
    {
        $items = [];
        foreach ($nodes as $node) {
            $children = $node['children'];
            $items[] = new NavigationItemViewModel(
                $this->escape((string) $node['key']),
                $this->escape((string) $node['title']),
                $node['url'] === null ? null : $this->escape((string) $node['url']),
                $depth,
                match ($depth) {
                    1 => '', 2 => 'pl-2', 3 => 'pl-4', default => 'pl-6'
                },
                (bool) $node['active'],
                (bool) $node['active_ancestor'],
                (bool) $node['current_section'],
                (bool) $node['open'],
                $children !== [],
                $this->items($children, $depth + 1),
            );
        }

        return $items;
    }

    private function renderItem(NavigationItemViewModel $item, string $view, string $expand, string $collapse, string $contains): string
    {
        $children = '';
        foreach ($item->children as $child) {
            $children .= $this->renderItem($child, $view, $expand, $collapse, $contains);
        }
        $activeRole = $item->active ? 'page' : ($item->currentSection ? 'section' : ($item->activeAncestor ? 'ancestor' : null));

        return $this->templates->render(
            $view === 'header' ? 'smart.docara.navigation.header-item' : 'smart.docara.navigation.item',
            ['view' => new NavigationItemTemplateViewModel(
                $item,
                $children,
                $activeRole,
                in_array($activeRole, ['page', 'section'], true) ? ' weight-5' : '',
                min(4, $item->depth),
                $expand,
                $collapse,
                $contains,
            )],
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
