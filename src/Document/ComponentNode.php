<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

final readonly class ComponentNode implements DocumentNode
{
    /** @param array<string, string> $props */
    public function __construct(
        public string $alias,
        public string $component,
        public string $label,
        public array $props,
        private string $source,
        private SourceLocation $sourceLocation,
    ) {
        if ($alias === '' || $component === '' || trim($label) === '') {
            throw new \InvalidArgumentException('DOCUMENT_IR_COMPONENT_NODE_INVALID');
        }
    }

    public function type(): string
    {
        return 'component';
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
            'alias' => $this->alias,
            'component' => $this->component,
            'label' => $this->label,
            'props' => $this->props,
            'source' => $this->sourceLocation->toArray(),
        ];
    }
}
