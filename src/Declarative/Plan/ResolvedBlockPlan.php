<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Plan;

final readonly class ResolvedBlockPlan
{
    /** @param array<string, mixed> $data @param array<string, mixed> $provenance */
    public function __construct(
        public string $id,
        public string $block,
        public string $slot,
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
            'slot' => $this->slot,
            'renderer' => $this->renderer,
            'data' => $this->data,
            'smart' => $this->smart?->toArray(),
            'provenance' => $this->provenance,
        ];
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        foreach (['id', 'block', 'slot', 'renderer'] as $key) {
            if (! is_string($payload[$key] ?? null) || $payload[$key] === '') {
                throw new \InvalidArgumentException('RESOLVED_BLOCK_PLAN_INVALID');
            }
        }
        if (! is_array($payload['data'] ?? null) || ! is_array($payload['provenance'] ?? null)) {
            throw new \InvalidArgumentException('RESOLVED_BLOCK_PLAN_INVALID');
        }
        $smart = $payload['smart'] ?? null;
        if ($smart !== null && ! is_array($smart)) {
            throw new \InvalidArgumentException('RESOLVED_BLOCK_PLAN_INVALID');
        }

        return new self(
            $payload['id'],
            $payload['block'],
            $payload['slot'],
            $payload['renderer'],
            $payload['data'],
            is_array($smart) ? ResolvedSmartPlan::fromArray($smart) : null,
            $payload['provenance'],
        );
    }
}
