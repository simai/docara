<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering;

use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;

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
            'docara.header' => $this->viewModels->header($plan),
            'docara.navigation' => $this->viewModels->navigation($plan),
            'docara.outline' => $this->viewModels->outline($plan),
            default => throw new \InvalidArgumentException('SMART_RENDERER_UNSUPPORTED'),
        };

        return new RenderArtifact(
            $this->templates->render($plan->template, ['view' => $view]),
            $plan->assets,
            [
                'runtime' => str_starts_with($plan->smart, 'ui.')
                    ? 'simai-framework'
                    : 'docara.smart.template',
                'smart' => $plan->smart,
                'view' => $plan->view,
            ],
            $plan->provenance + ['template' => $plan->template],
        );
    }
}
