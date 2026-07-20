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
            default => throw new \InvalidArgumentException('SMART_RENDERER_UNSUPPORTED'),
        };

        return new RenderArtifact(
            $this->templates->render($plan->template, ['view' => $view]),
            $plan->assets,
            [
                'runtime' => 'simai-framework',
                'smart' => $plan->smart,
                'view' => $plan->view,
            ],
            $plan->provenance + ['template' => $plan->template],
        );
    }
}
