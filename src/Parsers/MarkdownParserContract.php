<?php

namespace Simai\Docara\Parsers;

interface MarkdownParserContract
{
    public function parse(string $text);
}
