<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Document;

final readonly class DocumentHeading
{
    public function __construct(
        public string $id,
        public int $level,
        public string $text,
        public SourceSpan $source,
    ) {
        if ($id === '' || $level < 1 || $level > 6 || trim($text) === '') {
            throw new \InvalidArgumentException('DOCUMENT_HEADING_INVALID');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'level' => $this->level,
            'text' => $this->text,
            'source' => $this->source->toArray(),
        ];
    }
}
