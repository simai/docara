<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative;

use Simai\Docara\Declarative\Composition\PageCompositionContext;
use Simai\Docara\Declarative\Composition\RegionCompositionResolver;
use Simai\Docara\Declarative\Definition\DefinitionRepository;
use Simai\Docara\Declarative\Document\Compilation\DocumentNodeBlockRegistry;
use Simai\Docara\Declarative\Document\DocumentAst;
use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Declarative\Document\SourceSpan;
use Simai\Docara\Declarative\Layout\LayoutDescriptor;
use Simai\Docara\Declarative\Layout\LayoutRegion;
use Simai\Docara\Declarative\Plan\ResolvedBlockFactory;
use Simai\Docara\Declarative\Plan\ResolvedRenderPlan;
use Simai\Docara\Declarative\Plan\ResolvedSectionPlan;
use Simai\Docara\Declarative\Rendering\ViewTreeInspector;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;
use Simai\Docara\Document\DocumentIr;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class DeclarativePageCompiler
{
    private ResolvedBlockFactory $blocks;

    private DocumentNodeBlockRegistry $documentNodes;

    public function __construct(
        private DefinitionRepository $definitions,
        private SmartComponentGateway $smarts,
        private RegionCompositionResolver $regionComposition = new RegionCompositionResolver,
        private ViewTreeInspector $viewTrees = new ViewTreeInspector,
        ?ResolvedBlockFactory $blocks = null,
        ?DocumentNodeBlockRegistry $documentNodes = null,
    ) {
        $this->blocks = $blocks ?? new ResolvedBlockFactory($this->definitions);
        $this->documentNodes = $documentNodes
            ?? DocumentNodeBlockRegistry::bundled($this->blocks, $this->smarts);
    }

    /** @param array<string, mixed> $frameworkLock */
    public static function bundled(array $frameworkLock): self
    {
        return new self(
            new DefinitionRepository,
            SmartComponentGateway::bundled($frameworkLock),
        );
    }

    public function compile(
        DocumentAst|DocumentIr $document,
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

        $documentBlocks = [];
        if ($document instanceof DocumentAst) {
            foreach ($document->nodes as $node) {
                $documentBlocks[] = $this->documentNodes->resolve($node, $sectionDefinition);
            }
        }
        $documentBlockIds = [];
        foreach ($documentBlocks as $documentBlock) {
            if (isset($documentBlockIds[$documentBlock->id])) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_DOCUMENT_NODE_ID_DUPLICATED',
                    "Document node block ID [{$documentBlock->id}] is duplicated.",
                );
            }
            $documentBlockIds[$documentBlock->id] = true;
        }
        $documentData = $document instanceof DocumentAst
            ? [
                'schema' => 'docara.resolved_document.v1',
                'source' => $document->source,
                'nodes' => array_map(
                    static fn ($block): array => $block->toArray(),
                    $documentBlocks,
                ),
            ]
            : [
                'schema' => 'docara.document_ir_reference.v1',
                'source' => $document->source,
                'node_count' => count($document->allNodes()),
                'sha256' => hash('sha256', CanonicalJson::encode($document->toArray())),
            ];
        $blocks = [
            $this->blocks->create(
                'document-' . substr(hash('sha256', $pageKey . "\0" . $document->canonicalHash()), 0, 20),
                'content.document',
                'content',
                $documentData,
                null,
                $sectionDefinition,
            ),
        ];

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
        foreach ($documentBlocks as $documentBlock) {
            if ($documentBlock->smart !== null) {
                array_push($assets, ...$documentBlock->smart->assets);
            }
        }
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
        $blockConfigurations = array_key_exists('blocks', $configuration)
            ? $configuration['blocks']
            : $definition['blocks'];
        $configurationSource = $this->configurationSource($layout, $region);
        foreach ($blockConfigurations as $ordinal => $blockConfiguration) {
            if (($blockConfiguration['block'] ?? null) === 'shell.element') {
                $blocks[] = $this->blocks->create(
                    (string) $configuration['id'] . '.' . $blockConfiguration['id'],
                    'shell.element',
                    (string) $blockConfiguration['slot'],
                    [
                        'element' => $blockConfiguration['element'],
                        'source' => $configurationSource,
                    ],
                    null,
                    $definition,
                );

                continue;
            }
            $smart = (string) $blockConfiguration['smart'];
            $hasBinding = is_string($blockConfiguration['bind'] ?? null);
            $props = $hasBinding
                ? $this->boundProps($blockConfiguration, $composition)
                : (is_array($blockConfiguration['props'] ?? null)
                    ? $blockConfiguration['props']
                    : []);
            $requestedView = is_string($blockConfiguration['view'] ?? null)
                ? $blockConfiguration['view']
                : $this->defaultCompositeView($smart, $props);
            $nodeId = 'smart-' . substr(
                hash('sha256', $pageKey . "\0" . $region . "\0" . $configuration['id'] . "\0" . $blockConfiguration['id'] . "\0" . $smart),
                0,
                20,
            );
            $resolvedSmart = $this->smarts->resolve(new SmartCallNode(
                $nodeId,
                $smart,
                $requestedView,
                $props,
                $ordinal + 1,
                new SourceSpan($configurationSource, 1, 1),
            ));
            $blocks[] = $this->blocks->create(
                (string) $configuration['id'] . '.' . $blockConfiguration['id'],
                (string) $blockConfiguration['block'],
                (string) $blockConfiguration['slot'],
                $hasBinding
                    ? ['binding' => (string) $blockConfiguration['bind']]
                    : ['source' => $configurationSource],
                $resolvedSmart,
                $definition,
            );
        }

        return new ResolvedSectionPlan(
            (string) $configuration['id'],
            $sectionKey,
            (string) $definition['type'],
            $region,
            (string) $definition['view'],
            $this->sectionViewTree(
                (string) $definition['view'],
                is_array($configuration['utilities'] ?? null)
                    ? $configuration['utilities']
                    : [],
            ),
            $definition['slots'],
            $blocks,
            [
                'definition' => (string) $definition['_source'],
                'sha256' => (string) $definition['_sha256'],
            ],
        );
    }

    private function configurationSource(LayoutDescriptor $layout, string $region): string
    {
        $configuration = $layout->provenance['configuration'] ?? [];
        $source = is_array($configuration)
            ? ($configuration['/layout/regions/' . $region . '/sections'] ?? null)
            : null;

        return is_string($source) && $source !== '' ? $source : '@layout-configuration';
    }

    /** @param list<string> $utilities @return array<string, mixed> */
    private function sectionViewTree(string $view, array $utilities): array
    {
        $resolved = $this->viewTree($view);
        if ($utilities === []) {
            return $resolved;
        }
        $existing = is_array($resolved['tree']['utilities'] ?? null)
            ? $resolved['tree']['utilities']
            : [];
        $resolved['tree']['utilities'] = array_values(array_unique([...$existing, ...$utilities]));

        return $resolved;
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
                'label' => $composition->navigationCopy['label'],
                'expand_label' => $composition->navigationCopy['expand'],
                'collapse_label' => $composition->navigationCopy['collapse'],
                'contains_current_label' => $composition->navigationCopy['contains_current'],
            ],
            'header_navigation' => [
                'items' => $composition->headerNavigation,
                'maximum_depth' => 4,
                'label' => $composition->headerNavigationLabel,
                'expand_label' => $composition->navigationCopy['expand'],
                'collapse_label' => $composition->navigationCopy['collapse'],
                'contains_current_label' => $composition->navigationCopy['contains_current'],
            ],
            'outline' => ['items' => $composition->outline, 'label' => $composition->tocLabel],
            default => throw new PortableConfigurationException(
                'DECLARATIVE_REGION_BINDING_FORBIDDEN',
                "Unknown declarative region binding [{$block['bind']}].",
            ),
        };
    }

    /** @param array<string, mixed> $props */
    private function defaultCompositeView(string $smart, array $props): string
    {
        if ($smart !== 'docara.brand') {
            return 'default';
        }

        $branding = is_array($props['branding'] ?? null) ? $props['branding'] : [];

        return match ($branding['mode'] ?? 'full') {
            'compact' => 'compact',
            'logo' => 'logo',
            'text' => 'text',
            default => 'default',
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
