<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

final readonly class ContainerNode implements DocumentNode
{
    /**
     * @param  array<string,string>  $props
     * @param  list<DocumentNode>  $childNodes
     */
    public function __construct(
        public string $alias,
        public string $component,
        public string $renderer,
        public array $props,
        private string $source,
        private SourceLocation $sourceLocation,
        private array $childNodes,
    ) {
        if ($alias === '' || $component === '' || $renderer === '' || $childNodes === []) {
            throw new \InvalidArgumentException('DOCUMENT_IR_CONTAINER_NODE_INVALID');
        }
    }

    public function type(): string
    {
        return 'container';
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

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'alias' => $this->alias,
            'component' => $this->component,
            'renderer' => $this->renderer,
            'props' => $this->props,
            'source' => $this->sourceLocation->toArray(),
            'children' => array_map(static fn (DocumentNode $node): array => $node->toArray(), $this->childNodes),
        ];
    }
}
