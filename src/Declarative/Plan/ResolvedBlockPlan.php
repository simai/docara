<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Plan;

final readonly class ResolvedBlockPlan
{
    /** @param array<string, mixed> $data @param array<string, mixed> $provenance */
    public function __construct(
        public string $id,
        public string $block,
        public string $renderer,
        public array $data,
        public ?ResolvedSmartPlan $smart,
        public array $provenance,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'block' => $this->block,
            'renderer' => $this->renderer,
            'data' => $this->data,
            'smart' => $this->smart?->toArray(),
            'provenance' => $this->provenance,
        ];
    }
}
