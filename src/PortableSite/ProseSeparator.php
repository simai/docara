<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use League\CommonMark\Node\Inline\AbstractInline;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

/**
 * A bullet standing alone between spaces in prose, as in a meta line under a
 * heading («`appearance` • по умолчанию `default`»), is a separator: the
 * shell draws it smaller and in the caption colour, and readers of the
 * accessibility tree skip it. Code spans and fences keep a plain bullet.
 */
final class ProseSeparator extends AbstractInline implements InlineParserInterface, NodeRendererInterface
{
    public const CHARACTER = '•';

    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::string(self::CHARACTER);
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();
        $before = $cursor->peek(-1);
        $after = $cursor->peek(mb_strlen(self::CHARACTER));
        if ($before !== ' ' || $after !== ' ') {
            return false;
        }
        $cursor->advanceBy(mb_strlen(self::CHARACTER));
        $inlineContext->getContainer()->appendChild(new self);

        return true;
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        return new HtmlElement('span', ['class' => 'docara-sep', 'aria-hidden' => 'true'], self::CHARACTER);
    }
}
