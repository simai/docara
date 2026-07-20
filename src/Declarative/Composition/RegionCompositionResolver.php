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
                        'id' => 'site-header',
                        'section' => 'docara.header',
                    ]],
                ],
                'sidebar' => [
                    'enabled' => true,
                    'sections' => [[
                        'id' => 'docs-navigation',
                        'section' => 'docara.navigation',
                    ]],
                ],
                'main' => ['enabled' => true, 'sections' => []],
                'outline' => [
                    'enabled' => true,
                    'sections' => [[
                        'id' => 'page-outline',
                        'section' => 'docara.outline',
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
        $this->assertNoExecutableSurface($layout);
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

    /** @param array<string, mixed> $configuration */
    private function assertNoExecutableSurface(array $configuration, string $pointer = '/layout'): void
    {
        foreach ($configuration as $key => $value) {
            if (is_string($key)
                && preg_match('/(?:template|blade|html|callback|callable|php|script|style|path)/i', $key) === 1
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_AUTHOR_EXECUTABLE_SURFACE_FORBIDDEN',
                    "Authored executable or template surface [$pointer/$key] is forbidden.",
                );
            }
            if (is_array($value)) {
                $this->assertNoExecutableSurface($value, $pointer . '/' . $key);
            } elseif (is_string($value)
                && preg_match('/(?:<\\?php|<script\\b|<style\\b|@php\\b|javascript:)/i', $value) === 1
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_AUTHOR_EXECUTABLE_SURFACE_FORBIDDEN',
                    "Authored executable content [$pointer/$key] is forbidden.",
                );
            }
        }
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
        $ids = [];
        foreach ($sections as $section) {
            $id = is_array($section) ? ($section['id'] ?? null) : null;
            $sectionKey = is_array($section) ? ($section['section'] ?? null) : null;
            $expected = [
                'header' => 'docara.header',
                'sidebar' => 'docara.navigation',
                'outline' => 'docara.outline',
            ][$region] ?? null;
            if (! is_string($id)
                || preg_match('/^[a-z][a-z0-9_.-]+$/D', $id) !== 1
                || isset($ids[$id])
                || ! is_string($sectionKey)
                || $sectionKey !== $expected
                || array_keys($section) !== ['id', 'section']
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_REGION_SECTION_INVALID',
                    "Declarative region [$region] contains an invalid section call.",
                );
            }
            $ids[$id] = true;
        }
    }
}
