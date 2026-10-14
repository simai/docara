<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Layout;

final readonly class LayoutRegion
{
    /** @param list<string> $sectionTypes @param list<string> $capabilities */
    public function __construct(
        public string $key,
        public bool $required,
        public bool $enabled,
        public array $sectionTypes,
        public array $capabilities,
    ) {
        if ($key === '' || $sectionTypes === [] || $capabilities === [] || ($required && ! $enabled)) {
            throw new \InvalidArgumentException('LAYOUT_REGION_INVALID');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'required' => $this->required,
            'enabled' => $this->enabled,
            'section_types' => $this->sectionTypes,
        ];
    }
}
