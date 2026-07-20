<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Document;

interface DocumentNode
{
    public function id(): string;

    public function type(): string;

    public function span(): SourceSpan;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
