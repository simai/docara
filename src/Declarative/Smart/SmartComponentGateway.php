<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Smart;

use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class SmartComponentGateway
{
    public function __construct(
        private SmartPlanResolver $framework,
        private CompositeSmartPlanResolver $product = new CompositeSmartPlanResolver,
    ) {}

    /** @param array<string, mixed> $frameworkLock */
    public static function bundled(array $frameworkLock): self
    {
        return new self(SmartPlanResolver::fromLock($frameworkLock));
    }

    public function resolve(SmartCallNode $call): ResolvedSmartPlan
    {
        if (str_starts_with($call->smart, 'ui.')) {
            return $this->framework->resolve($call);
        }
        if (str_starts_with($call->smart, 'docara.')) {
            return $this->product->resolve(
                $call->smart,
                $call->id(),
                $call->props,
                $call->view,
            );
        }

        throw new PortableConfigurationException(
            'DECLARATIVE_SMART_NAMESPACE_UNSUPPORTED',
            "Smart component namespace [{$call->smart}] is unsupported.",
        );
    }
}
