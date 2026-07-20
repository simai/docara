<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class DeclarativeExampleSourceViewModel
{
    public function __construct(
        public string $label,
        public string $path,
        public string $language,
        public string $code,
    ) {}
}
