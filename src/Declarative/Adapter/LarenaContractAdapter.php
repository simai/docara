<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Adapter;

use Simai\Docara\Declarative\Plan\ResolvedBlockPlan;
use Simai\Docara\Declarative\Plan\ResolvedRenderPlan;
use Simai\Docara\Declarative\Plan\ResolvedSectionPlan;

final class LarenaContractAdapter
{
    public function adapt(ResolvedRenderPlan $plan): LarenaResolvedRenderContract
    {
        $regions = [];
        foreach ($plan->regions as $region => $sections) {
            $regions[$region] = array_map(
                fn (ResolvedSectionPlan $section): array => $this->section($section),
                $sections,
            );
        }

        return new LarenaResolvedRenderContract(
            [
                'schema' => 'larena.layout.resolved_render_plan.v1',
                'contract_version' => 1,
                'page' => [
                    'key' => $plan->pageKey,
                    'title' => $plan->title,
                    'profile' => 'docs',
                ],
                'layout' => [
                    'key' => $plan->layout->key,
                    'view' => $plan->layout->view,
                    'view_tree' => $plan->layout->viewTree,
                    'regions' => array_keys($plan->layout->regions),
                    'region_configuration' => array_map(
                        static fn ($region): array => [
                            'required' => $region->required,
                            'enabled' => $region->enabled,
                            'section_types' => $region->sectionTypes,
                        ],
                        $plan->layout->regions,
                    ),
                ],
                'regions' => $regions,
                'assets' => $plan->assets,
                'source' => [
                    'adapter' => 'docara.larena_contract_adapter.v1',
                    'docara_plan_schema' => 'docara.resolved_render_plan.v2',
                    'docara_plan_hash' => $plan->canonicalHash(),
                ],
                'diagnostics' => $plan->diagnostics,
            ],
            $plan->semanticProjection(),
        );
    }

    /** @return array<string, mixed> */
    private function section(ResolvedSectionPlan $section): array
    {
        return [
            'id' => $section->id,
            'section' => $section->section,
            'type' => $section->type,
            'region' => $section->region,
            'view' => $section->view,
            'view_tree' => $section->viewTree,
            'slots' => $section->slots,
            'blocks' => array_map(
                fn (ResolvedBlockPlan $block): array => $this->block($block),
                $section->blocks,
            ),
        ];
    }

    /** @return array<string, mixed> */
    private function block(ResolvedBlockPlan $block): array
    {
        return [
            'id' => $block->id,
            'block' => $block->block,
            'slot' => $block->slot,
            'renderer' => $block->renderer,
            'data' => $block->data,
            'smart' => $block->smart === null ? null : [
                'key' => $block->smart->smart,
                'view' => $block->smart->view,
                'template' => $block->smart->template,
                'props' => $block->smart->props,
                'assets' => $block->smart->assets,
            ],
        ];
    }
}
