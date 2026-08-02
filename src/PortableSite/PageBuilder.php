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
        $document = $this->compiler->compile($plan->markdown, $plan->page);
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

        $contentHtml = $outline['html'];
        $frameworkCalls = [];
        foreach ($componentArtifacts as $artifact) {
            if (($artifact->hydration['runtime'] ?? null) !== 'simai-framework') {
                continue;
            }
            $frameworkCalls[] = [
                'schema' => 'docara.component_call.v1',
                'id' => (string) $artifact->hydration['smart'],
                'props' => $artifact->hydration['props'] ?? [],
                'ordinal' => (int) ($artifact->hydration['ordinal'] ?? 0),
                'line' => (int) ($artifact->hydration['source']['line'] ?? 0),
                'node_id' => (string) ($artifact->hydration['node_id'] ?? ''),
                'html_sha256' => hash('sha256', $artifact->html),
                'manifest_version' => (string) ($artifact->provenance['manifest_version'] ?? ''),
                'provider' => (string) ($artifact->provenance['provider'] ?? ''),
                'provider_revision' => (string) ($artifact->provenance['provider_revision'] ?? ''),
            ];
        }
        $framework = $runtime->recordGatewayCalls($plan->markdown, $frameworkCalls);
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
