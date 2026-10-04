<?php

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Console\InitCommand;
use Simai\Docara\File\Filesystem;
use Simai\Docara\PortableSite\PortableProjectInitializer;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class PortableInitCommandTest extends TestCase
{
    #[Test]
    public function portable_init_accepts_only_a_verified_project_local_composer_runtime(): void
    {
        $target = $this->tmpPath('project-local');
        $this->filesystem->ensureDirectoryExists($target . '/vendor/bin');
        $this->filesystem->put($target . '/vendor/bin/docara', "#!/usr/bin/env php\n");
        $this->filesystem->put($target . '/composer.json', json_encode(['require' => ['simai/docara' => '^2.0']], JSON_THROW_ON_ERROR));
        $this->filesystem->put($target . '/composer.lock', json_encode(['packages' => [['name' => 'simai/docara', 'version' => '2.4.0']], 'packages-dev' => []], JSON_THROW_ON_ERROR));

        $command = new InitCommand($this->filesystem, new PortableProjectInitializer($this->filesystem));
        $command->setApplication(new Application);
        $command->setBase($target);
        $console = new CommandTester($command);

        self::assertSame(Command::SUCCESS, $console->execute([]), $console->getDisplay());
        self::assertFileExists($target . '/docara.json');
        self::assertFileExists($target . '/composer.lock');
        self::assertFileExists($target . '/vendor/bin/docara');
    }

    #[Test]
    public function portable_init_creates_only_the_json_and_markdown_site_surface(): void
    {
        [$status, $console] = $this->executeInit([]);

        $this->assertSame(Command::SUCCESS, $status, $console->getDisplay());
        $this->assertFileExists($this->tmpPath('docara.json'));
        $this->assertFileExists($this->tmpPath('redirects.json'));
        $this->assertFileExists($this->tmpPath('simai-framework.lock.json'));
        $this->assertFileExists($this->tmpPath('content/ru/guides/getting-started.md'));
        $this->assertFileExists($this->tmpPath('content/ru/guides/getting-started.page.json'));
        $this->assertFileExists($this->tmpPath('content/ru/guides/platform/configuration/layout.md'));
        $this->assertFileExists($this->tmpPath('assets/docara-mark.svg'));
        $this->assertFileExists($this->tmpPath('assets/favicon.ico'));
        $this->assertFileExists($this->tmpPath('content/ru/index.page.json'));
        $this->assertFileExists($this->tmpPath('content/ru/landing.md'));
        $this->assertFileExists($this->tmpPath('content/ru/landing.page.json'));
        $this->assertFileExists($this->tmpPath('.docara/engine/ownership.json'));
        $this->assertFileExists($this->tmpPath('.docara/engine/dependency-lock.json'));
        $this->assertFileExists($this->tmpPath('.docara/engine/framework-lock.json'));
        $ownership = $this->json('.docara/engine/ownership.json');
        $this->assertSame('docara.project_ownership.v1', $ownership['schema']);
        $this->assertSame(['.docara/engine/**'], $ownership['owners']['engine']);
        $this->assertContains('content/**', $ownership['owners']['project']);
        $this->assertContains('build_*/**', $ownership['owners']['generated']);

        $this->assertFileDoesNotExist($this->tmpPath('.env'));
        $this->assertFileDoesNotExist($this->tmpPath('config.php'));
        $this->assertDirectoryDoesNotExist($this->tmpPath('source'));
        $this->assertFileDoesNotExist($this->tmpPath('package.json'));
        $this->assertFileDoesNotExist($this->tmpPath('vite.config.js'));

        $this->assertStringContainsString('Docara project was initialized', $console->getDisplay());
    }

    #[Test]
    public function portable_init_accepts_a_relative_target_directory(): void
    {
        [$status, $console] = $this->executeInit(['path' => 'sites/my-docara']);

        $this->assertSame(Command::SUCCESS, $status, $console->getDisplay());
        $this->assertFileExists($this->tmpPath('sites/my-docara/docara.json'));
        $this->assertFileExists($this->tmpPath('sites/my-docara/content/ru/index.md'));
        $this->assertFileDoesNotExist($this->tmpPath('docara.json'));
        $this->assertStringContainsString('sites/my-docara', $console->getDisplay());
    }

    #[Test]
    public function portable_init_accepts_an_absolute_target_and_refuses_the_old_implicit_update(): void
    {
        $target = $this->tmpPath('absolute-target');

        [$status, $console] = $this->executeInit(['path' => $target]);
        $this->assertSame(Command::SUCCESS, $status, $console->getDisplay());

        $site = $target . '/docara.json';
        $contents = "user-owned\n";
        file_put_contents($site, $contents);

        [$updateStatus, $updateConsole] = $this->executeInit([
            'path' => $target,
            '--update' => true,
        ]);

        $this->assertSame(Command::FAILURE, $updateStatus, $updateConsole->getDisplay());
        $this->assertStringContainsString('implicit "init --update" workflow is disabled', $updateConsole->getDisplay());
        $this->assertSame($contents, file_get_contents($site));
    }

    #[Test]
    public function portable_init_rejects_a_target_path_that_is_a_file(): void
    {
        $target = $this->tmpPath('not-a-directory');
        file_put_contents($target, 'occupied');

        [$status, $console] = $this->executeInit(['path' => $target]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('Target path is not a directory', $console->getDisplay());
        $this->assertSame('occupied', file_get_contents($target));
    }

    #[Test]
    public function portable_fixture_uses_versioned_schema_ids_and_the_exact_framework_pair(): void
    {
        [$status] = $this->executeInit([]);
        $this->assertSame(Command::SUCCESS, $status);

        $site = $this->json('docara.json');
        $rootSection = $this->json('content/ru/section.json');
        $nestedSection = $this->json('content/ru/guides/section.json');
        $indexPage = $this->json('content/ru/index.page.json');
        $docsPage = $this->json('content/ru/guides/getting-started.page.json');
        $landingPage = $this->json('content/ru/landing.page.json');
        $lock = $this->json('simai-framework.lock.json');

        $this->assertSame('docara.site.v1', $site['schema']);
        $this->assertSame('current', $site['documentation_version']);
        $this->assertSame('redirects.json', $site['redirects_file']);
        $this->assertSame('Docara', $site['branding']['title']);
        $this->assertSame('compact', $site['branding']['mode']);
        $this->assertSame('large', $site['branding']['size']);
        $this->assertSame('assets/docara-mark.svg', $site['branding']['logo']);
        $this->assertArrayNotHasKey('logo_dark', $site['branding']);
        $this->assertSame('assets/favicon.ico', $site['branding']['favicon']);
        $this->assertSame('docara.section.v1', $rootSection['schema']);
        $this->assertSame('docara.section.v1', $nestedSection['schema']);
        $this->assertSame('docara.page.v1', $indexPage['schema']);
        $this->assertSame('docara.page.v1', $docsPage['schema']);
        $this->assertSame('docara.page.v1', $landingPage['schema']);
        $this->assertSame('docs', $docsPage['preset']);
        $this->assertSame('landing', $landingPage['preset']);
        $this->assertSame(10, $indexPage['navigation']['order']);
        $this->assertSame(20, $nestedSection['navigation']['order']);
        $this->assertSame(30, $landingPage['navigation']['order']);
        $this->assertTrue($landingPage['navigation']['hidden']);
        $this->assertFalse($landingPage['search']['enabled']);
        $this->assertFalse($landingPage['search']['indexed']);

        $landing = file_get_contents($this->tmpPath('content/ru/landing.md'));
        $this->assertIsString($landing);
        $this->assertStringContainsString(':::cta', $landing);
        $this->assertStringContainsString('[Начать работу](/guides/getting-started/)', $landing);
        $this->assertStringContainsString(':::features', $landing);
        $this->assertStringNotContainsString(':::ui.button', $landing);

        $this->assertSame('docara.framework_lock.v1', $lock['schema']);
        $this->assertSame('larena.ui.frontend_runtime_lock.v3', $lock['runtime']['schema']);
        $this->assertSame('ui-4f5e5067afeb-smart-7d932ed51408', $lock['runtime']['pair_id']);
        $this->assertSame('4f5e5067afeba3b5206fef83469b668cc2af6a34', $lock['runtime']['ui']['commit']);
        $this->assertSame('7d932ed5140813806a601db3f3ddc6f3840e4638', $lock['runtime']['ui_smart']['commit']);
        $this->assertSame('6dff03e4a15adc3dc979ecc765afeca3d0dc06e7', $lock['runtime']['framework_registry']['source']['commit']);
        $this->assertSame('4b055d09926fec4c32f2ae43b2e7e0a6f64d7663', $lock['manifests']['ui.button']['provider_revision']);
        $this->assertSame('3f7bef6937dff2e9efcdbd86a1039b02f7e137847fc92f3b7117f007bd75a858', $lock['manifests']['ui.button']['sha256']);
        $this->assertSame('b8093561b2d724d9c295d890e22ac19147e75f2b1417b0fd2f186cde3f349a80', $lock['manifests']['ui.alert']['sha256']);
        $this->assertSame('docara.framework_asset_projection.v1', $lock['asset_projection']['schema']);
        $this->assertSame('_docara/framework-runtime', $lock['asset_projection']['mount']);
        $this->assertSame('simai/ui-smart', $lock['asset_projection']['source']['provider']);
        $this->assertSame(
            $lock['runtime']['ui_smart']['commit'],
            $lock['asset_projection']['source']['revision'],
        );
        $this->assertSame([
            'smart/alert/js/alert.js' => '9fe722e48e98332e98951c73feababd3be85b3dfa574542bf2afa85fc4f23db2',
            'smart/buttons/js/buttons.js' => 'dac70e17c1891712bacc4648ad4a2dc04ca0ff0a0ac04fb2924b15417812da30',
            'smart/close/js/close.js' => 'c61d617e890154735506d705b698bead51b52599ee591135cc218268011167e6',
            'smart/icon-buttons/css/icon-buttons.css' => '8dcec6c3d61b82fe533da22728d76ba5a0ea5a1732f02da4dec09b97d51728fe',
            'smart/icon-buttons/js/icon-buttons.js' => '008095322f560328b00d46bbedc28637abc272110441f72afc73421d9a3b82b9',
            'smart/icons/js/icons.js' => '63c83e36e0eafbcdd72eddfb23c9fb6e0b6e584afc6f6c1f53f8414ec067fa94',
            'smart/modal/js/modal.js' => '1a9333f602d57398df77bf7b35522c6a85ec346964731da25397dbadcb7ee342',
        ], array_map(
            static fn (array $record): string => $record['sha256'],
            $lock['asset_projection']['files'],
        ));
        $this->assertSame('docara.framework_dynamic_asset_projection.v1', $lock['dynamic_asset_projection']['schema']);
        $this->assertSame('_docara/framework-runtime', $lock['dynamic_asset_projection']['mount']);
        $this->assertSame($lock['runtime']['ui_smart']['commit'], $lock['dynamic_asset_projection']['source']['revision']);
        $this->assertCount(55, $lock['dynamic_asset_projection']['files']);
        $this->assertSame(
            '39612c91712b6c5a7f4134a71e7297ce29bf6203fabd1c74a1968e1eac692a3e',
            $lock['dynamic_asset_projection']['files']['smart/inputs/js/inputs.js']['sha256'],
        );
        $this->assertSame('docara.framework_icon_projection.v1', $lock['icon_projection']['schema']);
        $this->assertSame(
            '50f0603134ce7b70b2d71b686cc13e8b57ccb74c',
            $lock['icon_projection']['source']['revision'],
        );
        $this->assertSame(
            '5c0be48d07803e6eb6a993ad441f6fc92340ee0da9d1b57cc348f62569947ae5',
            $lock['icon_projection']['files']['outlined']['sha256'],
        );

        $encodedLock = json_encode($lock, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('latest', strtolower($encodedLock));
        $this->assertNotSame('main', $lock['runtime']['ui']['tag']);
        $this->assertNotSame('main', $lock['runtime']['ui_smart']['tag']);
    }

    #[Test]
    public function portable_fixture_contains_json_component_directives_without_inventing_button_href(): void
    {
        [$status] = $this->executeInit([]);
        $this->assertSame(Command::SUCCESS, $status);

        $markdown = file_get_contents($this->tmpPath('content/ru/guides/getting-started.md'));
        $this->assertIsString($markdown);
        $this->assertMatchesRegularExpression('/:::ui\.alert\R\{.+\}\R:::/', $markdown);
        $this->assertMatchesRegularExpression('/:::ui\.button\R\{.+\}\R:::/', $markdown);
        $this->assertStringContainsString('## Что проверить', $markdown);
        $this->assertStringContainsString('### Следующий шаг', $markdown);
        $this->assertStringContainsString('[следующий шаг](#следующий-шаг)', $markdown);

        preg_match('/:::ui\.button\R(\{.+\})\R:::/', $markdown, $matches);
        $button = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('Продолжить', $button['text']);
        $this->assertArrayNotHasKey('href', $button);
    }

    #[Test]
    public function deprecated_init_update_never_restores_or_changes_project_owned_files(): void
    {
        [$status] = $this->executeInit([]);
        $this->assertSame(Command::SUCCESS, $status);

        $page = $this->tmpPath('content/ru/index.md');
        $contents = "# User owned\n";
        file_put_contents($page, $contents);
        unlink($this->tmpPath('content/ru/landing.page.json'));

        [$updateStatus, $console] = $this->executeInit(['--update' => true]);

        $this->assertSame(Command::FAILURE, $updateStatus, $console->getDisplay());
        $this->assertSame($contents, file_get_contents($page));
        $this->assertFileDoesNotExist($this->tmpPath('content/ru/landing.page.json'));
    }

    #[Test]
    public function portable_update_refuses_to_implicitly_migrate_a_legacy_site(): void
    {
        $this->createSource([
            'config.php' => '<?php return [];',
            'source' => [
                'docs' => [
                    'index.md' => '# Legacy',
                ],
            ],
        ]);

        [$status, $console] = $this->executeInit(['--update' => true]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('Refusing to migrate an existing legacy site implicitly', $console->getDisplay());
        $this->assertFileDoesNotExist($this->tmpPath('docara.json'));
        $this->assertSame('# Legacy', file_get_contents($this->tmpPath('source/docs/index.md')));
    }

    #[Test]
    #[DataProvider('partialPortableMarkerProvider')]
    public function portable_update_refuses_every_partial_portable_marker_on_a_legacy_site(string $marker): void
    {
        $this->createSource([
            'config.php' => '<?php return [];',
            'source' => [
                'docs' => [
                    'index.md' => '# Legacy',
                ],
            ],
        ]);

        if ($marker === 'content') {
            $this->filesystem->ensureDirectoryExists($this->tmpPath($marker));
        } else {
            file_put_contents($this->tmpPath($marker), "{}\n");
        }

        [$status, $console] = $this->executeInit(['--update' => true]);

        $this->assertSame(Command::FAILURE, $status, $console->getDisplay());
        $this->assertStringContainsString('Refusing to migrate an existing legacy site implicitly', $console->getDisplay());
        $this->assertSame('# Legacy', file_get_contents($this->tmpPath('source/docs/index.md')));
        foreach (['docara.json', 'simai-framework.lock.json', 'content'] as $portableMarker) {
            $this->assertSame(
                $portableMarker === $marker,
                $this->filesystem->exists($this->tmpPath($portableMarker)),
                "Portable update unexpectedly changed marker [$portableMarker] for partial marker [$marker].",
            );
        }
    }

    /** @return array<string, array{string}> */
    public static function partialPortableMarkerProvider(): array
    {
        return [
            'site configuration only' => ['docara.json'],
            'framework lock only' => ['simai-framework.lock.json'],
            'content directory only' => ['content'],
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{int, CommandTester}
     */
    private function executeInit(array $arguments): array
    {
        $files = new Filesystem;
        $command = new InitCommand($files, new PortableProjectInitializer($files));
        $command->setApplication(new Application);
        $command->setBase($this->tmp);
        $console = new CommandTester($command);
        $status = $console->execute($arguments);

        return [$status, $console];
    }

    /**
     * @return array<string, mixed>
     */
    private function json(string $relative): array
    {
        return json_decode(
            file_get_contents($this->tmpPath($relative)),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
