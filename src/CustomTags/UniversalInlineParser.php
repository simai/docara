<?php

namespace Simai\Docara\CustomTags;

use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

class UniversalInlineParser implements InlineParserInterface
{
    public function __construct(private CustomTagRegistry $registry) {}

    public function getCharacters(): array
    {
        return [];
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();
        $remaining = $cursor->getRemainder();

        foreach ($this->registry->getPatterns() as $entry) {
            $type = $entry['type'];
            $pattern = $entry['pattern'];

            if (preg_match($pattern, $remaining, $matches, PREG_OFFSET_CAPTURE)) {
                // Совпадение должно быть с начала строки
                if ($matches[0][1] !== 0) {
                    continue;
                }

                $matchedText = $matches[0][0];
                $innerContent = $matches[1][0] ?? '';

                $node = new CustomTagNode($type, $innerContent);
                $inlineContext->getContainer()->appendChild($node);

                $cursor->advanceBy(strlen($matchedText));

                return true;
            }
        }

        return false;
    }

    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::regex('.+');
    }
}
