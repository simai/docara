<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

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
    ) {
        $this->renderers = $renderers ?? DocumentRendererRegistry::bundled($markdown);
    }

    public function build(
        ResolvedPagePlan $plan,
        string $root,
        FrameworkComponentRuntime $runtime,
        int $tocDepth,
    ): PageBuilderResult {
        $framework = $runtime->extract($plan->markdown, $plan->page);
        $document = null;
        $componentArtifacts = [];
        if ($this->usesTargetPipeline($plan->page)) {
            $document = $this->compiler->compile($framework->markdownWithPlaceholders, $plan->page);
            $rendered = $this->renderers->render(
                $document,
                new DocumentRenderContext($root, $root . '/' . ltrim($plan->page, '/')),
            );
            $renderedMarkdown = $rendered['document']->html;
            $componentArtifacts = $rendered['components'];
        } else {
            $renderedMarkdown = $this->markdown->render(
                $framework->markdownWithPlaceholders,
                $root,
                $root . '/' . ltrim($plan->page, '/'),
            );
        }
        $outline = (new PortableDocumentOutlineBuilder)->build(
            $renderedMarkdown,
            $tocDepth,
            PortableDocumentIds::reserved(),
        );

        return new PageBuilderResult(
            $framework->hydrate($outline['html']),
            $outline,
            $framework,
            $document,
            $componentArtifacts,
        );
    }

    private function usesTargetPipeline(string $page): bool
    {
        return str_replace('\\', '/', $page) === 'content/ru/components/badge.md';
    }
}
