<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Preview\View;

final readonly class PreviewPageViewModel
{
    public function __construct(
        public string $locale,
        public string $documentationVersion,
        public string $title,
        public string $pageTitle,
        public string $legacyUrl,
        public string $catalogUrl,
        public string $headHtml,
        public string $contentHtml,
        /** @var array<string, string> */
        public array $copy,
    ) {}
}
