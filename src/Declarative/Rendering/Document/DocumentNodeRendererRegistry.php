<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\Document;

use Simai\Docara\Declarative\Plan\ResolvedBlockPlan;
use Simai\Docara\Declarative\Rendering\RenderArtifact;
use Simai\Docara\Declarative\Rendering\SafeElementRenderer;
use Simai\Docara\Declarative\Rendering\SmartRenderer;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final readonly class DocumentNodeRendererRegistry
{
    /** @var array<string, DocumentNodeRenderer> */
    private array $renderers;

    /** @param iterable<DocumentNodeRenderer> $renderers */
    public function __construct(iterable $renderers)
    {
        $indexed = [];
        foreach ($renderers as $renderer) {
            $key = $renderer->renderer();
            if ($key === '' || isset($indexed[$key])) {
                throw new \LogicException('DECLARATIVE_DOCUMENT_RENDERER_DUPLICATED:' . $key);
            }
            $indexed[$key] = $renderer;
        }
        if ($indexed === []) {
            throw new \LogicException('DECLARATIVE_DOCUMENT_RENDERERS_REQUIRED');
        }
        $this->renderers = $indexed;
    }

    public static function bundled(
        PortableMarkdownRenderer $markdown,
        SmartRenderer $smart,
        SafeElementRenderer $elements,
    ): self {
        return new self([
            new MarkdownDocumentNodeRenderer($markdown),
            new SmartDocumentNodeRenderer($smart),
            new ElementDocumentNodeRenderer($elements),
        ]);
    }

    /** @param array<string, mixed> $payload */
    public function render(array $payload): RenderArtifact
    {
        $node = ResolvedBlockPlan::fromArray($payload);
        $renderer = $this->renderers[$node->renderer] ?? null;
        if ($renderer === null) {
            throw new PortableConfigurationException(
                'DECLARATIVE_DOCUMENT_RENDERER_UNSUPPORTED',
                "Document renderer [{$node->renderer}] is not registered.",
            );
        }

        return $renderer->render($node);
    }

    /** @return list<string> */
    public function renderers(): array
    {
        return array_keys($this->renderers);
    }
}
