<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

use Simai\Docara\Declarative\Rendering\RenderArtifact;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final readonly class ContainerDocumentNodeRenderer implements DocumentNodeRenderer
{
    public function __construct(private PortableMarkdownRenderer $markdown) {}

    public function types(): array
    {
        return ['container'];
    }

    public function render(DocumentNode $node, DocumentRenderContext $context): RenderArtifact
    {
        throw new \LogicException('DOCUMENT_CONTAINER_CHILD_ARTIFACTS_REQUIRED');
    }

    /** @param list<RenderArtifact> $children */
    public function renderChildren(ContainerNode $node, array $children, DocumentRenderContext $context): RenderArtifact
    {
        $html = implode('', array_map(static fn (RenderArtifact $artifact): string => $artifact->html, $children));
        $assets = [];
        $localPublicAssets = [];
        foreach ($children as $artifact) {
            array_push($assets, ...$artifact->assets);
            array_push(
                $localPublicAssets,
                ...array_values(array_filter($artifact->hydration['local_public_assets'] ?? [], 'is_string')),
            );
        }
        $assets = array_values(array_unique($assets));
        sort($assets, SORT_STRING);
        $localPublicAssets = array_values(array_unique($localPublicAssets));
        sort($localPublicAssets, SORT_STRING);

        return new RenderArtifact(
            $this->markdown->renderContainer($node, $html, $context->sourceRoot, $context->sourceFile),
            $assets,
            [
                'renderer' => $node->renderer,
                'node_type' => 'container',
                'child_artifacts' => count($children),
                'local_public_assets' => $localPublicAssets,
            ],
            ['source' => $node->location()->toArray()],
        );
    }
}
