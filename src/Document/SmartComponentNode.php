<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

final readonly class SmartComponentNode implements DocumentNode
{
    /** @param array<string,mixed> $props */
    public function __construct(
        public string $smart,
        public string $view,
        public array $props,
        public int $ordinal,
        private string $source,
        private SourceLocation $sourceLocation,
    ) {
        if ($ordinal < 1) {
            throw new \InvalidArgumentException('DOCUMENT_SMART_COMPONENT_ORDINAL_INVALID');
        }
    }

    public function type(): string
    {
        return 'smart_component';
    }

    public function raw(): string
    {
        return $this->source;
    }

    public function location(): SourceLocation
    {
        return $this->sourceLocation;
    }

    public function children(): array
    {
        return [];
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'smart' => $this->smart,
            'view' => $this->view,
            'props' => $this->props,
            'ordinal' => $this->ordinal,
            'source' => $this->sourceLocation->toArray(),
        ];
    }
}
