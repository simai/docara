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
            if (! $node instanceof CustomTagNode && ! $node instanceof CustomTagInline) {
                return '';
            }
            $spec = $this->registry->get($node->getType());

            if ($spec?->renderer instanceof \Closure && $node instanceof CustomTagNode) {
                return ($spec->renderer)($node, $childRenderer);
            }

            $htmlTag = $node->getHtmlTag()
                ?? $spec?->htmlTag
                ?? ($node instanceof CustomTagInline ? 'span' : 'div');

            $inner = $childRenderer->renderNodes($node->children());
            if ($inner === '') {
                $meta = $node->getMeta();
                if (isset($meta['innerRaw'])) {
                    $inner = $meta['innerRaw'];
                }
                if ($inner === '' && $node instanceof CustomTagNode && isset($meta['raw'])) {
                    $inner = $meta['raw'];
                }
            }

            return new \League\CommonMark\Util\HtmlElement(
                $htmlTag,
                $node->getAttrs(),
                $inner
            );
        }
    }
