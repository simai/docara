<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class OutlineViewModel
{
    /**
     * @param  list<OutlineItemViewModel>  $items
     * @param  string  $settingsJson  The Framework sf-contents items, safe inside a script element.
     */
    public function __construct(public array $items, public string $label, public string $settingsJson = '{"items":[]}') {}
}
