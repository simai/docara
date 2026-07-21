<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering;

use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;
use Simai\Docara\Declarative\Rendering\View\AlertViewModel;
use Simai\Docara\Declarative\Rendering\View\ButtonViewModel;
use Simai\Docara\Declarative\Rendering\View\HeaderViewModel;
use Simai\Docara\Declarative\Rendering\View\NavigationItemViewModel;
use Simai\Docara\Declarative\Rendering\View\NavigationViewModel;
use Simai\Docara\Declarative\Rendering\View\OutlineItemViewModel;
use Simai\Docara\Declarative\Rendering\View\OutlineViewModel;

final class ViewModelFactory
{
    public function alert(ResolvedSmartPlan $plan): AlertViewModel
    {
        if ($plan->smart !== 'ui.alert') {
            throw new \InvalidArgumentException('SMART_VIEW_MODEL_UNSUPPORTED');
        }
        $props = $plan->props;

        return new AlertViewModel(
            $this->escape((string) $props['id']),
            $this->escape((string) $plan->provenance['runtime_pair']),
            $this->escape((string) $props['type']),
            $this->escape((string) $props['variant']),
            $this->escape((string) $props['title']),
            $this->escape((string) $props['supporting-text']),
            $this->escape((string) $props['aria-label']),
            (bool) $props['closable'],
            isset($props['icon']) ? $this->escape((string) $props['icon']) : null,
        );
    }

    public function button(ResolvedSmartPlan $plan): ButtonViewModel
    {
        if ($plan->smart !== 'ui.button') {
            throw new \InvalidArgumentException('SMART_VIEW_MODEL_UNSUPPORTED');
        }
        $props = $plan->props;

        return new ButtonViewModel(
            $this->escape((string) $plan->provenance['runtime_pair']),
            $this->escape((string) $props['text']),
            $this->escape((string) $props['size']),
            $this->escape((string) $props['type']),
            $this->escape((string) $props['scheme']),
            $this->escape((string) $props['native-type']),
            $this->escape((string) $props['aria-label']),
            isset($props['radius']) && $props['radius'] !== ''
                ? $this->escape((string) $props['radius'])
                : null,
            (bool) $props['loading'],
            (bool) $props['disabled'],
        );
    }

    public function brand(ResolvedSmartPlan $plan): HeaderViewModel
    {
        $branding = $plan->props['branding'];

        return new HeaderViewModel(
            $this->escape((string) $branding['title']),
            $branding['label'] === null ? null : $this->escape((string) $branding['label']),
            $this->escape((string) $branding['home_url']),
            $branding['logo'] === null ? null : $this->escape((string) $branding['logo']),
            $branding['logo_dark'] === null ? null : $this->escape((string) $branding['logo_dark']),
        );
    }

    public function navigation(ResolvedSmartPlan $plan): NavigationViewModel
    {
        return new NavigationViewModel(
            $this->navigationItems($plan->props['items']),
            (int) $plan->props['maximum_depth'],
            $this->escape((string) $plan->props['label']),
            $this->escape((string) $plan->props['expand_label']),
            $this->escape((string) $plan->props['collapse_label']),
            $this->escape((string) $plan->props['contains_current_label']),
        );
    }

    public function toc(ResolvedSmartPlan $plan): OutlineViewModel
    {
        $items = [];
        foreach ($plan->props['items'] as $item) {
            $level = (int) $item['level'];
            $items[] = new OutlineItemViewModel(
                $this->escape((string) $item['id']),
                $level,
                $this->escape((string) $item['text']),
                match ($level) {
                    2 => '',
                    3 => 'pl-2',
                    4 => 'pl-4',
                    5 => 'pl-6',
                    default => 'pl-8',
                },
            );
        }

        return new OutlineViewModel($items, $this->escape((string) $plan->props['label']));
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<NavigationItemViewModel>
     */
    private function navigationItems(array $nodes, int $depth = 1): array
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
                    1 => '',
                    2 => 'pl-2',
                    3 => 'pl-4',
                    default => 'pl-6',
                },
                (bool) $node['active'],
                (bool) $node['active_ancestor'],
                (bool) $node['current_section'],
                (bool) $node['open'],
                $children !== [],
                $this->navigationItems($children, $depth + 1),
            );
        }

        return $items;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
