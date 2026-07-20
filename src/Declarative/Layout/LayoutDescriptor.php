<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Layout;

final readonly class LayoutDescriptor
{
    /**
     * @param  array<string, LayoutRegion>  $regions
     * @param  list<string>  $assets
     * @param  array<string, mixed>  $provenance
     */
    public function __construct(
        public string $key,
        public string $view,
        public array $viewTree,
        public array $regions,
        public array $assets,
        public array $provenance,
    ) {
        if ($key === '' || $view === '' || $viewTree === [] || array_keys($regions) !== [
            'header',
            'sidebar',
            'main',
            'outline',
            'footer',
        ]) {
            throw new \InvalidArgumentException('LAYOUT_DESCRIPTOR_INVALID');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'view' => $this->view,
            'view_tree' => $this->viewTree,
            'regions' => array_map(
                static fn (LayoutRegion $region): array => $region->toArray(),
                $this->regions,
            ),
            'assets' => $this->assets,
            'provenance' => $this->provenance,
        ];
    }
}
