<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative;

use Simai\Docara\Declarative\Composition\PageCompositionContext;
use Simai\Docara\Declarative\Composition\RegionCompositionResolver;
use Simai\Docara\Declarative\Definition\DefinitionRepository;
use Simai\Docara\Declarative\Document\DocumentAst;
use Simai\Docara\Declarative\Document\MarkdownNode;
use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Declarative\Layout\LayoutDescriptor;
use Simai\Docara\Declarative\Layout\LayoutRegion;
use Simai\Docara\Declarative\Plan\ResolvedBlockPlan;
use Simai\Docara\Declarative\Plan\ResolvedRenderPlan;
use Simai\Docara\Declarative\Plan\ResolvedSectionPlan;
use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;
use Simai\Docara\Declarative\Rendering\ViewTreeInspector;
use Simai\Docara\Declarative\Smart\CompositeSmartPlanResolver;
use Simai\Docara\Declarative\Smart\SmartPlanResolver;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class DeclarativePageCompiler
{
    public function __construct(
        private DefinitionRepository $definitions,
        private SmartPlanResolver $smart,
        private CompositeSmartPlanResolver $composites = new CompositeSmartPlanResolver,
        private RegionCompositionResolver $regionComposition = new RegionCompositionResolver,
        private ViewTreeInspector $viewTrees = new ViewTreeInspector,
    ) {}

    /** @param array<string, mixed> $frameworkLock */
    public static function bundled(array $frameworkLock): self
    {
        return new self(
            new DefinitionRepository,
            SmartPlanResolver::fromLock($frameworkLock),
        );
    }

    public function compile(
        DocumentAst $document,
        string $pageKey,
        string $title,
        int $outlineDepth = 3,
        ?PageCompositionContext $composition = null,
        array $layoutConfiguration = [],
        array $configurationProvenance = [],
    ): ResolvedRenderPlan {
        $regionComposition = $this->regionComposition->resolve(
            $layoutConfiguration,
            $configurationProvenance,
        );
        $layoutDefinition = $this->definitions->layout($regionComposition['key']);
        $layout = $this->layout(
            $layoutDefinition,
            $regionComposition['regions'],
            $regionComposition['provenance'],
        );
        $sectionDefinition = $this->definitions->section('docara.article');
        if (! in_array('main', $sectionDefinition['allowed_regions'], true)
            || ! in_array((string) $sectionDefinition['type'], $layout->regions['main']->sectionTypes, true)
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_SECTION_REGION_FORBIDDEN',
                'Section [docara.article] is not allowed in region [main].',
            );
        }

        $blocks = [];
        foreach ($document->nodes as $node) {
            if ($node instanceof MarkdownNode) {
                $blocks[] = $this->markdownBlock($node, $sectionDefinition);

                continue;
            }
            if ($node instanceof SmartCallNode) {
                $blocks[] = $this->smartBlock($node, $sectionDefinition);
            }
        }
        if ($blocks === []) {
            throw new PortableConfigurationException(
                'DECLARATIVE_PAGE_BLOCKS_REQUIRED',
                'A declarative page must resolve at least one block.',
            );
        }

        $section = new ResolvedSectionPlan(
            'section-' . substr(hash('sha256', $pageKey . "\0docara.article"), 0, 20),
            'docara.article',
            (string) $sectionDefinition['type'],
            'main',
            (string) $sectionDefinition['view'],
            $this->viewTree((string) $sectionDefinition['view']),
            $sectionDefinition['slots'],
            $blocks,
            [
                'definition' => (string) $sectionDefinition['_source'],
                'sha256' => (string) $sectionDefinition['_sha256'],
            ],
        );
        $regions = [];
        foreach (array_keys($layout->regions) as $region) {
            $regions[$region] = $region === 'main' && $layout->regions[$region]->enabled
                ? [$section]
                : [];
        }
        if ($composition !== null) {
            foreach ($regionComposition['regions'] as $region => $regionConfiguration) {
                if ($region === 'main' || ! $regionConfiguration['enabled']) {
                    continue;
                }
                foreach ($regionConfiguration['sections'] as $sectionConfiguration) {
                    $regions[$region][] = $this->configuredShellSection(
                        $pageKey,
                        $region,
                        $sectionConfiguration,
                        $composition,
                        $layout,
                    );
                }
            }
        }
        $sectionIds = [];
        $blockIds = [];
        foreach ($regions as $regionSections) {
            foreach ($regionSections as $resolvedSection) {
                if (isset($sectionIds[$resolvedSection->id])) {
                    throw new PortableConfigurationException(
                        'DECLARATIVE_SECTION_INSTANCE_ID_DUPLICATED',
                        "Section instance ID [{$resolvedSection->id}] is duplicated.",
                    );
                }
                $sectionIds[$resolvedSection->id] = true;
                foreach ($resolvedSection->blocks as $resolvedBlock) {
                    if (isset($blockIds[$resolvedBlock->id])) {
                        throw new PortableConfigurationException(
                            'DECLARATIVE_BLOCK_INSTANCE_ID_DUPLICATED',
                            "Block instance ID [{$resolvedBlock->id}] is duplicated.",
                        );
                    }
                    $blockIds[$resolvedBlock->id] = true;
                }
            }
        }

        $assets = $layout->assets;
        foreach ($regions as $sections) {
            foreach ($sections as $regionSection) {
                foreach ($regionSection->blocks as $block) {
                    if ($block->smart === null) {
                        continue;
                    }
                    array_push($assets, ...$block->smart->assets);
                }
            }
        }
        $assets = array_values(array_unique($assets));
        sort($assets, SORT_STRING);
        $layoutInspection = $this->viewTrees->inspect($layout->viewTree['tree']);
        if ($layoutInspection['regions'] !== array_keys($layout->regions)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_LAYOUT_VIEW_REGIONS_MISMATCH',
                'The layout View Tree must place every declared region exactly once and in descriptor order.',
            );
        }
        foreach ($regions as $regionSections) {
            foreach ($regionSections as $resolvedSection) {
                $inspection = $this->viewTrees->inspect($resolvedSection->viewTree['tree']);
                if ($inspection['slots'] !== $resolvedSection->slots) {
                    throw new PortableConfigurationException(
                        'DECLARATIVE_SECTION_VIEW_SLOTS_MISMATCH',
                        "Section [{$resolvedSection->section}] View Tree slots do not match its definition.",
                    );
                }
            }
        }

        return new ResolvedRenderPlan(
            $pageKey,
            $title,
            $outlineDepth,
            $layout,
            $document,
            $regions,
            $assets,
            [
                'compiler' => 'docara.declarative_page_compiler.v1',
                'document_hash' => $document->canonicalHash(),
                'composition' => $composition?->toArray(),
                'region_composition' => $regionComposition,
                'view_runtime' => $layoutInspection['utility_registry'],
            ],
            [
                [
                    'code' => 'COMPOSITION_EXPANDED',
                    'status' => 'pass',
                    'regions' => count($regions),
                    'sections' => array_sum(array_map('count', $regions)),
                ],
                [
                    'code' => 'SAFE_VIEW_TREE_VALIDATED',
                    'status' => 'pass',
                    'layout_nodes' => $layoutInspection['nodes'],
                    'framework_compatibility_id' => $layoutInspection['utility_registry']['compatibility_id'],
                ],
                [
                    'code' => 'AUTHOR_EXECUTABLE_SURFACES',
                    'status' => 'absent',
                ],
            ],
        );
    }

    /** @param array<string, mixed> $configuration */
    private function configuredShellSection(
        string $pageKey,
        string $region,
        array $configuration,
        PageCompositionContext $composition,
        LayoutDescriptor $layout,
    ): ResolvedSectionPlan {
        $sectionKey = (string) $configuration['section'];
        $definition = $this->definitions->section($sectionKey);
        if (! in_array($region, $definition['allowed_regions'], true)
            || ! in_array((string) $definition['type'], $layout->regions[$region]->sectionTypes, true)
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_SECTION_REGION_FORBIDDEN',
                "Section [$sectionKey] is not allowed in region [$region].",
            );
        }
        $blocks = [];
        foreach ($definition['blocks'] as $blockConfiguration) {
            $smart = (string) $blockConfiguration['smart'];
            $nodeId = 'smart-' . substr(
                hash('sha256', $pageKey . "\0" . $region . "\0" . $configuration['id'] . "\0" . $blockConfiguration['id'] . "\0" . $smart),
                0,
                20,
            );
            $blocks[] = $this->block(
                (string) $configuration['id'] . '.' . $blockConfiguration['id'],
                (string) $blockConfiguration['block'],
                (string) $blockConfiguration['slot'],
                ['binding' => (string) $blockConfiguration['bind']],
                $this->composites->resolve(
                    $smart,
                    $nodeId,
                    $this->boundProps($blockConfiguration, $composition),
                ),
                $definition,
            );
        }

        return new ResolvedSectionPlan(
            (string) $configuration['id'],
            $sectionKey,
            (string) $definition['type'],
            $region,
            (string) $definition['view'],
            $this->viewTree((string) $definition['view']),
            $definition['slots'],
            $blocks,
            [
                'definition' => (string) $definition['_source'],
                'sha256' => (string) $definition['_sha256'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>
     */
    private function boundProps(array $block, PageCompositionContext $composition): array
    {
        return match ($block['bind']) {
            'branding' => ['branding' => $composition->branding],
            'navigation' => [
                'items' => $composition->navigation,
                'maximum_depth' => (int) ($block['props']['maximum_depth'] ?? 4),
            ],
            'outline' => ['items' => $composition->outline],
            default => throw new PortableConfigurationException(
                'DECLARATIVE_REGION_BINDING_FORBIDDEN',
                "Unknown declarative region binding [{$block['bind']}].",
            ),
        };
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, array{enabled:bool,sections:list<array<string,mixed>>}>  $configuration
     * @param  array<string, string>  $configurationProvenance
     */
    private function layout(
        array $definition,
        array $configuration,
        array $configurationProvenance,
    ): LayoutDescriptor {
        $regions = [];
        foreach ($definition['regions'] as $key => $region) {
            $enabled = $configuration[$key]['enabled'];
            if ((bool) $region['required'] && ! $enabled) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_REQUIRED_REGION_DISABLED',
                    "Required region [$key] cannot be disabled.",
                );
            }
            $regions[$key] = new LayoutRegion(
                (string) $key,
                (bool) $region['required'],
                $enabled,
                $region['section_types'],
            );
        }

        return new LayoutDescriptor(
            (string) $definition['key'],
            (string) $definition['view'],
            $this->viewTree((string) $definition['view']),
            $regions,
            $definition['assets'],
            [
                'definition' => (string) $definition['_source'],
                'sha256' => (string) $definition['_sha256'],
                'configuration' => $configurationProvenance,
            ],
        );
    }

    /** @param array<string, mixed> $section */
    private function markdownBlock(MarkdownNode $node, array $section): ResolvedBlockPlan
    {
        return $this->block(
            $node->id(),
            'content.markdown',
            'content',
            ['markdown' => $node->markdown, 'source' => $node->span()->toArray()],
            null,
            $section,
        );
    }

    /** @param array<string, mixed> $section */
    private function smartBlock(SmartCallNode $node, array $section): ResolvedBlockPlan
    {
        return $this->block(
            $node->id(),
            'content.smart',
            'content',
            ['source' => $node->span()->toArray()],
            $this->smart->resolve($node),
            $section,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $section
     */
    private function block(
        string $id,
        string $key,
        string $slot,
        array $data,
        ?ResolvedSmartPlan $smart,
        array $section,
    ): ResolvedBlockPlan {
        if (! in_array($key, $section['allowed_blocks'], true)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_BLOCK_SECTION_FORBIDDEN',
                "Block [$key] is not allowed in section [{$section['key']}].",
            );
        }
        if (! in_array($slot, $section['slots'], true)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_BLOCK_SLOT_FORBIDDEN',
                "Block [$key] cannot target slot [$slot] in section [{$section['key']}].",
            );
        }
        $definition = $this->definitions->block($key);
        if ($smart !== null && ! in_array($smart->smart, $definition['allowed_smart'], true)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_BLOCK_SMART_FORBIDDEN',
                "Smart component [{$smart->smart}] is not allowed by block [$key].",
            );
        }

        return new ResolvedBlockPlan(
            $id,
            $key,
            $slot,
            (string) $definition['renderer'],
            $data,
            $smart,
            [
                'definition' => (string) $definition['_source'],
                'sha256' => (string) $definition['_sha256'],
            ],
        );
    }

    /** @return array<string, mixed> */
    private function viewTree(string $view): array
    {
        $definition = $this->definitions->view($view);

        return [
            'key' => $definition['key'],
            'tree' => $definition['tree'],
            'provenance' => [
                'definition' => $definition['_source'],
                'sha256' => $definition['_sha256'],
            ],
        ];
    }
}
