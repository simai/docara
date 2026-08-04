<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Composer\InstalledVersions;
use Simai\Docara\Application\ArtifactTestService;
use Simai\Docara\Application\DesignAtlasService;
use Simai\Docara\Application\DiscoveryService;
use Simai\Docara\Application\QaService;
use Simai\Docara\Application\ScaffoldService;
use Simai\Docara\Application\ValidationService;
use Simai\Docara\File\Filesystem;
use Simai\Docara\File\ProjectFilesystemGuard;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableProjectInitializer;
use Simai\Docara\PortableSite\PortableProjectUpdater;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Simai\Docara\Preview\PreviewKernel;
use Simai\Docara\Preview\PreviewShell;
use Symfony\Component\Console\Application;

final class ApplicationFactory
{
    public static function create(?string $base = null): Application
    {
        $base ??= getcwd() ?: '.';
        $files = new Filesystem;
        $writes = new ProjectFilesystemGuard;
        $builder = new PortableSiteBuilder($files, new PortableMarkdownRenderer);
        $version = InstalledVersions::isInstalled('simai/docara')
            ? (InstalledVersions::getPrettyVersion('simai/docara') ?? 'dev')
            : 'dev';

        $application = new Application('Docara', $version);
        $discovery = new DiscoveryService;
        $preview = new PreviewKernel($builder, $files, $writes);
        $application->addCommands([
            (new InitCommand($files, new PortableProjectInitializer($files)))->setBase($base),
            (new UpdateCommand(new PortableProjectUpdater($files)))->setBase($base),
            (new BuildCommand($builder))->setBase($base),
            (new PreviewCommand($preview, new PreviewShell($files, $writes)))->setBase($base),
            (new ServeCommand)->setBase($base),
            (new VerifyStaticCommand)->setBase($base),
            (new DoctorCommand($discovery))->setBase($base),
            (new ListCommand($discovery))->setBase($base),
            (new InspectCommand($discovery))->setBase($base),
            (new SchemaCommand($discovery))->setBase($base),
            (new AtlasCommand(new DesignAtlasService))->setBase($base),
            (new ScaffoldCommand(new ScaffoldService($writes)))->setBase($base),
            (new ValidateCommand(new ValidationService))->setBase($base),
            (new TestArtifactCommand(new ArtifactTestService($preview)))->setBase($base),
            (new QaCommand(new QaService($preview, new PreviewShell($files, $writes), $writes)))->setBase($base),
        ]);

        return $application;
    }
}
