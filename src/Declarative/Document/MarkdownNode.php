<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Document;

final readonly class MarkdownNode implements DocumentNode
{
    public function __construct(
        private string $nodeId,
        public string $markdown,
        private SourceSpan $sourceSpan,
    ) {
        if ($nodeId === '' || $markdown === '') {
            throw new \InvalidArgumentException('DOCUMENT_MARKDOWN_NODE_INVALID');
        }
    }

    public function id(): string
    {
        return $this->nodeId;
    }

    public function type(): string
    {
        return 'markdown';
    }

    public function span(): SourceSpan
    {
        return $this->sourceSpan;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->nodeId,
            'type' => $this->type(),
            'markdown' => $this->markdown,
            'source' => $this->sourceSpan->toArray(),
        ];
    }
}
