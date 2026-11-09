<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;

/**
 * Renders inline code as CommonMark does, and marks a long piece so it may
 * wrap on a narrow screen.
 *
 * The shell keeps inline code on one line, so a token such as icon-start
 * never breaks at its hyphen. A long path, URN or class name kept on one
 * line is wider than a phone and widens the whole page; such a piece gets
 * data-docara-code-wrap, and the shell lets it wrap where a line allows and,
 * where a part is still too long, inside the token. A heading's larger type
 * reaches the edge sooner, so its limit is half the prose one.
 */
final class PortableInlineCodeRenderer implements NodeRendererInterface
{
    public const WRAP_ATTRIBUTE = 'data-docara-code-wrap';

    /** Characters a piece of inline code in prose keeps on one line. */
    public const PROSE_LIMIT = 32;

    /** Characters a piece of inline code in a heading keeps on one line. */
    public const HEADING_LIMIT = 16;

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        Code::assertInstanceOf($node);

        $attributes = $node->data->get('attributes');
        $literal = $node->getLiteral();
        if (self::wraps($literal, self::insideHeading($node))) {
            $attributes[self::WRAP_ATTRIBUTE] = true;
        }

        return new HtmlElement('code', $attributes, Xml::escape($literal));
    }

    /** The opening <code> tag for a piece of text the build writes as code itself. */
    public static function openingTag(string $text, bool $heading = false): string
    {
        return self::wraps($text, $heading) ? '<code ' . self::WRAP_ATTRIBUTE . '>' : '<code>';
    }

    public static function wraps(string $text, bool $heading = false): bool
    {
        return mb_strlen(trim($text), 'UTF-8') > ($heading ? self::HEADING_LIMIT : self::PROSE_LIMIT);
    }

    private static function insideHeading(Node $node): bool
    {
        for ($parent = $node->parent(); $parent !== null; $parent = $parent->parent()) {
            if ($parent instanceof Heading) {
                return true;
            }
        }

        return false;
    }
}
