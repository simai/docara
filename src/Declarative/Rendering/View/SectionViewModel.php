<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class SectionViewModel
{
    public function __construct(
        public string $id,
        public string $section,
        public string $region,
        public string $content,
    ) {}
}
