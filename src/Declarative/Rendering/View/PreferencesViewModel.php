<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class PreferencesViewModel
{
    /**
     * @param  list<array<string, mixed>>  $groups
     * @param  string  $settingsJson  The Framework sf-menu-context entries, safe inside a script element.
     */
    public function __construct(
        public string $position,
        public array $groups,
        public string $title,
        public string $closeLabel,
        public string $resetLabel,
        public string $settingsJson = '{"entries":[]}',
    ) {}
}
