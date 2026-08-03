<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

final readonly class Diagnostic
{
    /** @param array<string, mixed> $provenance */
    public function __construct(
        public string $code,
        public string $severity,
        public string $message,
        public ?string $path = null,
        public ?string $pointer = null,
        public ?int $line = null,
        public ?int $column = null,
        public ?string $owner = null,
        public array $provenance = [],
        public ?string $suggestion = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'severity' => $this->severity,
            'message' => $this->message,
            'source' => $this->path === null ? null : array_filter([
                'path' => $this->path,
                'pointer' => $this->pointer,
                'line' => $this->line,
                'column' => $this->column,
            ], static fn (mixed $value): bool => $value !== null),
            'owner' => $this->owner,
            'provenance' => $this->provenance,
            'suggestion' => $this->suggestion,
        ];
    }
}
