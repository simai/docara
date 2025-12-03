<?php

namespace Simai\Docara\CustomTags;

use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

/**
 * Minimal inline parser to support same-line custom tags: !type ... !endtype
 * This is opt-in and only runs if CustomTagsExtension registers it.
 */
class UniversalInlineParser implements InlineParserInterface
{
    public function __construct(private CustomTagRegistry $registry) {}

    public function getCharacters(): array
    {
        return ['!'];
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();
        $remaining = $cursor->getRemainder();

        foreach ($this->registry->getSpecs() as $spec) {
            if ($spec->closeRegex === null) {
                continue;
            }

            $pattern = '/^!' . preg_quote($spec->type, '/') . '(?:\s+(?<attrs>[^!]+?))?\s+(?<inner>.*?)\s*!end' . preg_quote($spec->type, '/') . '/u';

            if (! preg_match($pattern, $remaining, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            if ($matches[0][1] !== 0) {
                continue;
            }

            $matchedText = $matches[0][0];
            $attrStr = $matches['attrs'][0] ?? '';
            $innerContent = $matches['inner'][0] ?? '';

            $userAttrs = Attrs::parseOpenLine($attrStr);
            $attrs = Attrs::merge($spec->baseAttrs, $userAttrs);

            $node = new CustomTagInline(
                $spec->type,
                $attrs,
                ['openMatch' => $matches, 'attrStr' => $attrStr]
            );

            if ($spec->attrsFilter instanceof \Closure) {
                $node->setAttrs(($spec->attrsFilter)($node->getAttrs(), $node->getMeta()));
            }

            if ($innerContent !== '') {
                $node->appendChild(new Text($innerContent));
            }

            $inlineContext->getContainer()->appendChild($node);
            $cursor->advanceBy(strlen($matchedText));

            return true;
        }

        return false;
    }

    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::oneOf('!');
    }
}
