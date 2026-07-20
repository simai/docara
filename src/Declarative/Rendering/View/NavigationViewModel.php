<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class NavigationViewModel
{
    /** @param list<NavigationItemViewModel> $items */
    public function __construct(
        public array $items,
        public int $maximumDepth,
    ) {}
}
