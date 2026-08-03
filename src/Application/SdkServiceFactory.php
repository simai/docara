<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

use Simai\Docara\File\Filesystem;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Simai\Docara\Preview\PreviewKernel;
use Simai\Docara\Preview\PreviewShell;

final class SdkServiceFactory
{
    public static function create(): SdkService
    {
        $files = new Filesystem;
        $builder = new PortableSiteBuilder($files, new PortableMarkdownRenderer);
        $preview = new PreviewKernel($builder, $files);

        return new SdkService(
            new DiscoveryService,
            new ScaffoldService,
            new ValidationService,
            new ArtifactTestService($preview),
            new QaService($preview, new PreviewShell($files)),
        );
    }
}
