<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Document\Compilation;

use Simai\Docara\Declarative\Document\DocumentNode;
use Simai\Docara\Declarative\Plan\ResolvedBlockPlan;

interface DocumentNodeBlockResolver
{
    public function type(): string;

    /** @param array<string, mixed> $section */
    public function resolve(DocumentNode $node, array $section): ResolvedBlockPlan;
}
