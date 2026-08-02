<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Runtime;

use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;

final readonly class SmartInvocation
{
    /** @param array<string, mixed> $props @param array<string, mixed> $provenance */
    public function __construct(
        public string $id,
        public string $smart,
        public string $view,
        public ?string $preset,
        public array $props,
        public string $template,
        public string $strategy,
        public string $adapter,
        public array $provenance,
    ) {}

    public static function fromPlan(ResolvedSmartPlan $plan): self
    {
        $strategy = $plan->provenance['portable_strategy'] ?? 'server-static';
        $adapter = $plan->provenance['input_adapter'] ?? 'smart.props';
        if (! is_string($strategy) || ! is_string($adapter)) {
            throw new \InvalidArgumentException('SMART_INVOCATION_INVALID');
        }

        return new self(
            $plan->nodeId,
            $plan->smart,
            $plan->view,
            is_string($plan->provenance['preset'] ?? null) ? $plan->provenance['preset'] : null,
            $plan->props,
            $plan->template,
            $strategy,
            $adapter,
            $plan->provenance,
        );
    }
}
