<?php

namespace Simai\Docara\CustomTags;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final readonly class CustomTagRenderer implements NodeRendererInterface
{
    public function __construct(private CustomTagRegistry $registry) {}

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): mixed
    {
        if (! $node instanceof CustomTagNode) {
            return '';
        }
        $spec = $this->registry->get($node->getType());

        if ($spec?->renderer instanceof \Closure) {
            return ($spec->renderer)($node, $childRenderer);
        }

        return new \League\CommonMark\Util\HtmlElement(
            $spec?->htmlTag ?? 'div',
            $node->getAttrs(),
            $childRenderer->renderNodes($node->children())
        );
    }
}
