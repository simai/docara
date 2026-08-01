<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

use Simai\Docara\Declarative\Rendering\RenderArtifact;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final readonly class SourceDocumentNodeRenderer implements DocumentNodeRenderer
{
    public function __construct(private PortableMarkdownRenderer $markdown) {}

    public function types(): array
    {
        return ['heading', 'paragraph', 'list', 'blockquote', 'image', 'table', 'code_block', 'example'];
    }

    public function render(DocumentNode $node, DocumentRenderContext $context): RenderArtifact
    {
        $html = $this->markdown->render($node->raw(), $context->sourceRoot, $context->sourceFile);

        return new RenderArtifact(
            $html,
            [],
            ['renderer' => 'markdown', 'node_type' => $node->type()],
            ['source' => $node->location()->toArray()],
        );
    }
}
