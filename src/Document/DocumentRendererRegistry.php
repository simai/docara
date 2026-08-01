<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

use Simai\Docara\Declarative\Rendering\RenderArtifact;
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

    public static function bundled(PortableMarkdownRenderer $markdown): self
    {
        return new self([
            new SourceDocumentNodeRenderer($markdown),
            new ComponentDocumentNodeRenderer($markdown->componentGateway()),
            new ComponentBlockDocumentNodeRenderer($markdown->componentGateway(), $markdown),
        ]);
    }

    /** @return array{document:RenderArtifact,components:list<RenderArtifact>} */
    public function render(DocumentIr $document, DocumentRenderContext $context): array
    {
        $componentArtifacts = [];
        $assets = [];
        foreach ($document->allNodes() as $node) {
            if ($node instanceof ComponentNode || $node instanceof ComponentBlockNode) {
                $artifact = $this->renderer($node)->render($node, $context);
                $componentArtifacts[] = $artifact;
                array_push($assets, ...$artifact->assets);
            }
        }
        $html = '';
        foreach ($document->nodes as $node) {
            $artifact = $this->renderer($node)->render($node, $context);
            $html .= $artifact->html;
            array_push($assets, ...$artifact->assets);
        }
        $assets = array_values(array_unique($assets));
        sort($assets, SORT_STRING);

        return [
            'document' => new RenderArtifact(
                $html,
                $assets,
                [
                    'renderer_registry' => $this->types(),
                    'component_calls' => count($componentArtifacts),
                ],
                ['source' => $document->source],
            ),
            'components' => $componentArtifacts,
        ];
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
