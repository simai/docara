<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

final readonly class ComponentBlockNode implements DocumentNode
{
    /**
     * @param  array<string, string>  $props
     * @param  list<DocumentNode>  $childNodes
     */
    public function __construct(
        public string $alias,
        public string $component,
        public array $props,
        private string $source,
        public string $body,
        private SourceLocation $sourceLocation,
        private array $childNodes,
    ) {
        if ($alias === '' || $component === '' || trim($body) === '' || $childNodes === []) {
            throw new \InvalidArgumentException('DOCUMENT_IR_COMPONENT_BLOCK_NODE_INVALID');
        }
    }

    public function type(): string
    {
        return 'component_block';
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
        return $this->childNodes;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'alias' => $this->alias,
            'component' => $this->component,
            'props' => $this->props,
            'source' => $this->sourceLocation->toArray(),
            'children' => array_map(static fn (DocumentNode $node): array => $node->toArray(), $this->childNodes),
        ];
    }
}
