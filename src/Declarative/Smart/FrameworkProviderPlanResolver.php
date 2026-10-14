<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Smart;

use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;
use Simai\Docara\Smart\SmartRegistry;

final readonly class FrameworkProviderPlanResolver implements ProviderSmartPlanResolver
{
    private PortableProviderPlanResolver $portable;

    public function __construct(
        private SmartPlanResolver $legacy,
        private SmartRegistry $smarts,
    ) {
        $this->portable = new PortableProviderPlanResolver($this->providerId(), $this->smarts);
    }

    public function providerId(): string
    {
        return 'framework.lock';
    }

    public function resolve(SmartCallNode $call): ResolvedSmartPlan
    {
        $definition = $this->smarts->definition($call->smart);

        return ($definition->provenance['provider_adapter'] ?? null) === 'portable.manifest.direct'
            ? $this->portable->resolve($call)
            : $this->legacy->resolve($call);
    }
}
