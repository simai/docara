<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Layout;

final readonly class LayoutRegion
{
    /** @param list<string> $sectionTypes */
    public function __construct(
        public string $key,
        public bool $required,
        public bool $enabled,
        public array $sectionTypes,
    ) {
        if ($key === '' || $sectionTypes === [] || ($required && ! $enabled)) {
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
