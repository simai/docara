<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering;

use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;
use Simai\Docara\Declarative\Rendering\View\NavigationItemTemplateViewModel;
use Simai\Docara\Declarative\Rendering\View\NavigationItemViewModel;
use Simai\Docara\Declarative\Rendering\View\NavigationViewModel;

final readonly class SmartRenderer
{
    public function __construct(
        private TrustedTemplateRegistry $templates = new TrustedTemplateRegistry,
        private ViewModelFactory $viewModels = new ViewModelFactory,
    ) {}

    public function render(ResolvedSmartPlan $plan): RenderArtifact
    {
        $view = match ($plan->smart) {
            'ui.alert' => $this->viewModels->alert($plan),
            'ui.button' => $this->viewModels->button($plan),
            'docara.brand' => $this->viewModels->brand($plan),
            'docara.navigation' => $this->viewModels->navigation($plan),
            'docara.toc' => $this->viewModels->toc($plan),
            default => throw new \InvalidArgumentException('SMART_RENDERER_UNSUPPORTED'),
        };

        $html = $view instanceof NavigationViewModel
            ? $this->navigation($plan->template, $view)
            : $this->templates->render($plan->template, ['view' => $view]);

        return new RenderArtifact(
            $html,
            $plan->assets,
            [
                'runtime' => str_starts_with($plan->smart, 'ui.')
                    ? 'simai-framework'
                    : 'docara.smart.template',
                'smart' => $plan->smart,
                'view' => $plan->view,
                'owner' => (string) ($plan->provenance['provider'] ?? 'unknown'),
                'asset_owner' => $plan->smart,
                'hydration_owner' => $plan->smart,
                'assets' => $plan->assets,
            ],
            $plan->provenance + ['template' => $plan->template],
        );
    }

    private function navigation(string $template, NavigationViewModel $view): string
    {
        $items = '';
        foreach ($view->items as $item) {
            $items .= $this->navigationItem($item, $view);
        }
        $rendered = new class($view->maximumDepth, $items, $view->label, $view->expandLabel, $view->collapseLabel, $view->containsCurrentLabel)
        {
            public function __construct(
                public readonly int $maximumDepth,
                public readonly string $itemsHtml,
                public readonly string $label,
                public readonly string $expandLabel,
                public readonly string $collapseLabel,
                public readonly string $containsCurrentLabel,
            ) {}
        };

        return $this->templates->render($template, ['view' => $rendered]);
    }

    private function navigationItem(NavigationItemViewModel $item, NavigationViewModel $navigation): string
    {
        $children = '';
        foreach ($item->children as $child) {
            $children .= $this->navigationItem($child, $navigation);
        }
        $activeRole = $item->active
            ? 'page'
            : ($item->currentSection ? 'section' : ($item->activeAncestor ? 'ancestor' : null));
        $weightClass = match ($activeRole) {
            'page', 'section' => ' weight-5',
            default => '',
        };

        return $this->templates->render('smart.docara.navigation.item', [
            'view' => new NavigationItemTemplateViewModel(
                $item,
                $children,
                $activeRole,
                $weightClass,
                min(4, $item->depth),
                $navigation->expandLabel,
                $navigation->collapseLabel,
                $navigation->containsCurrentLabel,
            ),
        ]);
    }
}
