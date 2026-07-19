<?php

declare(strict_types=1);

namespace Simai\Docara\Markdown;

use InvalidArgumentException;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

final readonly class DirectiveBlockStartParser implements BlockStartParserInterface
{
    public const PORTABLE = 'portable';

    public const FRAMEWORK = 'framework';

    public function __construct(private string $family)
    {
        if (! in_array($family, [self::PORTABLE, self::FRAMEWORK], true)) {
            throw new InvalidArgumentException("Unknown directive family [$family].");
        }
    }

    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($cursor->isIndented() || $parserState->getParagraphContent() !== null) {
            return BlockStart::none();
        }

        // Both directive families must become CommonMark block boundaries.
        // The inspector filters the requested family after pairing, but a
        // foreign-family block still prevents an adjacent opener from being
        // swallowed as paragraph continuation.
        $opening = self::matchOpening($cursor->getLine());
        if ($opening === null) {
            return BlockStart::none();
        }

        $cursor->advanceToEnd();
        $block = new DirectiveBlock($opening['name'], $opening['fence_length']);

        return BlockStart::of(new DirectiveBlockContinueParser($block))->at($cursor);
    }

    /** @return array{name: string, fence_length: int, family: string}|null */
    public static function matchOpening(string $line): ?array
    {
        $pattern = '/^(:{3,})(card|steps|cta|features|ui\.[a-z][a-z0-9._-]*)[ \t]*$/u';
        if (preg_match($pattern, $line, $match) !== 1) {
            return null;
        }

        return [
            'name' => $match[2],
            'fence_length' => strlen($match[1]),
            'family' => str_starts_with($match[2], 'ui.') ? self::FRAMEWORK : self::PORTABLE,
        ];
    }
}
