<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Declarative\Rendering\RenderArtifact;
use Simai\Docara\Declarative\Rendering\SmartRenderer;
use Simai\Docara\Document\DocumentRenderContext;
use Simai\Docara\Document\DocumentRendererRegistry;
use Simai\Docara\Document\MarkdownCompiler;
use Simai\Docara\Framework\FrameworkComponentRuntime;
use Simai\Docara\Portable\ResolvedPagePlan;

final readonly class PageBuilder
{
    private DocumentRendererRegistry $renderers;

    public function __construct(
        private PortableMarkdownRenderer $markdown,
        private MarkdownCompiler $compiler = new MarkdownCompiler,
        ?DocumentRendererRegistry $renderers = null,
        ?SmartRenderer $smartRenderer = null,
    ) {
        $this->renderers = $renderers ?? DocumentRendererRegistry::bundled($markdown, $smartRenderer);
    }

    public function build(
        ResolvedPagePlan $plan,
        string $root,
        FrameworkComponentRuntime $runtime,
        int $tocDepth,
    ): PageBuilderResult {
        $framework = $runtime->extract($plan->markdown, $plan->page);
        $document = $this->compiler->compile($framework->markdownWithPlaceholders, $plan->page);
        $rendered = $this->renderers->render(
            $document,
            new DocumentRenderContext($root, $root . '/' . ltrim($plan->page, '/')),
        );
        $renderedMarkdown = $rendered['document']->html;
        $componentArtifacts = $rendered['components'];
        $outline = (new PortableDocumentOutlineBuilder)->build(
            $renderedMarkdown,
            $tocDepth,
            PortableDocumentIds::reserved(),
        );

        $contentHtml = $framework->hydrate($outline['html']);
        $documentArtifact = new RenderArtifact(
            $contentHtml,
            $rendered['document']->assets,
            $rendered['document']->hydration + [
                'document_ir' => $document->toArray(),
                'components' => array_map(
                    static fn (RenderArtifact $artifact): array => $artifact->hydration,
                    $componentArtifacts,
                ),
            ],
            $rendered['document']->provenance + [
                'document_ir_sha256' => hash('sha256', json_encode($document->toArray(), JSON_THROW_ON_ERROR)),
            ],
        );

        return new PageBuilderResult(
            $contentHtml,
            $documentArtifact,
            $outline,
            $framework,
            $document,
            $componentArtifacts,
        );
    }
}
