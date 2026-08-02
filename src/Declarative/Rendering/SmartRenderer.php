<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering;

use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;
use Simai\Docara\Smart\Runtime\Context\BrandingContextAdapter;
use Simai\Docara\Smart\Runtime\Context\GenericPropsContextAdapter;
use Simai\Docara\Smart\Runtime\Context\NavigationContextAdapter;
use Simai\Docara\Smart\Runtime\Context\OutlineContextAdapter;
use Simai\Docara\Smart\Runtime\Context\PreferencesContextAdapter;
use Simai\Docara\Smart\Runtime\Context\SmartContextAdapterRegistry;
use Simai\Docara\Smart\Runtime\SmartInvocation;
use Simai\Docara\Smart\Runtime\SmartTemplateContext;
use Simai\Docara\Smart\Runtime\Strategy\RegisteredTemplateStrategy;
use Simai\Docara\Smart\Runtime\Strategy\SmartRendererStrategyRegistry;

final readonly class SmartRenderer
{
    public function __construct(
        private TrustedTemplateRegistry $templates = new TrustedTemplateRegistry,
        private SmartContextAdapterRegistry $adapters = new SmartContextAdapterRegistry([
            new GenericPropsContextAdapter,
            new BrandingContextAdapter,
            new NavigationContextAdapter,
            new OutlineContextAdapter,
            new PreferencesContextAdapter,
        ]),
        private SmartRendererStrategyRegistry $strategies = new SmartRendererStrategyRegistry([
            new RegisteredTemplateStrategy('server-static'),
            new RegisteredTemplateStrategy('server-first-hydratable'),
            new RegisteredTemplateStrategy('client-owned'),
            new RegisteredTemplateStrategy('shadow-dom-owned'),
        ]),
    ) {}

    public function render(ResolvedSmartPlan $plan): RenderArtifact
    {
        $invocation = SmartInvocation::fromPlan($plan);
        $view = $this->adapters->get($invocation->adapter)->prepare($invocation);
        $context = SmartTemplateContext::forInvocation($invocation, $view);
        $html = $this->strategies->get($invocation->strategy)->render($invocation, $context, $this->templates);

        return new RenderArtifact(
            $html,
            $plan->assets,
            [
                'runtime' => (string) ($plan->provenance['runtime'] ?? 'portable-smart'),
                'smart' => $plan->smart,
                'view' => $plan->view,
                'owner' => (string) ($plan->provenance['provider'] ?? 'unknown'),
                'asset_owner' => $plan->smart,
                'hydration_owner' => $plan->smart,
                'assets' => $plan->assets,
                'render' => is_array($context->manifest['render'] ?? null)
                    ? $context->manifest['render']
                    : [],
                'template_abi' => $invocation->templateAbi,
            ],
            $plan->provenance + [
                'template' => $plan->template,
                'portable_strategy' => $invocation->strategy,
                'input_adapter' => $invocation->adapter,
            ],
        );
    }
}
