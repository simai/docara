<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Plan;

use Simai\Docara\Declarative\Document\DocumentAst;
use Simai\Docara\Declarative\Layout\LayoutDescriptor;
use Simai\Docara\Portable\CanonicalJson;

final readonly class ResolvedRenderPlan
{
    /**
     * @param  array<string, list<ResolvedSectionPlan>>  $regions
     * @param  list<string>  $assets
     * @param  array<string, mixed>  $provenance
     * @param  list<array<string, mixed>>  $diagnostics
     */
    public function __construct(
        public string $pageKey,
        public string $title,
        public int $outlineDepth,
        public LayoutDescriptor $layout,
        public DocumentAst $document,
        public array $regions,
        public array $assets,
        public array $provenance,
        public array $diagnostics,
    ) {
        if ($pageKey === ''
            || trim($title) === ''
            || $outlineDepth < 2
            || $outlineDepth > 6
            || array_keys($regions) !== array_keys($layout->regions)
        ) {
            throw new \InvalidArgumentException('RESOLVED_RENDER_PLAN_INVALID');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'docara.resolved_render_plan.v2',
            'page_key' => $this->pageKey,
            'title' => $this->title,
            'outline_depth' => $this->outlineDepth,
            'layout' => $this->layout->toArray(),
            'document' => $this->document->toArray(),
            'regions' => array_map(
                static fn (array $sections): array => array_map(
                    static fn (ResolvedSectionPlan $section): array => $section->toArray(),
                    $sections,
                ),
                $this->regions,
            ),
            'assets' => $this->assets,
            'provenance' => $this->provenance,
            'diagnostics' => $this->diagnostics,
        ];
    }

    public function canonicalHash(): string
    {
        return hash('sha256', CanonicalJson::encode($this->toArray()));
    }

    /** @return array<string, mixed> */
    public function semanticProjection(): array
    {
        $smart = [];
        foreach ($this->regions as $sections) {
            foreach ($sections as $section) {
                foreach ($section->blocks as $block) {
                    foreach ($this->smartPlans($block) as $resolvedSmart) {
                        $smart[] = [
                            'smart' => $resolvedSmart->smart,
                            'view' => $resolvedSmart->view,
                            'props' => $resolvedSmart->props,
                        ];
                    }
                }
            }
        }

        return [
            'title' => $this->title,
            'regions' => array_keys($this->regions),
            'headings' => array_map(
                static fn ($heading): array => [
                    'id' => $heading->id,
                    'level' => $heading->level,
                    'text' => $heading->text,
                ],
                $this->document->headings,
            ),
            'links' => array_map(
                static fn ($link): array => [
                    'destination' => $link->destination,
                    'label' => $link->label,
                ],
                $this->document->links,
            ),
            'smart' => $smart,
        ];
    }

    /** @return list<ResolvedSmartPlan> */
    private function smartPlans(ResolvedBlockPlan $block): array
    {
        $smart = $block->smart instanceof ResolvedSmartPlan ? [$block->smart] : [];
        $nodes = $block->data['nodes'] ?? null;
        if (! is_array($nodes)) {
            return $smart;
        }
        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }
            $nested = ResolvedBlockPlan::fromArray($node);
            array_push($smart, ...$this->smartPlans($nested));
        }

        return $smart;
    }
}
