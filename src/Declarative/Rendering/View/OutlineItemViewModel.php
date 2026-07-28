<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class OutlineItemViewModel
{
    public function __construct(
        public string $id,
        public int $level,
        public string $text,
        public string $indentationClass,
    ) {}
}
