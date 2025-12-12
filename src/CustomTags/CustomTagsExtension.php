<?php

namespace Simai\Docara\CustomTags;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;

final class CustomTagsExtension implements ExtensionInterface
{
    public function __construct(private CustomTagRegistry $registry) {}

    /**
     * @param  EnvironmentBuilderInterface  $env
     */
    public function register(EnvironmentBuilderInterface $environment): void
    {

        $environment->addBlockStartParser(new UniversalBlockParser($this->registry), 200);

        $environment->addRenderer(CustomTagNode::class, new CustomTagRenderer($this->registry));
        $environment->addRenderer(CustomTagInline::class, new CustomTagRenderer($this->registry));
        $environment->addInlineParser(new UniversalInlineParser($this->registry), 150);
    }
}
