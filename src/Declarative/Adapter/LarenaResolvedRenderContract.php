<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Adapter;

use Simai\Docara\Portable\CanonicalJson;

final readonly class LarenaResolvedRenderContract
{
    /** @param array<string, mixed> $payload @param array<string, mixed> $semantics */
    public function __construct(
        public array $payload,
        public array $semantics,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->payload;
    }

    public function canonicalHash(): string
    {
        return hash('sha256', CanonicalJson::encode($this->payload));
    }
}
