<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Preview\View;

final readonly class PreviewIndexViewModel
{
    /** @param list<PreviewIndexItemViewModel> $items */
    public function __construct(
        public string $locale,
        public string $documentationVersion,
        public string $title,
        public string $headHtml,
        public string $receiptUrl,
        public int $renderedCount,
        public int $skippedCount,
        public array $items,
    ) {}
}
