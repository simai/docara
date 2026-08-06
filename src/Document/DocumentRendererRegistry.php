<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

use Simai\Docara\Declarative\Rendering\RenderArtifact;
use Simai\Docara\Declarative\Rendering\SmartRenderer;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final readonly class DocumentRendererRegistry
{
    /** @var array<string, DocumentNodeRenderer> */
    private array $renderers;

    /** @param iterable<DocumentNodeRenderer> $renderers */
    public function __construct(iterable $renderers)
    {
        $indexed = [];
        foreach ($renderers as $renderer) {
            foreach ($renderer->types() as $type) {
                if ($type === '' || isset($indexed[$type])) {
                    throw new \LogicException('DOCUMENT_IR_RENDERER_DUPLICATE:' . $type);
                }
                $indexed[$type] = $renderer;
            }
        }
        $this->renderers = $indexed;
    }

    public static function bundled(PortableMarkdownRenderer $markdown, ?SmartRenderer $smartRenderer = null): self
    {
        $smartRenderer ??= new SmartRenderer;

        return new self([
            new SourceDocumentNodeRenderer($markdown),
            new ComponentDocumentNodeRenderer($markdown->componentGateway()),
            new ComponentBlockDocumentNodeRenderer($markdown->componentGateway(), $markdown),
            new SmartComponentDocumentNodeRenderer($markdown->componentGateway(), $smartRenderer),
            new ContainerDocumentNodeRenderer($markdown),
        ]);
    }

    /** @return array{document:RenderArtifact,components:list<RenderArtifact>} */
    public function render(DocumentIr $document, DocumentRenderContext $context): array
    {
        $componentArtifacts = [];
        $assets = [];
        $localPublicAssets = [];
        $html = '';
        foreach ($document->nodes as $node) {
            [$artifact, $nestedComponents] = $this->renderTree($node, $context);
            $html .= $artifact->html;
            array_push($assets, ...$artifact->assets);
            array_push(
                $localPublicAssets,
                ...array_values(array_filter($artifact->hydration['local_public_assets'] ?? [], 'is_string')),
            );
            array_push($componentArtifacts, ...$nestedComponents);
        }
        $assets = array_values(array_unique($assets));
        sort($assets, SORT_STRING);
        $localPublicAssets = array_values(array_unique($localPublicAssets));
        sort($localPublicAssets, SORT_STRING);

        return [
            'document' => new RenderArtifact(
                $html,
                $assets,
                [
                    'renderer_registry' => $this->types(),
                    'component_calls' => count($componentArtifacts),
                    'local_public_assets' => $localPublicAssets,
                ],
                ['source' => $document->source],
            ),
            'components' => $componentArtifacts,
        ];
    }

    /** @return array{0:RenderArtifact,1:list<RenderArtifact>} */
    private function renderTree(DocumentNode $node, DocumentRenderContext $context): array
    {
        if ($node instanceof ContainerNode) {
            $children = [];
            $components = [];
            foreach ($node->children() as $child) {
                [$artifact, $childComponents] = $this->renderTree($child, $context);
                $children[] = $artifact;
                array_push($components, ...$childComponents);
            }
            $renderer = $this->renderer($node);
            if (! $renderer instanceof ContainerDocumentNodeRenderer) {
                throw new \LogicException('DOCUMENT_CONTAINER_RENDERER_INVALID');
            }

            return [$renderer->renderChildren($node, $children, $context), $components];
        }

        $components = [];
        foreach ($node->children() as $child) {
            [, $childComponents] = $this->renderTree($child, $context);
            array_push($components, ...$childComponents);
        }
        try {
            $artifact = $this->renderer($node)->render($node, $context);
        } catch (PortableConfigurationException $exception) {
            if ($exception->hasFileLocation()) {
                throw $exception;
            }
            $location = $node->location();
            throw new PortableConfigurationException(
                $exception->errorCode,
                $exception->getMessage() . ' Source [' . $location->label() . '].',
                $exception,
                $location->file,
                '/document/node',
                $location->line,
                $location->column,
            );
        }
        if ($node instanceof ComponentNode || $node instanceof ComponentBlockNode || $node instanceof SmartComponentNode) {
            $components[] = $artifact;
        }

        return [$artifact, $components];
    }

    /** @return list<string> */
    public function types(): array
    {
        return array_keys($this->renderers);
    }

    private function renderer(DocumentNode $node): DocumentNodeRenderer
    {
        return $this->renderers[$node->type()] ?? throw new PortableConfigurationException(
            'DOCUMENT_IR_RENDERER_UNKNOWN',
            "Document node renderer [{$node->type()}] is not registered at [{$node->location()->label()}].",
        );
    }
}
