<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Composer\InstalledVersions;
use Simai\Docara\File\Filesystem;
use Simai\Docara\PortableSite\PortableHtmlRenderer;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableProjectInitializer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Symfony\Component\Console\Application;

final class ApplicationFactory
{
    public static function create(?string $base = null): Application
    {
        $base ??= getcwd() ?: '.';
        $files = new Filesystem;
        $html = new PortableHtmlRenderer;
        $builder = new PortableSiteBuilder($files, new PortableMarkdownRenderer, $html);
        $version = InstalledVersions::isInstalled('simai/docara')
            ? (InstalledVersions::getPrettyVersion('simai/docara') ?? 'dev')
            : 'dev';

        $application = new Application('Docara', $version);
        $application->addCommands([
            (new InitCommand($files, new PortableProjectInitializer($files)))->setBase($base),
            (new BuildCommand($builder))->setBase($base),
            (new ServeCommand)->setBase($base),
            (new VerifyStaticCommand)->setBase($base),
        ]);

        return $application;
    }
}
