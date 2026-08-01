<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

interface DocumentNode
{
    public function type(): string;

    public function raw(): string;

    public function location(): SourceLocation;

    /** @return list<DocumentNode> */
    public function children(): array;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
