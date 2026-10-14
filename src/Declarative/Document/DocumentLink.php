<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Document;

final readonly class DocumentLink
{
    public function __construct(
        public string $destination,
        public string $label,
        public SourceSpan $source,
    ) {
        if ($destination === '') {
            throw new \InvalidArgumentException('DOCUMENT_LINK_INVALID');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'destination' => $this->destination,
            'label' => $this->label,
            'source' => $this->source->toArray(),
        ];
    }
}
