<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

use Simai\Docara\Declarative\Rendering\RenderArtifact;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;

final readonly class ComponentDocumentNodeRenderer implements DocumentNodeRenderer
{
    public function __construct(private SmartComponentGateway $components) {}

    public function types(): array
    {
        return ['component'];
    }

    public function render(DocumentNode $node, DocumentRenderContext $context): RenderArtifact
    {
        if (! $node instanceof ComponentNode) {
            throw new \LogicException('DOCUMENT_COMPONENT_RENDERER_NODE_INVALID');
        }

        return $this->components->renderComponentContract($node);
    }
}
