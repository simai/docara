<?php

declare(strict_types=1);

namespace Simai\Docara\Markdown;

use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;

final class DirectiveBlockContinueParser extends AbstractBlockContinueParser
{
    public function __construct(private readonly DirectiveBlock $block) {}

    public function getBlock(): AbstractBlock
    {
        return $this->block;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): null
    {
        return null;
    }
}
