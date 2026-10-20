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
        $this->filesystem->put($target . '/composer.json', json_encode(['require' => ['simai/docara' => '^3.0']], JSON_THROW_ON_ERROR));
        $this->filesystem->put($target . '/composer.lock', json_encode(['packages' => [['name' => 'simai/docara', 'version' => '3.0.0']], 'packages-dev' => []], JSON_THROW_ON_ERROR));

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
        $this->assertStringContainsString('Next, build, check and preview the site', $console->getDisplay());
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
    public function portable_init_treats_version_control_metadata_as_empty_but_not_other_files(): void
    {
        $target = $this->tmpPath('fresh-repository');
        $this->filesystem->ensureDirectoryExists($target . '/.git');
        file_put_contents($target . '/.gitignore', "/vendor/\n");
        file_put_contents($target . '/.DS_Store', '');

        [$status, $console] = $this->executeInit(['path' => $target], false);

        $this->assertSame(Command::SUCCESS, $status, $console->getDisplay());
        $this->assertFileExists($target . '/docara.json');
        $this->assertSame("/vendor/\n", file_get_contents($target . '/.gitignore'));
        $this->assertStringContainsString('cd ' . $target, $console->getDisplay());
        $this->assertStringContainsString('docara build production', $console->getDisplay());

        $occupied = $this->tmpPath('occupied');
        $this->filesystem->ensureDirectoryExists($occupied . '/.git');
        file_put_contents($occupied . '/notes.txt', 'mine');

        [$status, $console] = $this->executeInit(['path' => $occupied], false);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('requires an empty target directory', $console->getDisplay());
        $this->assertFileDoesNotExist($occupied . '/docara.json');
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
        $this->assertSame('ui-32c7a64e4575', $lock['runtime']['pair_id']);
        $this->assertSame('32c7a64e457583eaf5a1860590001217a86b5af9', $lock['runtime']['ui']['commit']);
        $this->assertSame('32c7a64e457583eaf5a1860590001217a86b5af9', $lock['runtime']['ui_smart']['commit']);
        $this->assertSame('efccc737657634122270ce7281a43878baa78410', $lock['runtime']['framework_registry']['source']['commit']);
        $this->assertSame('4b055d09926fec4c32f2ae43b2e7e0a6f64d7663', $lock['manifests']['ui.button']['provider_revision']);
        $this->assertSame('235e049df3d4493aebe39098fbc54e5d86cbd98edd2f9996258497900f6d8130', $lock['manifests']['ui.button']['sha256']);
        $this->assertSame('056329bb80128310728d279ce6c2374413d40b2bf81a087627f80616d2be99a7', $lock['manifests']['ui.alert']['sha256']);
        $this->assertSame('docara.framework_asset_projection.v1', $lock['asset_projection']['schema']);
        $this->assertSame('_docara/framework-runtime', $lock['asset_projection']['mount']);
        $this->assertSame('simai/ui', $lock['asset_projection']['source']['provider']);
        $this->assertSame(
            $lock['runtime']['ui_smart']['commit'],
            $lock['asset_projection']['source']['revision'],
        );
        $this->assertSame([
            'smart/alert/js/alert.js' => 'c2c80109299ad4483a85c69cf73eb20e5b906cc093c4527af59b7e4a67af3839',
            'smart/buttons/js/buttons.js' => '16599394e25a25bbf8f1f18fca15ce869332ad83fe2c8f7f9f9d66ae131a1c25',
            'smart/close/js/close.js' => '298d648260c74e2c7d966f18c6665a91d823bc3ae83e20d3cb555c6c8a327e81',
            'smart/icon-buttons/css/icon-buttons.css' => 'fa03db4eadc3b43d08f42226d942cb608f72f1af16be35538df44fd7777c1fb1',
            'smart/icon-buttons/js/icon-buttons.js' => '8a86efe323624af6aeed20ba355e50c8e2ccbd99c82055b02932a7b6ebe6b773',
            'smart/icons/js/icons.js' => '818b2b59ba1e1585f9b941aefbdbd9700e0260cdf804ed54a6bac37160c5d60e',
            'smart/modal/js/modal.js' => '59697096c20f66ff35335a2ecde9fe4ec33cf51a5d6fd37c2fa4b7c606acebe4',
        ], array_map(
            static fn (array $record): string => $record['sha256'],
            $lock['asset_projection']['files'],
        ));
        $this->assertSame('docara.framework_dynamic_asset_projection.v1', $lock['dynamic_asset_projection']['schema']);
        $this->assertSame('_docara/framework-runtime', $lock['dynamic_asset_projection']['mount']);
        $this->assertSame($lock['runtime']['ui_smart']['commit'], $lock['dynamic_asset_projection']['source']['revision']);
        $this->assertCount(68, $lock['dynamic_asset_projection']['files']);
        $this->assertSame(
            '8f1df268efe7f587a0e2b037f485aec7baca2bdc637ea949211fea6a219b8a72',
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

    #[Test]
    public function init_without_options_creates_the_minimal_english_starter(): void
    {
        [$status, $console] = $this->executeInit([], showcase: false);

        $this->assertSame(Command::SUCCESS, $status, $console->getDisplay());
        $this->assertStringContainsString('Template: minimal, content language: en', $console->getDisplay());
        $site = $this->json('docara.json');
        $this->assertSame('en', $site['default_locale']);
        $this->assertSame('content/en', $site['content_root']);
        foreach (['index.md', 'writing.md', 'components.md', 'lang.json', 'section.json'] as $file) {
            $this->assertFileExists($this->tmpPath('content/en/' . $file));
        }
        foreach (['simai-framework.lock.json', 'redirects.json', 'assets/docara-mark.svg', 'assets/favicon.ico', 'contracts/generated/framework-contract-registry.json', '.docara/engine/ownership.json'] as $file) {
            $this->assertFileExists($this->tmpPath($file));
        }
        $this->assertFileEquals(dirname(__DIR__) . '/stubs/portable/simai-framework.lock.json', $this->tmpPath('simai-framework.lock.json'));
        $this->assertDirectoryDoesNotExist($this->tmpPath('content/ru'));
        $this->assertDirectoryDoesNotExist($this->tmpPath('smart'));
        $this->assertStringContainsString(':::example', (string) file_get_contents($this->tmpPath('content/en/writing.md')));
        $this->assertStringContainsString(':::ui.button', (string) file_get_contents($this->tmpPath('content/en/components.md')));
        foreach (glob($this->tmpPath('content/en/*')) ?: [] as $file) {
            $this->assertDoesNotMatchRegularExpression('/[А-Яа-яЁё]/u', (string) file_get_contents($file), $file);
        }
    }

    #[Test]
    public function the_minimal_starter_is_also_written_in_russian(): void
    {
        [$status, $console] = $this->executeInit(['--template' => 'minimal', '--lang' => 'ru'], showcase: false);

        $this->assertSame(Command::SUCCESS, $status, $console->getDisplay());
        $this->assertSame('ru', $this->json('docara.json')['default_locale']);
        $this->assertFileExists($this->tmpPath('content/ru/index.md'));
        $this->assertFileExists($this->tmpPath('content/ru/lang.json'));
        $this->assertDirectoryDoesNotExist($this->tmpPath('content/en'));
        $this->assertSame(
            array_map('basename', glob(dirname(__DIR__) . '/stubs/minimal/en/content/en/*') ?: []),
            array_map('basename', glob(dirname(__DIR__) . '/stubs/minimal/ru/content/ru/*') ?: []),
            'Both languages of the minimal starter have the same pages.',
        );
    }

    #[Test]
    public function an_unavailable_template_language_fails_clearly_and_changes_nothing(): void
    {
        [$status, $console] = $this->executeInit(['--template' => 'showcase', '--lang' => 'en'], showcase: false);

        $this->assertSame(Command::FAILURE, $status);
        $display = preg_replace('/\s+/', ' ', $console->getDisplay());
        $this->assertStringContainsString('The "showcase" template is available only in: ru.', $display);
        $this->assertStringContainsString('--template=minimal --lang=en', $display);
        $this->assertSame([], array_values(array_diff(scandir($this->tmp) ?: [], ['.', '..'])));

        [$status, $console] = $this->executeInit(['--template' => 'blog'], showcase: false);
        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('Unknown template "blog"', $console->getDisplay());
        $this->assertSame([], array_values(array_diff(scandir($this->tmp) ?: [], ['.', '..'])));
    }

    /**
     * Without explicit options the helper initializes the Russian showcase,
     * the starter most assertions in this class describe.
     *
     * @param  array<string, mixed>  $arguments
     * @return array{int, CommandTester}
     */
    private function executeInit(array $arguments, bool $showcase = true): array
    {
        if ($showcase) {
            $arguments += ['--template' => 'showcase', '--lang' => 'ru'];
        }
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
