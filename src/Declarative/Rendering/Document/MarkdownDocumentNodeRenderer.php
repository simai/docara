<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\Document;

use Simai\Docara\Declarative\Plan\ResolvedBlockPlan;
use Simai\Docara\Declarative\Rendering\RenderArtifact;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final readonly class MarkdownDocumentNodeRenderer implements DocumentNodeRenderer
{
    public function __construct(private PortableMarkdownRenderer $markdown) {}

    public function renderer(): string
    {
        return 'block.markdown';
    }

    public function render(ResolvedBlockPlan $node): RenderArtifact
    {
        $markdown = $node->data['markdown'] ?? null;
        if (! is_string($markdown) || $markdown === '') {
            throw new \InvalidArgumentException('DECLARATIVE_DOCUMENT_MARKDOWN_INVALID');
        }

        return new RenderArtifact(
            $this->markdown->render($markdown),
            [],
            [],
            $node->provenance + ['block' => $node->block],
        );
    }
}
