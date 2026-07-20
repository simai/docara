<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class PageViewModel
{
    /** @param array{header: string, sidebar: string, main: string, outline: string, footer: string} $regions */
    public function __construct(
        public string $pageKey,
        public string $title,
        public array $regions,
    ) {}
}
