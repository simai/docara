<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Declarative\Rendering\RenderArtifact;
use Simai\Docara\Document\DocumentIr;
use Simai\Docara\Framework\ComponentDirectiveDocument;

final readonly class PageBuilderResult
{
    /**
     * @param  array{html:string,items:list<array<string,mixed>>}  $outline
     * @param  list<RenderArtifact>  $componentArtifacts
     */
    public function __construct(
        public string $contentHtml,
        public RenderArtifact $documentArtifact,
        public array $outline,
        public ComponentDirectiveDocument $frameworkComponents,
        public DocumentIr $document,
        public array $componentArtifacts,
    ) {}
}
