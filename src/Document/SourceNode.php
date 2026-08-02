<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

final readonly class SourceNode implements DocumentNode
{
    /** @param array<string, mixed> $data @param list<DocumentNode> $children */
    public function __construct(
        private string $nodeType,
        private string $source,
        private SourceLocation $sourceLocation,
        public array $data = [],
        private array $childNodes = [],
    ) {
        if (! in_array($nodeType, ['heading', 'paragraph', 'list', 'blockquote', 'image', 'table', 'code_block', 'example', 'typed_directive', 'html_comment'], true)) {
            throw new \InvalidArgumentException('DOCUMENT_IR_SOURCE_NODE_TYPE_INVALID');
        }
    }

    public function type(): string
    {
        return $this->nodeType;
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
            'type' => $this->nodeType,
            'source' => $this->sourceLocation->toArray(),
            'data' => $this->data,
            'children' => array_map(static fn (DocumentNode $node): array => $node->toArray(), $this->childNodes),
        ];
    }
}
