<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class DeclarativeExampleIndexViewModel
{
    /** @param list<DeclarativeExampleIndexItemViewModel> $items */
    public function __construct(
        public string $title,
        public string $intro,
        public array $items,
    ) {}
}
