<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Composition;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class RegionCompositionResolver
{
    private const REGIONS = ['header', 'sidebar', 'main', 'outline', 'footer'];

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'key' => 'docara.docs',
            'regions' => [
                'header' => [
                    'enabled' => true,
                    'sections' => [[
                        'section' => 'docara.shell',
                        'blocks' => [[
                            'block' => 'shell.smart',
                            'smart' => 'docara.header',
                            'bind' => 'branding',
                        ]],
                    ]],
                ],
                'sidebar' => [
                    'enabled' => true,
                    'sections' => [[
                        'section' => 'docara.shell',
                        'blocks' => [[
                            'block' => 'shell.smart',
                            'smart' => 'docara.navigation',
                            'bind' => 'navigation',
                            'props' => ['maximum_depth' => 4],
                        ]],
                    ]],
                ],
                'main' => ['enabled' => true, 'sections' => []],
                'outline' => [
                    'enabled' => true,
                    'sections' => [[
                        'section' => 'docara.shell',
                        'blocks' => [[
                            'block' => 'shell.smart',
                            'smart' => 'docara.outline',
                            'bind' => 'outline',
                        ]],
                    ]],
                ],
                'footer' => ['enabled' => false, 'sections' => []],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $layout
     * @param  array<string, string>  $provenance
     * @return array{
     *   key:string,
     *   regions:array<string, array{enabled:bool,sections:list<array<string,mixed>>}>,
     *   provenance:array<string,string>
     * }
     */
    public function resolve(array $layout, array $provenance = []): array
    {
        $defaults = self::defaults();
        $key = $layout['key'] ?? $defaults['key'];
        $configuredRegions = $layout['regions'] ?? [];
        if ($key !== 'docara.docs' || ! is_array($configuredRegions)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_LAYOUT_CONFIGURATION_INVALID',
                'Declarative layout configuration must select the registered [docara.docs] layout.',
            );
        }

        $regions = [];
        foreach (self::REGIONS as $key) {
            $configured = $configuredRegions[$key] ?? [];
            $region = [
                'enabled' => is_array($configured) && array_key_exists('enabled', $configured)
                    ? $configured['enabled']
                    : $defaults['regions'][$key]['enabled'],
                'sections' => is_array($configured) && array_key_exists('sections', $configured)
                    ? $configured['sections']
                    : $defaults['regions'][$key]['sections'],
            ];
            if (! is_array($region)
                || ! is_bool($region['enabled'] ?? null)
                || ! is_array($region['sections'] ?? null)
                || ! array_is_list($region['sections'])
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_REGION_CONFIGURATION_INVALID',
                    "Declarative region [$key] is invalid.",
                );
            }
            $this->assertSections($key, $region['sections']);
            $regions[$key] = [
                'enabled' => $region['enabled'],
                'sections' => $region['sections'],
            ];
        }

        return [
            'key' => 'docara.docs',
            'regions' => $regions,
            'provenance' => array_filter(
                $provenance,
                static fn (string $source, string $pointer): bool => str_starts_with($pointer, '/layout'),
                ARRAY_FILTER_USE_BOTH,
            ),
        ];
    }

    /** @param list<array<string, mixed>> $sections */
    private function assertSections(string $region, array $sections): void
    {
        if ($region === 'main' && $sections !== []) {
            throw new PortableConfigurationException(
                'DECLARATIVE_MAIN_SECTIONS_MANAGED',
                'The main region is populated from the authored Markdown document and cannot declare shell sections.',
            );
        }
        foreach ($sections as $section) {
            if (! is_array($section)
                || ($section['section'] ?? null) !== 'docara.shell'
                || ! is_array($section['blocks'] ?? null)
                || ! array_is_list($section['blocks'])
                || $section['blocks'] === []
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_REGION_SECTION_INVALID',
                    "Declarative region [$region] contains an invalid section call.",
                );
            }
            foreach ($section['blocks'] as $block) {
                $this->assertBlock($region, $block);
            }
        }
    }

    private function assertBlock(string $region, mixed $block): void
    {
        if (! is_array($block) || ($block['block'] ?? null) !== 'shell.smart') {
            throw new PortableConfigurationException(
                'DECLARATIVE_REGION_BLOCK_INVALID',
                "Declarative region [$region] contains an invalid block call.",
            );
        }
        $smart = $block['smart'] ?? null;
        $bind = $block['bind'] ?? null;
        $expected = [
            'docara.header' => ['region' => 'header', 'bind' => 'branding'],
            'docara.navigation' => ['region' => 'sidebar', 'bind' => 'navigation'],
            'docara.outline' => ['region' => 'outline', 'bind' => 'outline'],
        ][$smart] ?? null;
        if ($expected === null || $expected['region'] !== $region || $expected['bind'] !== $bind) {
            throw new PortableConfigurationException(
                'DECLARATIVE_REGION_BINDING_FORBIDDEN',
                "Smart component [$smart] cannot bind [$bind] in region [$region].",
            );
        }
        $props = $block['props'] ?? [];
        if (! is_array($props)
            || ($smart !== 'docara.navigation' && $props !== [])
            || ($smart === 'docara.navigation' && ($props['maximum_depth'] ?? 4) !== 4)
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_REGION_PROPS_INVALID',
                "Smart component [$smart] contains unsupported authored props.",
            );
        }
    }
}
