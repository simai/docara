<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

final readonly class PortableSearchPlan
{
    /** @param array<string, mixed> $index */
    public function __construct(
        public array $index,
        public string $indexJson,
        public string $runtime,
        public string $contentHash,
        public string $runtimeHash,
        public string $indexUrl,
        public string $runtimeUrl,
    ) {}
}
