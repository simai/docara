<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class DeclarativeExampleIndexItemViewModel
{
    public function __construct(
        public string $title,
        public string $description,
        public string $category,
        public string $url,
    ) {}
}
