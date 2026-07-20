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
                foreach ($regionConfiguration['sections'] as $index => $sectionConfiguration) {
                    $regions[$region][] = $this->configuredShellSection(
                        $pageKey,
                        $region,
                        $index,
                        $sectionConfiguration,
                        $composition,
                        $layout,
                    );
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
            ],
        );
    }

    /** @param array<string, mixed> $configuration */
    private function configuredShellSection(
        string $pageKey,
        string $region,
        int $sectionIndex,
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
        foreach ($configuration['blocks'] as $blockIndex => $blockConfiguration) {
            $smart = (string) $blockConfiguration['smart'];
            $nodeId = 'smart-' . substr(
                hash('sha256', $pageKey . "\0" . $region . "\0" . $sectionIndex . "\0" . $blockIndex . "\0" . $smart),
                0,
                20,
            );
            $blocks[] = $this->block(
                'block-' . substr(hash('sha256', $nodeId . "\0" . $blockConfiguration['block']), 0, 20),
                (string) $blockConfiguration['block'],
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
            'section-' . substr(
                hash('sha256', $pageKey . "\0" . $region . "\0" . $sectionIndex . "\0" . $sectionKey),
                0,
                20,
            ),
            $sectionKey,
            (string) $definition['type'],
            $region,
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
            (string) $definition['template'],
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
        $definition = $this->definitions->block($key);

        return new ResolvedBlockPlan(
            $id,
            $key,
            (string) $definition['renderer'],
            $data,
            $smart,
            [
                'definition' => (string) $definition['_source'],
                'sha256' => (string) $definition['_sha256'],
            ],
        );
    }
}
