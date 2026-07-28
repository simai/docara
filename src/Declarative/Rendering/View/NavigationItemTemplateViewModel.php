<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class NavigationItemTemplateViewModel
{
    public function __construct(
        public NavigationItemViewModel $item,
        public string $childrenHtml,
        public ?string $activeRole,
        public string $weightClass,
        public int $frameworkLevel,
        public string $expandLabel,
        public string $collapseLabel,
        public string $containsCurrentLabel,
    ) {}
}
