<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\Document;

use Simai\Docara\Declarative\Plan\ResolvedBlockPlan;
use Simai\Docara\Declarative\Rendering\RenderArtifact;
use Simai\Docara\Declarative\Rendering\SafeElementRenderer;

final readonly class ElementDocumentNodeRenderer implements DocumentNodeRenderer
{
    public function __construct(private SafeElementRenderer $elements) {}

    public function renderer(): string
    {
        return 'block.element';
    }

    public function render(ResolvedBlockPlan $node): RenderArtifact
    {
        $element = $node->data['element'] ?? null;
        if (! is_array($element)) {
            throw new \InvalidArgumentException('DECLARATIVE_DOCUMENT_ELEMENT_INVALID');
        }

        return new RenderArtifact(
            $this->elements->render($element),
            [],
            ['runtime' => 'docara.safe_element.v1'],
            $node->provenance + [
                'block' => $node->block,
                'source' => $node->data['source'] ?? '@document',
            ],
        );
    }
}
