<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Preview\View;

final readonly class PreviewIndexItemViewModel
{
    public function __construct(
        public string $title,
        public string $legacyUrl,
        public ?string $previewUrl,
        public string $status,
        public string $unsupportedLabel,
    ) {}
}
