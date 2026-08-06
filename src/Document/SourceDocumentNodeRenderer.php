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
        return ['heading', 'paragraph', 'list', 'blockquote', 'image', 'table', 'code_block', 'example', 'typed_directive', 'html_comment'];
    }

    public function render(DocumentNode $node, DocumentRenderContext $context): RenderArtifact
    {
        $html = $node->type() === 'html_comment'
            ? "\n"
            : $this->markdown->renderAt(
                $node->raw(),
                $context->sourceRoot,
                $context->sourceFile,
                $node->location(),
            );
        if ($html === '') {
            $html = "\n";
        }

        return new RenderArtifact(
            $html,
            [],
            ['renderer' => 'markdown', 'node_type' => $node->type()],
            ['source' => $node->location()->toArray()],
        );
    }
}
