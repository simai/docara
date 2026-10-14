<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\Document;

use Simai\Docara\Declarative\Plan\ResolvedBlockPlan;
use Simai\Docara\Declarative\Rendering\RenderArtifact;
use Simai\Docara\Declarative\Rendering\SmartRenderer;

final readonly class SmartDocumentNodeRenderer implements DocumentNodeRenderer
{
    public function __construct(private SmartRenderer $smart) {}

    public function renderer(): string
    {
        return 'block.smart';
    }

    public function render(ResolvedBlockPlan $node): RenderArtifact
    {
        if ($node->smart === null) {
            throw new \InvalidArgumentException('DECLARATIVE_DOCUMENT_SMART_INVALID');
        }

        return $this->smart->render($node->smart);
    }
}
