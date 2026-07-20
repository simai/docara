<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative;

use Simai\Docara\Declarative\Document\DocumentParser;
use Simai\Docara\Declarative\Rendering\DeclarativePageRenderer;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

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
    ): self {
        return new self(
            new DocumentParser,
            DeclarativePageCompiler::bundled($frameworkLock),
            new DeclarativePageRenderer(
                $markdown,
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
    ): DeclarativePageResult {
        $document = $this->parser->parse($markdown, $source);
        $plan = $this->compiler->compile($document, $pageKey, $title, $outlineDepth);

        return new DeclarativePageResult($plan, $this->renderer->render($plan));
    }
}
