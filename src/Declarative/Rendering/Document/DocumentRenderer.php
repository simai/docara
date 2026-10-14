<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\Document;

use Simai\Docara\Declarative\Plan\ResolvedBlockPlan;
use Simai\Docara\Declarative\Rendering\RenderArtifact;

final readonly class DocumentRenderer
{
    public function __construct(private DocumentNodeRendererRegistry $nodes) {}

    public function render(ResolvedBlockPlan $document): RenderArtifact
    {
        $nodes = $document->data['nodes'] ?? null;
        if (! is_array($nodes) || $nodes === []) {
            throw new \InvalidArgumentException('DECLARATIVE_DOCUMENT_NODES_REQUIRED');
        }

        $html = '';
        $assets = [];
        $components = [];
        $provenance = [];
        foreach ($nodes as $node) {
            if (! is_array($node)) {
                throw new \InvalidArgumentException('DECLARATIVE_DOCUMENT_NODE_INVALID');
            }
            $artifact = $this->nodes->render($node);
            $html .= $artifact->html;
            array_push($assets, ...$artifact->assets);
            if (isset($artifact->hydration['components']) && is_array($artifact->hydration['components'])) {
                array_push($components, ...$artifact->hydration['components']);
            } elseif (isset($artifact->hydration['hydration_owner'])) {
                $components[] = $artifact->hydration;
            }
            $provenance[] = $artifact->provenance;
        }

        $assets = array_values(array_unique($assets));
        sort($assets, SORT_STRING);

        return new RenderArtifact(
            $html,
            $assets,
            [
                'runtime' => 'docara.document_renderer.v1',
                'components' => $components,
            ],
            $document->provenance + [
                'block' => $document->block,
                'source' => $document->data['source'] ?? '@document',
                'nodes' => $provenance,
            ],
        );
    }
}
