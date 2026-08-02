<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

use Simai\Docara\Declarative\Rendering\RenderArtifact;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final readonly class ComponentBlockDocumentNodeRenderer implements DocumentNodeRenderer
{
    public function __construct(
        private SmartComponentGateway $components,
        private PortableMarkdownRenderer $markdown,
    ) {}

    public function types(): array
    {
        return ['component_block'];
    }

    public function render(DocumentNode $node, DocumentRenderContext $context): RenderArtifact
    {
        if (! $node instanceof ComponentBlockNode) {
            throw new \LogicException('DOCUMENT_COMPONENT_BLOCK_RENDERER_NODE_INVALID');
        }

        return $this->components->renderComponentContract(
            $node,
            $this->markdown->render($node->body, $context->sourceRoot, $context->sourceFile),
        );
    }
}
