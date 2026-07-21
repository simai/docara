<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering;

use Simai\Docara\Declarative\Plan\ResolvedBlockPlan;
use Simai\Docara\Declarative\Plan\ResolvedRenderPlan;
use Simai\Docara\Declarative\Plan\ResolvedSectionPlan;
use Simai\Docara\PortableSite\PortableDocumentOutlineBuilder;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final readonly class DeclarativePageRenderer
{
    public function __construct(
        private PortableMarkdownRenderer $markdown,
        private SmartRenderer $smart = new SmartRenderer,
        private ViewTreeRenderer $viewTrees = new ViewTreeRenderer,
        private SafeElementRenderer $elements = new SafeElementRenderer,
        private array $reservedDocumentIds = [],
    ) {}

    public function render(
        ResolvedRenderPlan $plan,
        ?string $trustedGeneratedMainHtml = null,
    ): RenderArtifact {
        $regions = [];
        $assets = $plan->assets;
        $sectionEvidence = [];
        $componentHydration = [];
        foreach ($plan->regions as $region => $sections) {
            if ($region === 'main' && $trustedGeneratedMainHtml !== null) {
                $regions[$region] = $trustedGeneratedMainHtml;
                $sectionEvidence[] = [
                    'source' => '@docara/generated-content',
                    'sha256' => hash('sha256', $trustedGeneratedMainHtml),
                ];

                continue;
            }
            $renderedSections = [];
            foreach ($sections as $section) {
                $artifact = $this->section($section);
                $renderedSections[] = $artifact->html;
                array_push($assets, ...$artifact->assets);
                $sectionEvidence[] = $artifact->provenance;
                array_push($componentHydration, ...($artifact->hydration['components'] ?? []));
            }
            $regions[$region] = implode('', $renderedSections);
        }
        $outline = (new PortableDocumentOutlineBuilder)->build(
            $regions['main'],
            $plan->outlineDepth,
            $this->reservedDocumentIds,
        );
        $regions['main'] = $outline['html'];
        $assets = array_values(array_unique($assets));
        sort($assets, SORT_STRING);

        $identity = [
            'page_key' => $plan->pageKey,
            'page_title' => $plan->title,
        ];
        foreach ($plan->layout->regions as $region => $descriptor) {
            $identity['enabled:' . $region] = $descriptor->enabled ? 'true' : 'false';
        }

        return new RenderArtifact(
            $this->viewTrees->render(
                $plan->layout->viewTree['tree'],
                $regions,
                [],
                $identity,
            ),
            $assets,
            [
                'runtime' => 'docara.declarative.v1',
                'outline' => $outline['items'],
                'regions' => $regions,
                'components' => $componentHydration,
                'main_source' => $trustedGeneratedMainHtml === null
                    ? 'resolved_blocks'
                    : 'trusted_generated_projection',
            ],
            [
                'plan_hash' => $plan->canonicalHash(),
                'layout' => $plan->layout->provenance,
                'sections' => $sectionEvidence,
            ],
        );
    }

    private function section(ResolvedSectionPlan $section): RenderArtifact
    {
        $slots = array_fill_keys($section->slots, '');
        $assets = [];
        $blocks = [];
        $componentHydration = [];
        foreach ($section->blocks as $block) {
            $artifact = $this->block($block);
            $slots[$block->slot] .= $artifact->html;
            array_push($assets, ...$artifact->assets);
            $blocks[] = $artifact->provenance;
            if (isset($artifact->hydration['hydration_owner'])) {
                $componentHydration[] = $artifact->hydration;
            }
        }

        return new RenderArtifact(
            $this->viewTrees->render(
                $section->viewTree['tree'],
                [],
                $slots,
                [
                    'section_id' => $section->id,
                    'section_key' => $section->section,
                    'section_region' => $section->region,
                ],
            ),
            array_values(array_unique($assets)),
            ['components' => $componentHydration],
            $section->provenance + ['blocks' => $blocks],
        );
    }

    private function block(ResolvedBlockPlan $block): RenderArtifact
    {
        if ($block->renderer === 'block.markdown') {
            return new RenderArtifact(
                $this->markdown->render((string) $block->data['markdown']),
                [],
                [],
                $block->provenance + ['block' => $block->block],
            );
        }
        if ($block->renderer === 'block.smart' && $block->smart !== null) {
            return $this->smart->render($block->smart);
        }
        if ($block->renderer === 'block.element' && is_array($block->data['element'] ?? null)) {
            return new RenderArtifact(
                $this->elements->render($block->data['element']),
                [],
                ['runtime' => 'docara.safe_element.v1'],
                $block->provenance + [
                    'block' => $block->block,
                    'source' => $block->data['source'] ?? '@layout-configuration',
                ],
            );
        }

        throw new \InvalidArgumentException('DECLARATIVE_BLOCK_RENDERER_UNSUPPORTED');
    }
}
