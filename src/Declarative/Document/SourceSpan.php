<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Document;

final readonly class SourceSpan
{
    public function __construct(
        public string $source,
        public int $startLine,
        public int $endLine,
    ) {
        if ($source === '' || $startLine < 1 || $endLine < $startLine) {
            throw new \InvalidArgumentException('DOCUMENT_SOURCE_SPAN_INVALID');
        }
    }

    /** @return array{source: string, start_line: int, end_line: int} */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'start_line' => $this->startLine,
            'end_line' => $this->endLine,
        ];
    }
}
