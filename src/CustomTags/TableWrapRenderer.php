<?php

namespace Simai\Docara\CustomTags;

use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

class TableWrapRenderer implements NodeRendererInterface
{
    public function render($node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        if (! ($node instanceof Table)) {
            throw new \InvalidArgumentException;
        }

        $attrs = $node->data->get('attributes') ?? [];

        $table = new HtmlElement(
            'table',
            $attrs,
            $childRenderer->renderNodes($node->children())
        );

        if (isset($attrs['class']) && str_contains($attrs['class'], 'table')) {
            return new HtmlElement(
                'div',
                ['class' => 'dc-table-wrap'],
                $table
            );
        }

        return $table;
    }
}
