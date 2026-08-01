<?php

declare(strict_types=1);

namespace Simai\Docara\Content;

final readonly class PageSource
{
    public function __construct(
        public string $locale,
        public string $path,
        public string $route,
    ) {}
}
