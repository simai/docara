<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative;

use Simai\Docara\Declarative\Plan\ResolvedRenderPlan;
use Simai\Docara\Declarative\Rendering\RenderArtifact;

final readonly class DeclarativePageResult
{
    public function __construct(
        public ResolvedRenderPlan $plan,
        public RenderArtifact $artifact,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'status' => 'rendered',
            'plan' => $this->plan->toArray(),
            'plan_hash' => $this->plan->canonicalHash(),
            'render_artifact' => $this->artifact->toArray(),
        ];
    }
}
