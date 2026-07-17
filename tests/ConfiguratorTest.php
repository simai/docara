<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Configurator;

class ConfiguratorTest extends TestCase
{
    #[Test]
    public function it_allows_intermediate_settings_nodes_without_current_metadata(): void
    {
        $configurator = $this->app->make(Configurator::class);

        $items = [
            'en' => [
                'current' => [
                    'title' => 'Docs',
                    'has_index' => false,
                    'showInMenu' => true,
                    'menu' => [
                        'reference' => 'Reference',
                    ],
                ],
                'pages' => [
                    'reference' => [
                        'pages' => [
                            'color-primitives' => [
                                'current' => [
                                    'title' => 'Color primitives',
                                    'has_index' => true,
                                    'showInMenu' => true,
                                    'menu' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $tree = $configurator->buildMenuTree($items, '', 'en');

        static::assertArrayHasKey('/en/reference', $tree['/en']['children']);
        static::assertNull($tree['/en']['children']['/en/reference']['path']);
        static::assertArrayHasKey('/en/reference/color-primitives', $tree['/en']['children']['/en/reference']['children']);
    }
}
