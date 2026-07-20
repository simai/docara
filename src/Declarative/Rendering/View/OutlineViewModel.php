<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class OutlineViewModel
{
    /** @param list<OutlineItemViewModel> $items */
    public function __construct(public array $items) {}
}
