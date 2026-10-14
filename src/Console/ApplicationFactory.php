<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Composer\InstalledVersions;
use Simai\Docara\Application\ArtifactTestService;
use Simai\Docara\Application\CapabilitiesService;
use Simai\Docara\Application\DesignAtlasService;
use Simai\Docara\Application\DiscoveryService;
use Simai\Docara\Application\ProjectUpgradeService;
use Simai\Docara\Application\QaService;
use Simai\Docara\Application\ScaffoldService;
use Simai\Docara\Application\ValidationService;
use Simai\Docara\Documentation\DocumentationStatusService;
use Simai\Docara\File\Filesystem;
use Simai\Docara\File\ProjectFilesystemGuard;
use Simai\Docara\I18n\TranslationStatusService;
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
        // The builder resolves the Composition Recipe runtime when a build
        // starts: DOCARA_SIMAI_UI_ROOT or SIMAI_UI_ROOT when set, else the
        // runtime Docara bundles. Commands that do not build need no Node.js.
        $builder = new PortableSiteBuilder($files, new PortableMarkdownRenderer);
        $version = self::version();

        $application = new Application('Docara', $version);
        $discovery = new DiscoveryService;
        $preview = new PreviewKernel($builder, $files, $writes);
        $application->addCommands([
            (new CapabilitiesCommand(new CapabilitiesService))->setBase($base),
            (new InitCommand($files, new PortableProjectInitializer($files)))->setBase($base),
            (new UpgradeCommand(new ProjectUpgradeService($files)))->setBase($base),
            (new UpdateCommand(new PortableProjectUpdater($files)))->setBase($base),
            (new BuildCommand($builder))->setBase($base),
            (new TranslationsCommand(new TranslationStatusService))->setBase($base),
            (new DocumentationCommand(new DocumentationStatusService))->setBase($base),
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

    /**
     * The installed package version; in a checkout of Docara itself (where
     * Composer knows no version for the root package) the VERSION file.
     */
    public static function version(): string
    {
        $file = dirname(__DIR__, 2) . '/VERSION';
        $recorded = is_file($file) ? trim((string) file_get_contents($file)) : '';
        $isRoot = (InstalledVersions::getRootPackage()['name'] ?? null) === 'simai/docara';
        if (! $isRoot && InstalledVersions::isInstalled('simai/docara')) {
            $installed = InstalledVersions::getPrettyVersion('simai/docara');
            if (is_string($installed) && $installed !== '') {
                return $installed;
            }
        }

        return $recorded !== '' ? $recorded : 'dev';
    }
}
