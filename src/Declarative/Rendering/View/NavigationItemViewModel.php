<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class NavigationItemViewModel
{
    /** @param list<NavigationItemViewModel> $children */
    public function __construct(
        public string $key,
        public string $title,
        public ?string $url,
        public int $depth,
        public string $indentationClass,
        public bool $active,
        public bool $activeAncestor,
        public bool $currentSection,
        public bool $open,
        public bool $hasChildren,
        public array $children = [],
    ) {}
}
