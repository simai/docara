<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

use Simai\Docara\Declarative\Rendering\RenderArtifact;

interface DocumentNodeRenderer
{
    /** @return list<string> */
    public function types(): array;

    public function render(DocumentNode $node, DocumentRenderContext $context): RenderArtifact;
}
