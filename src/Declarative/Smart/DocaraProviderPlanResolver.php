<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Smart;

use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;

final readonly class DocaraProviderPlanResolver implements ProviderSmartPlanResolver
{
    public function __construct(private CompositeSmartPlanResolver $resolver = new CompositeSmartPlanResolver) {}

    public function providerId(): string
    {
        return 'docara.package';
    }

    public function resolve(SmartCallNode $call): ResolvedSmartPlan
    {
        return $this->resolver->resolve($call->smart, $call->id(), $call->props, $call->view);
    }
}
