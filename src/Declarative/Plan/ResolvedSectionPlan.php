<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Plan;

final readonly class ResolvedSectionPlan
{
    /** @param list<ResolvedBlockPlan> $blocks @param array<string, mixed> $provenance */
    public function __construct(
        public string $id,
        public string $section,
        public string $type,
        public string $region,
        public array $blocks,
        public array $provenance,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'section' => $this->section,
            'type' => $this->type,
            'region' => $this->region,
            'blocks' => array_map(
                static fn (ResolvedBlockPlan $block): array => $block->toArray(),
                $this->blocks,
            ),
            'provenance' => $this->provenance,
        ];
    }
}
