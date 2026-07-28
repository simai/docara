<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Plan;

final readonly class ResolvedSmartPlan
{
    /**
     * @param  array<string, mixed>  $props
     * @param  list<string>  $assets
     * @param  array<string, mixed>  $provenance
     */
    public function __construct(
        public string $nodeId,
        public string $smart,
        public string $view,
        public string $template,
        public array $props,
        public array $assets,
        public array $provenance,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'node_id' => $this->nodeId,
            'smart' => $this->smart,
            'view' => $this->view,
            'template' => $this->template,
            'props' => $this->props,
            'assets' => $this->assets,
            'provenance' => $this->provenance,
        ];
    }
}
