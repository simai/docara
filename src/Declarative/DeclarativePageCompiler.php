<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative;

use Simai\Docara\Declarative\Composition\PageCompositionContext;
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
    ): ResolvedRenderPlan {
        $layoutDefinition = $this->definitions->layout('docara.docs');
        $layout = $this->layout($layoutDefinition);
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
            $regions[$region] = $region === 'main' ? [$section] : [];
        }
        if ($composition !== null) {
            $regions['header'] = [
                $this->shellSection(
                    $pageKey,
                    'header',
                    'docara.header',
                    ['branding' => $composition->branding],
                    $layout,
                ),
            ];
            $regions['sidebar'] = [
                $this->shellSection(
                    $pageKey,
                    'sidebar',
                    'docara.navigation',
                    ['items' => $composition->navigation, 'maximum_depth' => 4],
                    $layout,
                ),
            ];
            $regions['outline'] = [
                $this->shellSection(
                    $pageKey,
                    'outline',
                    'docara.outline',
                    ['items' => $composition->outline],
                    $layout,
                ),
            ];
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
            ],
        );
    }

    /** @param array<string, mixed> $props */
    private function shellSection(
        string $pageKey,
        string $region,
        string $smart,
        array $props,
        LayoutDescriptor $layout,
    ): ResolvedSectionPlan {
        $definition = $this->definitions->section('docara.shell');
        if (! in_array($region, $definition['allowed_regions'], true)
            || ! in_array((string) $definition['type'], $layout->regions[$region]->sectionTypes, true)
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_SECTION_REGION_FORBIDDEN',
                "Section [docara.shell] is not allowed in region [$region].",
            );
        }
        $nodeId = 'smart-' . substr(hash('sha256', $pageKey . "\0" . $smart), 0, 20);
        $block = $this->block(
            'block-' . substr(hash('sha256', $nodeId . "\0shell.smart"), 0, 20),
            'shell.smart',
            [],
            $this->composites->resolve($smart, $nodeId, $props),
            $definition,
        );

        return new ResolvedSectionPlan(
            'section-' . substr(hash('sha256', $pageKey . "\0" . $region . "\0docara.shell"), 0, 20),
            'docara.shell',
            (string) $definition['type'],
            $region,
            [$block],
            [
                'definition' => (string) $definition['_source'],
                'sha256' => (string) $definition['_sha256'],
            ],
        );
    }

    /** @param array<string, mixed> $definition */
    private function layout(array $definition): LayoutDescriptor
    {
        $regions = [];
        foreach ($definition['regions'] as $key => $region) {
            $regions[$key] = new LayoutRegion(
                (string) $key,
                (bool) $region['required'],
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
