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

        //             $env->addInlineParser(new UniversalInlineParser($this->registry), 150);

        $environment->addBlockStartParser(new UniversalBlockParser($this->registry), 0);

        $environment->addRenderer(CustomTagNode::class, new CustomTagRenderer($this->registry));
    }
}
