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
        private array $reservedDocumentIds = [],
    ) {}

    public function render(ResolvedRenderPlan $plan): RenderArtifact
    {
        $regions = [];
        $assets = $plan->assets;
        $sectionEvidence = [];
        foreach ($plan->regions as $region => $sections) {
            $renderedSections = [];
            foreach ($sections as $section) {
                $artifact = $this->section($section);
                $renderedSections[] = $artifact->html;
                array_push($assets, ...$artifact->assets);
                $sectionEvidence[] = $artifact->provenance;
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
        foreach ($section->blocks as $block) {
            $artifact = $this->block($block);
            $slots[$block->slot] .= $artifact->html;
            array_push($assets, ...$artifact->assets);
            $blocks[] = $artifact->provenance;
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
            [],
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

        throw new \InvalidArgumentException('DECLARATIVE_BLOCK_RENDERER_UNSUPPORTED');
    }
}
