<?php

namespace Simai\Docara\Parsers;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\SmartPunct\SmartPunctExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

class CommonMarkParser implements MarkdownParserContract
{
    private MarkdownConverter $converter;

    public function __construct()
    {
        $commonmark = data_get(app('config')->all(), 'commonmark');
        $environment = new Environment(data_get($commonmark, 'config', []));

        $environment->addExtension(new CommonMarkCoreExtension);

        $extensions = is_array($commonmark) && array_key_exists('extensions', $commonmark) ? $commonmark['extensions'] : [
            new AttributesExtension,
            new SmartPunctExtension,
            new StrikethroughExtension,
            new TableExtension,
        ];

        collect($extensions)->map(fn ($extension) => $environment->addExtension($extension));

        collect(
            data_get($commonmark, 'renderers')
        )->map(fn ($renderer, $nodeClass) => $environment->addRenderer($nodeClass, $renderer));

        $this->converter = new MarkdownConverter($environment);
    }

    public function parse(string $text)
    {
        throw new \RuntimeException('commonmark parse');
        return $this->converter->convert($text);
    }
}
