<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Document;

final readonly class SmartCallNode implements DocumentNode
{
    /** @param array<string, mixed> $props */
    public function __construct(
        private string $nodeId,
        public string $smart,
        public string $view,
        public array $props,
        public int $ordinal,
        private SourceSpan $sourceSpan,
    ) {
        if ($nodeId === ''
            || preg_match('/^ui(?:\.[a-z][a-z0-9_]*)+$/D', $smart) !== 1
            || $view === ''
            || $ordinal < 1
        ) {
            throw new \InvalidArgumentException('DOCUMENT_SMART_CALL_NODE_INVALID');
        }
    }

    public function id(): string
    {
        return $this->nodeId;
    }

    public function type(): string
    {
        return 'smart';
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
            'smart' => $this->smart,
            'view' => $this->view,
            'props' => $this->props,
            'ordinal' => $this->ordinal,
            'source' => $this->sourceSpan->toArray(),
        ];
    }
}
