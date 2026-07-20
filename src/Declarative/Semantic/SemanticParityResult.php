<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Semantic;

final readonly class SemanticParityResult
{
    /** @param array<string, mixed> $legacy @param array<string, mixed> $declarative */
    public function __construct(
        public bool $passed,
        public array $legacy,
        public array $declarative,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'status' => $this->passed ? 'pass' : 'fail',
            'legacy_hash' => hash('sha256', json_encode($this->legacy, JSON_THROW_ON_ERROR)),
            'declarative_hash' => hash('sha256', json_encode($this->declarative, JSON_THROW_ON_ERROR)),
            'legacy' => $this->legacy,
            'declarative' => $this->declarative,
        ];
    }
}
