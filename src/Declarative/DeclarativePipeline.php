<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative;

use Simai\Docara\Declarative\Composition\PageCompositionContext;
use Simai\Docara\Declarative\Document\DocumentParser;
use Simai\Docara\Declarative\Rendering\DeclarativePageRenderer;
use Simai\Docara\Declarative\Rendering\RenderArtifact;
use Simai\Docara\Declarative\Rendering\SmartRenderer;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;
use Simai\Docara\Document\DocumentIr;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\Smart\SmartRegistry;

final readonly class DeclarativePipeline
{
    public function __construct(
        private DocumentParser $parser,
        private DeclarativePageCompiler $compiler,
        private DeclarativePageRenderer $renderer,
    ) {}

    /**
     * @param  array<string, mixed>  $frameworkLock
     * @param  list<string>  $reservedDocumentIds
     */
    public static function bundled(
        array $frameworkLock,
        PortableMarkdownRenderer $markdown,
        array $reservedDocumentIds = [],
        ?SmartRegistry $smarts = null,
        ?SmartComponentGateway $gateway = null,
        ?SmartRenderer $smartRenderer = null,
    ): self {
        return new self(
            new DocumentParser($smarts ?? SmartRegistry::bundled()),
            DeclarativePageCompiler::bundled($frameworkLock, $smarts, $gateway),
            new DeclarativePageRenderer(
                $markdown,
                $smartRenderer ?? new SmartRenderer,
                reservedDocumentIds: $reservedDocumentIds,
            ),
        );
    }

    public function build(
        string $markdown,
        string $source,
        string $pageKey,
        string $title,
        int $outlineDepth,
        ?PageCompositionContext $composition = null,
        array $layoutConfiguration = [],
        array $configurationProvenance = [],
    ): DeclarativePageResult {
        $document = $this->parser->parse($markdown, $source);
        $plan = $this->compiler->compile(
            $document,
            $pageKey,
            $title,
            $outlineDepth,
            $composition,
            $layoutConfiguration,
            $configurationProvenance,
        );

        return new DeclarativePageResult($plan, $this->renderer->render($plan));
    }

    public function compose(
        DocumentIr $document,
        string $pageKey,
        string $title,
        int $outlineDepth,
        RenderArtifact $mainDocument,
        ?PageCompositionContext $composition = null,
        array $layoutConfiguration = [],
        array $configurationProvenance = [],
    ): DeclarativePageResult {
        $plan = $this->compiler->compile(
            $document,
            $pageKey,
            $title,
            $outlineDepth,
            $composition,
            $layoutConfiguration,
            $configurationProvenance,
        );

        return new DeclarativePageResult(
            $plan,
            $this->renderer->render($plan, $mainDocument),
        );
    }
}
