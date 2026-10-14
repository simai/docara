<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\Document;

use Simai\Docara\Declarative\Plan\ResolvedBlockPlan;
use Simai\Docara\Declarative\Rendering\RenderArtifact;

interface DocumentNodeRenderer
{
    public function renderer(): string;

    public function render(ResolvedBlockPlan $node): RenderArtifact;
}
