<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Smart;

use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;

final readonly class FrameworkProviderPlanResolver implements ProviderSmartPlanResolver
{
    public function __construct(private SmartPlanResolver $resolver) {}

    public function providerId(): string
    {
        return 'framework.lock';
    }

    public function resolve(SmartCallNode $call): ResolvedSmartPlan
    {
        return $this->resolver->resolve($call);
    }
}
