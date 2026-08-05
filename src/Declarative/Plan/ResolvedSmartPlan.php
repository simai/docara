<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Plan;

final readonly class ResolvedSmartPlan
{
    /**
     * @param  array<string, mixed>  $props
     * @param  list<string>  $assets
     * @param  array<string, mixed>  $provenance
     * @param  list<self>  $children
     */
    public function __construct(
        public string $nodeId,
        public string $smart,
        public string $view,
        public string $template,
        public array $props,
        public array $assets,
        public array $provenance,
        public array $children = [],
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
            'children' => array_map(static fn (self $child): array => $child->toArray(), $this->children),
        ];
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        foreach (['node_id', 'smart', 'view', 'template'] as $key) {
            if (! is_string($payload[$key] ?? null) || $payload[$key] === '') {
                throw new \InvalidArgumentException('RESOLVED_SMART_PLAN_INVALID');
            }
        }
        if (! is_array($payload['props'] ?? null)
            || ! is_array($payload['assets'] ?? null)
            || ! is_array($payload['provenance'] ?? null)
            || ! is_array($payload['children'] ?? [])
            || array_filter($payload['assets'], static fn (mixed $asset): bool => ! is_string($asset)) !== []
        ) {
            throw new \InvalidArgumentException('RESOLVED_SMART_PLAN_INVALID');
        }

        return new self(
            $payload['node_id'],
            $payload['smart'],
            $payload['view'],
            $payload['template'],
            $payload['props'],
            array_values($payload['assets']),
            $payload['provenance'],
            array_map(
                static fn (mixed $child): self => is_array($child)
                    ? self::fromArray($child)
                    : throw new \InvalidArgumentException('RESOLVED_SMART_PLAN_INVALID'),
                $payload['children'] ?? [],
            ),
        );
    }
}
