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
            'schema' => 'docara.resolved_render_plan.v1',
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
                    if ($block->smart instanceof ResolvedSmartPlan) {
                        $smart[] = [
                            'smart' => $block->smart->smart,
                            'view' => $block->smart->view,
                            'props' => $block->smart->props,
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
}
