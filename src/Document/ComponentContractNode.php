<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

interface ComponentContractNode extends DocumentNode
{
    public function alias(): string;

    public function component(): string;

    /** @return array<string, string> */
    public function props(): array;
}
