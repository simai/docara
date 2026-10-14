<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Composition;

use Simai\Docara\Declarative\Definition\DefinitionRepository;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class RegionCompositionResolver
{
    public function __construct(private DefinitionRepository $definitions = new DefinitionRepository) {}

    /** @return array<string, mixed> */
    public static function defaults(?DefinitionRepository $definitions = null): array
    {
        $definitions ??= new DefinitionRepository;
        $layout = $definitions->defaultLayout();

        return self::configurationFromDefinition($layout);
    }

    /** @return array<string, mixed> */
    public static function defaultsFor(string $layout, DefinitionRepository $definitions): array
    {
        return self::configurationFromDefinition($definitions->layout($layout));
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
        $default = $this->definitions->defaultLayout();
        $key = $layout['key'] ?? $default['key'];
        if (! is_string($key)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_LAYOUT_CONFIGURATION_INVALID',
                'Declarative layout configuration must select a registered layout.',
            );
        }
        $definition = $this->definitions->layout($key);
        $defaults = self::configurationFromDefinition($definition);
        $configuredRegions = $layout['regions'] ?? [];
        if (! is_array($configuredRegions)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_LAYOUT_CONFIGURATION_INVALID',
                "Declarative layout [$key] regions must be an object.",
            );
        }
        $unknownRegions = array_diff(array_keys($configuredRegions), array_keys($definition['regions']));
        if ($unknownRegions !== []) {
            throw new PortableConfigurationException(
                'DECLARATIVE_REGION_UNKNOWN',
                'Declarative layout contains an unknown region [' . (string) reset($unknownRegions) . '].',
            );
        }

        $documentRegion = (string) $definition['document']['region'];
        $regions = [];
        foreach ($definition['regions'] as $regionKey => $regionDefinition) {
            $configured = $configuredRegions[$regionKey] ?? [];
            if (! is_array($configured)) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_REGION_CONFIGURATION_INVALID',
                    "Declarative region [$regionKey] is invalid.",
                );
            }
            $region = [
                'enabled' => array_key_exists('enabled', $configured)
                    ? $configured['enabled']
                    : $defaults['regions'][$regionKey]['enabled'],
                'sections' => array_key_exists('sections', $configured)
                    ? $configured['sections']
                    : $defaults['regions'][$regionKey]['sections'],
            ];
            if (! is_bool($region['enabled']) || ! is_array($region['sections']) || ! array_is_list($region['sections'])) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_REGION_CONFIGURATION_INVALID',
                    "Declarative region [$regionKey] is invalid.",
                );
            }
            if (($regionDefinition['required'] ?? false) === true && $region['enabled'] !== true) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_REQUIRED_REGION_DISABLED',
                    "Required region [$regionKey] cannot be disabled.",
                );
            }
            $this->assertSections(
                (string) $regionKey,
                $region['sections'],
                $regionDefinition,
                $documentRegion,
            );
            $regions[(string) $regionKey] = $region;
        }

        return [
            'key' => $key,
            'regions' => $regions,
            'provenance' => array_filter(
                $provenance,
                static fn (string $source, string $pointer): bool => str_starts_with($pointer, '/layout'),
                ARRAY_FILTER_USE_BOTH,
            ),
        ];
    }

    /** @param array<string, mixed> $definition @return array<string, mixed> */
    private static function configurationFromDefinition(array $definition): array
    {
        $regions = [];
        foreach ($definition['regions'] as $key => $region) {
            $regions[(string) $key] = [
                'enabled' => (bool) $region['default_enabled'],
                'sections' => $region['default_sections'],
            ];
        }

        return ['key' => $definition['key'], ...$definition['configuration'], 'regions' => $regions];
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

    /** @param list<array<string, mixed>> $sections @param array<string, mixed> $regionDefinition */
    private function assertSections(string $region, array $sections, array $regionDefinition, string $documentRegion): void
    {
        if ($region === $documentRegion && $sections !== []) {
            throw new PortableConfigurationException(
                'DECLARATIVE_DOCUMENT_REGION_MANAGED',
                "Region [$region] is populated from the authored Markdown document and cannot declare shell sections.",
            );
        }
        if (count($sections) > 8) {
            throw new PortableConfigurationException(
                'DECLARATIVE_REGION_SECTION_LIMIT_EXCEEDED',
                "Declarative region [$region] exceeds the section limit.",
            );
        }
        $ids = [];
        foreach ($sections as $section) {
            $id = is_array($section) ? ($section['id'] ?? null) : null;
            $sectionKey = is_array($section) ? ($section['section'] ?? null) : null;
            $keys = is_array($section) ? array_keys($section) : [];
            $blocks = is_array($section) ? ($section['blocks'] ?? null) : null;
            $utilities = is_array($section) ? ($section['utilities'] ?? null) : null;
            if (! is_string($id)
                || preg_match('/^[a-z][a-z0-9_.-]+$/D', $id) !== 1
                || isset($ids[$id])
                || ! is_string($sectionKey)
                || array_diff($keys, ['blocks', 'id', 'section', 'utilities']) !== []
                || ($blocks !== null && (! is_array($blocks) || ! array_is_list($blocks) || $blocks === []))
                || ($utilities !== null && (! is_array($utilities) || ! array_is_list($utilities) || count($utilities) > 16))
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_REGION_SECTION_INVALID',
                    "Declarative region [$region] contains an invalid section call.",
                );
            }
            $definition = $this->definitions->section($sectionKey);
            if (! in_array($region, $definition['allowed_regions'], true)
                || ! in_array((string) $definition['type'], $regionDefinition['section_types'], true)
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_REGION_SECTION_INVALID',
                    "Section [$sectionKey] is not allowed in region [$region].",
                );
            }
            $sectionCapabilities = is_array($definition['capabilities'] ?? null)
                ? $definition['capabilities']
                : [];
            $regionCapabilities = is_array($regionDefinition['capabilities'] ?? null)
                ? $regionDefinition['capabilities']
                : ['region.' . $region];
            if ($sectionCapabilities !== [] && array_intersect($sectionCapabilities, $regionCapabilities) === []) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_SECTION_CAPABILITY_MISMATCH',
                    "Section [$sectionKey] has no capability admitted by region [$region].",
                );
            }
            if ($blocks !== null) {
                $this->assertBlocks($region, $id, $blocks, $definition, $regionCapabilities);
            }
            $ids[$id] = true;
        }
    }

    /** @param list<array<string, mixed>> $blocks @param array<string, mixed> $section */
    private function assertBlocks(string $region, string $sectionId, array $blocks, array $section, array $regionCapabilities): void
    {
        if (count($blocks) > 12) {
            throw new PortableConfigurationException(
                'DECLARATIVE_REGION_BLOCK_LIMIT_EXCEEDED',
                "Declarative section [$sectionId] in region [$region] exceeds the block limit.",
            );
        }
        $ids = [];
        foreach ($blocks as $block) {
            $id = is_array($block) ? ($block['id'] ?? null) : null;
            $key = is_array($block) ? ($block['block'] ?? null) : null;
            $slot = is_array($block) ? ($block['slot'] ?? null) : null;
            if (! is_string($id)
                || preg_match('/^[a-z][a-z0-9_.-]+$/D', $id) !== 1
                || isset($ids[$id])
                || ! is_string($key)
                || ! in_array($key, $section['allowed_blocks'], true)
                || ! is_string($slot)
                || ! in_array($slot, $section['slots'], true)
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_REGION_BLOCK_INVALID',
                    "Declarative section [$sectionId] contains an invalid block call.",
                );
            }
            $definition = $this->definitions->block($key);
            match ($definition['kind']) {
                'element' => $this->assertElementBlock($sectionId, $id, $block),
                'smart' => $this->assertSmartBlock($sectionId, $id, $block, $section, $regionCapabilities),
                default => throw new PortableConfigurationException(
                    'DECLARATIVE_REGION_BLOCK_KIND_FORBIDDEN',
                    "Block [$key] cannot be authored in a shell region.",
                ),
            };
            $ids[$id] = true;
        }
    }

    /** @param array<string, mixed> $block */
    private function assertElementBlock(string $sectionId, string $id, array $block): void
    {
        $element = $block['element'] ?? null;
        $elementUtilities = is_array($element) ? ($element['utilities'] ?? []) : null;
        if (! is_array($element)
            || array_diff(array_keys($block), ['id', 'block', 'slot', 'element']) !== []
            || array_diff(array_keys($element), ['tag', 'text', 'href', 'aria_label', 'utilities']) !== []
            || ! is_string($element['tag'] ?? null)
            || ! in_array($element['tag'], ['div', 'p', 'span', 'a'], true)
            || ! is_string($element['text'] ?? null)
            || trim($element['text']) === ''
            || mb_strlen($element['text']) > 500
            || ! is_array($elementUtilities)
            || ! array_is_list($elementUtilities)
            || count($elementUtilities) > 16
            || count(array_filter($elementUtilities, 'is_string')) !== count($elementUtilities)
            || count($elementUtilities) !== count(array_unique($elementUtilities))
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_REGION_ELEMENT_INVALID',
                "Declarative element block [$sectionId.$id] is invalid.",
            );
        }
    }

    /** @param array<string, mixed> $block */
    private function assertSmartBlock(string $sectionId, string $id, array $block, array $section, array $regionCapabilities): void
    {
        $smart = $block['smart'] ?? null;
        if (array_diff(array_keys($block), ['id', 'block', 'slot', 'smart', 'view', 'bind', 'props']) !== []
            || ! is_string($smart)
            || (array_key_exists('view', $block) && ! is_string($block['view']))
            || ! is_array($block['props'] ?? null)
            || count($block['props']) > 32
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_REGION_SMART_INVALID',
                "Declarative Smart block [$sectionId.$id] is invalid.",
            );
        }
        try {
            $this->definitions->assertSmartRegistered($smart);
        } catch (\InvalidArgumentException $exception) {
            throw new PortableConfigurationException(
                'DECLARATIVE_REGION_SMART_INVALID',
                "Declarative Smart block [$sectionId.$id] references an unregistered component [$smart].",
                $exception,
            );
        }
        if (array_key_exists('bind', $block)) {
            if (! is_string($block['bind']) || preg_match('/^[a-z][a-z0-9-]*\.[a-z][a-z0-9_.-]+$/D', $block['bind']) !== 1) {
                throw new PortableConfigurationException('DECLARATIVE_REGION_BINDING_FORBIDDEN', "Declarative Smart block [$sectionId.$id] has an invalid binding ID.");
            }
            $binding = $this->definitions->bindings()->get($block['bind']);
            $sectionCapabilities = is_array($section['capabilities'] ?? null) ? $section['capabilities'] : [];
            if ($binding->smart !== $smart
                || array_intersect($binding->capabilities, $regionCapabilities) === []
                || ($sectionCapabilities !== [] && array_intersect($binding->capabilities, $sectionCapabilities) === [])
            ) {
                throw new PortableConfigurationException('DECLARATIVE_BINDING_CAPABILITY_MISMATCH', "Binding [{$block['bind']}] is not admitted for Smart [$smart] in this shell capability.");
            }
        }
    }
}
