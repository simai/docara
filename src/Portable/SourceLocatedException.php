<?php

declare(strict_types=1);

namespace Simai\Docara\Portable;

interface SourceLocatedException
{
    public function sourcePath(): string;

    public function sourcePointer(): string;

    public function sourceLine(): int;

    public function sourceColumn(): int;
}
