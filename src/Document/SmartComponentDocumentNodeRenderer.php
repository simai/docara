<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Declarative\Document\SourceSpan;
use Simai\Docara\Declarative\Rendering\RenderArtifact;
use Simai\Docara\Declarative\Rendering\SmartRenderer;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;

final readonly class SmartComponentDocumentNodeRenderer implements DocumentNodeRenderer
{
    public function __construct(
        private SmartComponentGateway $gateway,
        private SmartRenderer $renderer,
    ) {}

    public function types(): array
    {
        return ['smart_component'];
    }

    public function render(DocumentNode $node, DocumentRenderContext $context): RenderArtifact
    {
        if (! $node instanceof SmartComponentNode) {
            throw new \LogicException('DOCUMENT_SMART_COMPONENT_NODE_INVALID');
        }
        $location = $node->location();

        return $this->renderer->render($this->gateway->resolve(new SmartCallNode(
            'smart-' . substr(hash('sha256', $location->label() . "\0" . $node->smart), 0, 20),
            $node->smart,
            $node->view,
            $node->props,
            1,
            new SourceSpan($location->file, $location->line, $location->endLine),
        )));
    }
}
