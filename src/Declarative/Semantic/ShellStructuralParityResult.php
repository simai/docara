<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Semantic;

final readonly class ShellStructuralParityResult
{
    /** @param array<string, mixed> $expected @param array<string, mixed> $actual */
    public function __construct(
        public bool $passed,
        public array $expected,
        public array $actual,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'status' => $this->passed ? 'pass' : 'fail',
            'expected_hash' => hash('sha256', json_encode($this->expected, JSON_THROW_ON_ERROR)),
            'actual_hash' => hash('sha256', json_encode($this->actual, JSON_THROW_ON_ERROR)),
            'expected' => $this->expected,
            'actual' => $this->actual,
        ];
    }
}
