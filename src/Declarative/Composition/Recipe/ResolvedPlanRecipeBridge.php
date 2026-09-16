<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Composition\Recipe;

use Simai\Docara\Declarative\Document\DocumentAst;
use Simai\Docara\Declarative\Layout\LayoutDescriptor;
use Simai\Docara\Declarative\Layout\LayoutRegion;
use Simai\Docara\Declarative\Plan\ResolvedBlockPlan;
use Simai\Docara\Declarative\Plan\ResolvedRenderPlan;
use Simai\Docara\Declarative\Plan\ResolvedSectionPlan;
use Simai\Docara\Document\DocumentIr;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class ResolvedPlanRecipeBridge
{
    public function __construct(
        private FileRecipeCompiler $runtime,
        private string $projectRoot,
    ) {}

    public function resolve(ResolvedRenderPlan $plan): ResolvedRenderPlan
    {
        $recipe = $this->recipe($plan);
        $inputs = [
            'schema' => 'simai.composition.inputs.v1',
            'scope' => 'docara:portable-site',
            'values' => (object) [],
        ];
        $result = $this->runtime->resolve($this->projectRoot, $recipe, $inputs, self::manifests());
        $document = $result['document'];
        $actualProjection = is_array($document) ? $this->projection($document['root'] ?? null) : [];
        $expectedProjection = $this->expectedProjection($plan);
        if (CanonicalJson::encode($actualProjection) !== CanonicalJson::encode($expectedProjection)) {
            $expectedProjection = json_decode(CanonicalJson::encode($expectedProjection), true, 512, JSON_THROW_ON_ERROR);
            $actualProjection = json_decode(CanonicalJson::encode($actualProjection), true, 512, JSON_THROW_ON_ERROR);
            throw new PortableConfigurationException(
                'COMPOSITION_RECIPE_PLAN_PARITY_FAILED',
                "Composition Recipe changed the resolved page plan [{$plan->pageKey}] (expected "
                    . hash('sha256', json_encode($expectedProjection, JSON_THROW_ON_ERROR))
                    . ', actual '
                    . hash('sha256', json_encode($actualProjection, JSON_THROW_ON_ERROR))
                    . ', first difference '
                    . $this->firstDifference($expectedProjection, $actualProjection)
                    . ').',
            );
        }

        return $this->hydrate(
            $document,
            $plan->document,
            [
                'schema' => 'docara.composition_recipe_primary.v1',
                'recipe_digest' => $result['dependencyReceipt']['recipeDigest'],
                'document_digest' => $result['dependencyReceipt']['documentDigest'],
                'dependency_receipt' => $result['dependencyReceipt'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $document
     * @param  array<string, mixed>  $receipt
     */
    private function hydrate(array $document, DocumentAst|DocumentIr $sourceDocument, array $receipt): ResolvedRenderPlan
    {
        $root = $document['root'] ?? null;
        $data = is_array($root) && is_array($root['data'] ?? null) ? $root['data'] : null;
        $regionNodes = is_array($root) && is_array($root['slots']['regions'] ?? null) ? $root['slots']['regions'] : null;
        if (! is_array($data) || ! is_array($regionNodes) || ! is_array($data['layout'] ?? null)) {
            throw new PortableConfigurationException('COMPOSITION_RECIPE_DOCUMENT_INVALID', 'Resolved Docara Composition Document is incomplete.');
        }
        $layoutData = $data['layout'];
        $layoutRegions = [];
        $regions = [];
        foreach ($regionNodes as $regionNode) {
            $regionData = is_array($regionNode) && is_array($regionNode['data'] ?? null) ? $regionNode['data'] : null;
            $sectionNodes = is_array($regionNode) && is_array($regionNode['slots']['sections'] ?? null) ? $regionNode['slots']['sections'] : null;
            if (! is_array($regionData) || ! is_array($sectionNodes) || ! is_string($regionData['key'] ?? null)) {
                throw new PortableConfigurationException('COMPOSITION_RECIPE_DOCUMENT_INVALID', 'Resolved Docara region is incomplete.');
            }
            $region = new LayoutRegion(
                $regionData['key'],
                (bool) ($regionData['required'] ?? false),
                (bool) ($regionData['enabled'] ?? false),
                is_array($regionData['section_types'] ?? null) ? $regionData['section_types'] : [],
                is_array($regionData['capabilities'] ?? null) ? $regionData['capabilities'] : [],
            );
            $layoutRegions[$region->key] = $region;
            $regions[$region->key] = [];
            foreach ($sectionNodes as $sectionNode) {
                $sectionData = is_array($sectionNode) && is_array($sectionNode['data'] ?? null) ? $sectionNode['data'] : null;
                $blockNodes = is_array($sectionNode) && is_array($sectionNode['slots']['blocks'] ?? null) ? $sectionNode['slots']['blocks'] : null;
                if (! is_array($sectionData) || ! is_array($blockNodes)) {
                    throw new PortableConfigurationException('COMPOSITION_RECIPE_DOCUMENT_INVALID', 'Resolved Docara section is incomplete.');
                }
                $blocks = [];
                foreach ($blockNodes as $blockNode) {
                    if (! is_array($blockNode) || ! is_array($blockNode['data'] ?? null)) {
                        throw new PortableConfigurationException('COMPOSITION_RECIPE_DOCUMENT_INVALID', 'Resolved Docara block is incomplete.');
                    }
                    $blocks[] = ResolvedBlockPlan::fromArray($blockNode['data']);
                }
                $regions[$region->key][] = new ResolvedSectionPlan(
                    (string) ($sectionData['id'] ?? ''),
                    (string) ($sectionData['section'] ?? ''),
                    (string) ($sectionData['type'] ?? ''),
                    (string) ($sectionData['region'] ?? ''),
                    (string) ($sectionData['view'] ?? ''),
                    is_array($sectionData['view_tree'] ?? null) ? $sectionData['view_tree'] : [],
                    is_array($sectionData['slots'] ?? null) ? $sectionData['slots'] : [],
                    $blocks,
                    is_array($sectionData['provenance'] ?? null) ? $sectionData['provenance'] : [],
                );
            }
        }
        $layout = new LayoutDescriptor(
            (string) ($layoutData['key'] ?? ''),
            (string) ($layoutData['view'] ?? ''),
            is_array($layoutData['view_tree'] ?? null) ? $layoutData['view_tree'] : [],
            $layoutRegions,
            (string) ($layoutData['document_region'] ?? ''),
            is_array($layoutData['assets'] ?? null) ? $layoutData['assets'] : [],
            is_array($layoutData['provenance'] ?? null) ? $layoutData['provenance'] : [],
        );

        return new ResolvedRenderPlan(
            (string) ($data['page_key'] ?? ''),
            (string) ($data['title'] ?? ''),
            (int) ($data['outline_depth'] ?? 0),
            $layout,
            $sourceDocument,
            $regions,
            is_array($data['assets'] ?? null) ? $data['assets'] : [],
            (is_array($data['provenance'] ?? null) ? $data['provenance'] : []) + ['composition_recipe_primary' => $receipt],
            is_array($data['diagnostics'] ?? null) ? $data['diagnostics'] : [],
        );
    }

    /** @return array<string, mixed> */
    private function recipe(ResolvedRenderPlan $plan): array
    {
        $regions = [];
        foreach ($plan->layout->regions as $regionOrdinal => $region) {
            $regionKey = $region->key;
            $sections = [];
            foreach ($plan->regions[$regionKey] as $sectionOrdinal => $section) {
                $blocks = [];
                foreach ($section->blocks as $blockOrdinal => $block) {
                    $blocks[] = $this->node(
                        "block-$regionOrdinal-$sectionOrdinal-$blockOrdinal",
                        'docara.block',
                        $block->toArray(),
                    );
                }
                $sectionData = $section->toArray();
                unset($sectionData['blocks']);
                $sections[] = $this->node(
                    "section-$regionOrdinal-$sectionOrdinal",
                    'docara.section',
                    $sectionData,
                    ['blocks' => $blocks],
                );
            }
            $regions[] = $this->node(
                'region-' . substr(hash('sha256', $regionKey), 0, 16),
                'docara.region',
                [
                    'key' => $region->key,
                    'required' => $region->required,
                    'enabled' => $region->enabled,
                    'section_types' => $region->sectionTypes,
                    'capabilities' => $region->capabilities,
                ],
                ['sections' => $sections],
            );
        }
        $layout = $plan->layout->toArray();
        unset($layout['regions']);

        return [
            'schema' => 'simai.composition.recipe.v1',
            'id' => 'docara.page.' . substr(hash('sha256', $plan->pageKey), 0, 24),
            'profile' => 'ui-layout',
            'inputs' => (object) [],
            'root' => $this->node('page', 'docara.page', [
                'page_key' => $plan->pageKey,
                'title' => $plan->title,
                'outline_depth' => $plan->outlineDepth,
                'layout' => $layout,
                'document' => $plan->document->toArray(),
                'assets' => $plan->assets,
                'provenance' => $plan->provenance,
                'diagnostics' => $plan->diagnostics,
            ], ['regions' => $regions]),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, list<array<string, mixed>>>  $slots
     * @return array<string, mixed>
     */
    private function node(string $id, string $type, array $data, array $slots = []): array
    {
        return [
            'id' => $id,
            'node' => [
                'type' => $type,
                'data' => array_map(static fn (mixed $value): array => ['literal' => $value], $data),
                ...($slots === [] ? [] : ['slots' => $slots]),
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function manifests(): array
    {
        $path = dirname(__DIR__, 4) . '/resources/contracts/composition/docara-type-manifests.json';
        $contents = is_file($path) ? file_get_contents($path) : false;
        if (! is_string($contents)) {
            throw new \RuntimeException('docara_composition_recipe_manifests_unavailable');
        }
        try {
            $manifests = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('docara_composition_recipe_manifests_invalid', previous: $exception);
        }
        if (! is_array($manifests) || ! array_is_list($manifests)) {
            throw new \RuntimeException('docara_composition_recipe_manifests_invalid');
        }

        return $manifests;
    }

    /** @return array<string, mixed> */
    private function expectedProjection(ResolvedRenderPlan $plan): array
    {
        $regions = [];
        foreach ($plan->layout->regions as $regionKey => $region) {
            $regions[] = [
                'type' => 'docara.region',
                'data' => [
                    'key' => $region->key,
                    'required' => $region->required,
                    'enabled' => $region->enabled,
                    'section_types' => $region->sectionTypes,
                    'capabilities' => $region->capabilities,
                ],
                'slots' => [
                    'sections' => array_map(
                        fn (ResolvedSectionPlan $section): array => [
                            'type' => 'docara.section',
                            'data' => array_diff_key($section->toArray(), ['blocks' => true]),
                            'slots' => [
                                'blocks' => array_map(
                                    static fn (ResolvedBlockPlan $block): array => [
                                        'type' => 'docara.block',
                                        'data' => $block->toArray(),
                                        'slots' => [],
                                    ],
                                    $section->blocks,
                                ),
                            ],
                        ],
                        $plan->regions[$regionKey],
                    ),
                ],
            ];
        }
        $layout = $plan->layout->toArray();
        unset($layout['regions']);

        return [
            'type' => 'docara.page',
            'data' => [
                'page_key' => $plan->pageKey,
                'title' => $plan->title,
                'outline_depth' => $plan->outlineDepth,
                'layout' => $layout,
                'document' => $plan->document->toArray(),
                'assets' => $plan->assets,
                'provenance' => $plan->provenance,
                'diagnostics' => $plan->diagnostics,
            ],
            'slots' => ['regions' => $regions],
        ];
    }

    /** @return array<string, mixed> */
    private function projection(mixed $node): array
    {
        if (! is_array($node) || ! is_string($node['type'] ?? null) || ! is_array($node['data'] ?? null)) {
            return [];
        }
        $slots = [];
        foreach (($node['slots'] ?? []) as $name => $children) {
            $slots[$name] = array_map(fn (mixed $child): array => $this->projection($child), $children);
        }

        return ['type' => $node['type'], 'data' => $node['data'], 'slots' => $slots];
    }

    private function firstDifference(mixed $expected, mixed $actual, string $path = '$'): string
    {
        if (get_debug_type($expected) !== get_debug_type($actual)) {
            return $path . ':' . get_debug_type($expected) . '!=' . get_debug_type($actual);
        }
        if (! is_array($expected)) {
            return $expected === $actual ? 'unknown' : $path;
        }
        if (array_keys($expected) !== array_keys($actual)) {
            return $path . ':keys';
        }
        foreach ($expected as $key => $value) {
            if ($value !== $actual[$key]) {
                return $this->firstDifference($value, $actual[$key], $path . '.' . $key);
            }
        }

        return 'unknown';
    }
}
