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
        $this->assertSame('ui-994f9db407fa', $lock['runtime']['pair_id']);
        $this->assertSame('994f9db407fa5523feeafd1b8cb859fad34fa9c3', $lock['runtime']['ui']['commit']);
        $this->assertSame('994f9db407fa5523feeafd1b8cb859fad34fa9c3', $lock['runtime']['ui_smart']['commit']);
        $this->assertSame('698b188d654d074ab60abe81d888ab8501ff827f', $lock['runtime']['framework_registry']['source']['commit']);
        $this->assertSame('4b055d09926fec4c32f2ae43b2e7e0a6f64d7663', $lock['manifests']['ui.button']['provider_revision']);
        $this->assertSame('6444d8a10ac1a4cf5270fdc177f5f3b198521aab85485ccbcd067207931869e2', $lock['manifests']['ui.button']['sha256']);
        $this->assertSame('76698032d681edd38d6132fa18970dc4b1e4cd443798acb142cab504c9d88cb6', $lock['manifests']['ui.alert']['sha256']);
        $this->assertSame('docara.framework_asset_projection.v1', $lock['asset_projection']['schema']);
        $this->assertSame('_docara/framework-runtime', $lock['asset_projection']['mount']);
        $this->assertSame('simai/ui', $lock['asset_projection']['source']['provider']);
        $this->assertSame(
            $lock['runtime']['ui_smart']['commit'],
            $lock['asset_projection']['source']['revision'],
        );
        $this->assertSame([
            'smart/alert/js/alert.js' => 'aa5e8fcb82eb7b26675e0771c2b5c26579dedc59af923af42a4aa9ae7672e084',
            'smart/alert/js/alert.min.js' => '445a20dc1cc1e81f4aec2d41f2b3d221b9b80c797193bbaa1cdf5ecf1723539b',
            'smart/button-close/css/button-close.css' => '3bf84ff663eb381ed5da16bd496d628b9b709c0315d6c589059b424990a2296e',
            'smart/button-close/css/button-close.min.css' => '01ba4719c80b6fe911b091a7c05124b64eeece964e09c058ef8f9805daca546b',
            'smart/button-close/js/button-close.js' => '62e025a13dbe8d168c4226b83489bb65388a28483f89a098e6f93cbfb1b37142',
            'smart/button-close/js/button-close.min.js' => '42b62ab87275d5286284b37e43eb0ab9e94caa3f1b853c92df0b9a2d69f61d74',
            'smart/button-icon/css/button-icon.css' => '067e14feb6735e064f5ab969af9bc6709656a55f8880625354cf117e0e312771',
            'smart/button-icon/css/button-icon.min.css' => '60316696fdccd172fc0e6a4f77ed3e5fc0fc19056e8979259ed8e8db5fcb23b9',
            'smart/button-icon/js/button-icon.js' => 'e3cb41a730b9081b3c12bed9bfdb49920063366bf0526fb323b4dedffdf71aae',
            'smart/button-icon/js/button-icon.min.js' => '6032bf1baf47d2f4f7e0bb0777e0e7c9e1ccfcb3f4d89a3bba735035d822d9c4',
            'smart/button/js/button.js' => '58f9f26176512891f2e604186b306d353805c58946587a0aaab031f4c3e8c40f',
            'smart/button/js/button.min.js' => 'd7583b5f40a81c64ae5adda6823a12cb0e5ba318b3e5e188a2037e4745342162',
            'smart/icon/js/icon.js' => 'b9b9203c68c3fedeff6c65c4373d15d82e05ad7cbc5661f31d02107198dc6543',
            'smart/icon/js/icon.min.js' => 'a6ec88a30419a097931f08cc0601b5a76a6141adc68a4839d43e34d5feece8c4',
            'smart/modal/js/modal.js' => 'f98a527ec6feb5cc1de845afa73da0de579d5981a08e1a52e0a7282228f56462',
            'smart/modal/js/modal.min.js' => '5bbe5bc548f334d5408c3b9b0603488b177caaa2a9b7a77db4727e679fe5fef4',
        ], array_map(
            static fn (array $record): string => $record['sha256'],
            $lock['asset_projection']['files'],
        ));
        $this->assertSame('docara.framework_dynamic_asset_projection.v1', $lock['dynamic_asset_projection']['schema']);
        $this->assertSame('_docara/framework-runtime', $lock['dynamic_asset_projection']['mount']);
        $this->assertSame($lock['runtime']['ui_smart']['commit'], $lock['dynamic_asset_projection']['source']['revision']);
        $this->assertCount(323, $lock['dynamic_asset_projection']['files']);
        $this->assertSame(
            '94945b75e3f5f67a6ec805f1035a19ca46a1a9cb64fba7d8b20dd9988d50c393',
            $lock['dynamic_asset_projection']['files']['smart/input/js/input.js']['sha256'],
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
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->tmpPath('content/en'), \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $this->assertDoesNotMatchRegularExpression('/[А-Яа-яЁё]/u', (string) file_get_contents($file->getPathname()), $file->getPathname());
        }
        $this->assertDoesNotMatchRegularExpression('/[А-Яа-яЁё]/u', (string) file_get_contents($this->tmpPath('AGENTS.md')));
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

    /** @return array<string, array{string, string}> */
    public static function everyStarter(): array
    {
        return [
            'minimal, English' => ['minimal', 'en'],
            'minimal, Russian' => ['minimal', 'ru'],
            'showcase, Russian' => ['showcase', 'ru'],
        ];
    }

    #[Test]
    #[DataProvider('everyStarter')]
    public function every_starter_ships_the_documentation_standard(string $template, string $language): void
    {
        [$status, $console] = $this->executeInit(['--template' => $template, '--lang' => $language], showcase: false);

        $this->assertSame(Command::SUCCESS, $status, $console->getDisplay());
        $authoring = $this->json('docara.authoring.json');
        $this->assertSame('docara.authoring.v1', $authoring['schema']);
        $this->assertTrue($authoring['infer_profiles']);
        $this->assertSame(
            ['getting-started/**' => 'tutorial', 'how-to/**' => 'how_to', 'reference/**' => 'reference', 'explanation/**' => 'explanation'],
            array_column($authoring['rules'], 'profile', 'match'),
        );
        $pages = [
            'getting-started/first-page.md' => 'tutorial',
            'how-to/publish-the-site.md' => 'how_to',
            'reference/page-types.md' => 'reference',
            'explanation/page-types.md' => 'explanation',
        ];
        foreach ($pages as $page => $profile) {
            $source = (string) file_get_contents($this->tmpPath("content/{$language}/{$page}"));
            $this->assertStringContainsString("profile: {$profile}\n", $source, $page);
            $this->assertMatchesRegularExpression('/^description: "[^"]+"$/m', $source, $page);
            $this->assertStringContainsString($language === 'ru' ? '## См. также' : '## See also', $source, $page);
            $this->assertFileExists($this->tmpPath("content/{$language}/" . dirname($page) . '/section.json'));
        }

        $agents = (string) file_get_contents($this->tmpPath('AGENTS.md'));
        $lines = substr_count($agents, "\n");
        $this->assertGreaterThanOrEqual(60, $lines);
        $this->assertLessThanOrEqual(100, $lines);
        $this->assertStringContainsString("Pages are Markdown files in `content/{$language}/`", $agents);
        $this->assertStringContainsString("Page language: `{$language}`", $agents);
        foreach (['`how_to`', 'One topic per page', 'description', 'See also', 'alt text', 'No text inside images', 'scaffold page <route> --type <type>', 'validate page <route>', 'verify-static build_production'] as $needle) {
            $this->assertStringContainsString($needle, $agents);
        }
        $this->assertStringContainsString('Guide for AI assistants: AGENTS.md', $console->getDisplay());
        $this->assertFileDoesNotExist($this->tmpPath(PortableProjectInitializer::AGENTS_FALLBACK));
    }

    #[Test]
    public function an_existing_agents_file_is_kept_and_the_guide_goes_next_to_it(): void
    {
        file_put_contents($this->tmpPath('AGENTS.md'), "# Our own rules\n");

        [$status, $console] = $this->executeInit(['--template' => 'minimal', '--lang' => 'en'], showcase: false);

        $this->assertSame(Command::SUCCESS, $status, $console->getDisplay());
        $this->assertSame("# Our own rules\n", file_get_contents($this->tmpPath('AGENTS.md')));
        $this->assertStringStartsWith('# Instructions for AI assistants', (string) file_get_contents($this->tmpPath('docs/AGENTS.docara.md')));
        $display = preg_replace('/\s+/', ' ', $console->getDisplay());
        $this->assertStringContainsString('AGENTS.md already exists and was left unchanged', $display);
        $this->assertStringContainsString('docs/AGENTS.docara.md', $display);
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
    /** @return array<string, array{string}> */
    public static function setLanguages(): array
    {
        return ['English' => ['en'], 'Russian' => ['ru']];
    }

    #[Test]
    #[DataProvider('setLanguages')]
    public function the_set_template_writes_the_five_set_sections_with_the_set_id(string $language): void
    {
        [$status, $console] = $this->executeInit(['--template' => 'set', '--lang' => $language, '--set' => 'view'], showcase: false);

        $this->assertSame(Command::SUCCESS, $status, $console->getDisplay());
        $orders = [];
        foreach (['index.page.json', 'connection.page.json', 'components/section.json', 'demos/section.json', 'changes.page.json'] as $file) {
            $orders[$file] = $this->json("content/{$language}/{$file}")['navigation']['order'];
        }
        $this->assertSame([10, 20, 30, 40, 50], array_values($orders), 'Overview, Connection, Components, Demos, Changes.');
        $connection = (string) file_get_contents($this->tmpPath("content/{$language}/connection.md"));
        $this->assertStringContainsString('data-sf-sets="view"', $connection);
        $this->assertStringContainsString("'view': {base: '/assets/view/smart'", $connection);
        $this->assertStringNotContainsString('{set}', $connection);
        $this->assertStringContainsString('view', $this->json('docara.json')['title']);
        $this->assertSame(
            ['index.md' => 'landing', 'connection.md' => 'article', 'components/**' => 'reference', 'demos/**' => 'article', 'changes.md' => 'reference'],
            array_column($this->json('docara.authoring.json')['rules'], 'profile', 'match'),
        );
        // The five sections are the set's skeleton: no page-type samples.
        $this->assertDirectoryDoesNotExist($this->tmpPath("content/{$language}/getting-started"));
        $this->assertSame(
            array_map('basename', glob(dirname(__DIR__) . '/stubs/set/en/content/en/{,*/}*', GLOB_BRACE) ?: []),
            array_map('basename', glob(dirname(__DIR__) . "/stubs/set/{$language}/content/{$language}/{,*/}*", GLOB_BRACE) ?: []),
            'Both languages of the set starter have the same pages.',
        );
    }

    #[Test]
    public function the_set_template_refuses_an_id_that_is_not_a_set_id(): void
    {
        [$status, $console] = $this->executeInit(['--template' => 'set', '--set' => 'View Set'], showcase: false);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('The set id "view set" is not valid', $console->getDisplay());
        $this->assertFileDoesNotExist($this->tmpPath('docara.json'));
    }

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
