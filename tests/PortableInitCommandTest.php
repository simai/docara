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
        $this->assertSame('ui-73df5d1b4d4c', $lock['runtime']['pair_id']);
        $this->assertSame('73df5d1b4d4c82d674dfa2c7fb4e41f49a6b47eb', $lock['runtime']['ui']['commit']);
        $this->assertSame('73df5d1b4d4c82d674dfa2c7fb4e41f49a6b47eb', $lock['runtime']['ui_smart']['commit']);
        $this->assertSame('b209ab955296b2476480e4d1ab82db164dfca6ad', $lock['runtime']['framework_registry']['source']['commit']);
        $this->assertSame('4b055d09926fec4c32f2ae43b2e7e0a6f64d7663', $lock['manifests']['ui.button']['provider_revision']);
        $this->assertSame('23a5817fbfc3d247853d17ad270dd6914bafc6fd139ffe5524060ae6b317570b', $lock['manifests']['ui.button']['sha256']);
        $this->assertSame('f8c25205d51f18ab777f7ab3e6b8643203a72dfefd3ac799f01acaed32f96048', $lock['manifests']['ui.alert']['sha256']);
        $this->assertSame('docara.framework_asset_projection.v1', $lock['asset_projection']['schema']);
        $this->assertSame('_docara/framework-runtime', $lock['asset_projection']['mount']);
        $this->assertSame('simai/ui', $lock['asset_projection']['source']['provider']);
        $this->assertSame(
            $lock['runtime']['ui_smart']['commit'],
            $lock['asset_projection']['source']['revision'],
        );
        $this->assertSame([
            'smart/alert/js/alert.js' => 'a2177dd9ba1559150b0085c5bf2cd9b1bc272703690b7ffaa92639d180aff07e',
            'smart/alert/js/alert.min.js' => '3dd52eb8cea060036f995972372c1b38771539a1670d1e4fcfff887ef7b82ffb',
            'smart/avatar/js/avatar.js' => 'b04235faaad48e325bfbc37f2f98dab21f3b04bf9b75fd6b4b1433fac105af16',
            'smart/avatar/js/avatar.min.js' => 'bf390e889e72ac0fa8cc96d6cf6696bebbdf9cb0321207cd5c5b8b6631028e56',
            'smart/badge/js/badge.js' => '925a514cfabe7609c33b9cc314d7b595fb671331b6605ea178641ab9ce96ed4a',
            'smart/badge/js/badge.min.js' => '3c8a8031cdb2af2b27f48c6e1f922a4020fe2826bfe29f54a40d6a89fb3d6ed9',
            'smart/button-close/css/button-close.css' => '3bf84ff663eb381ed5da16bd496d628b9b709c0315d6c589059b424990a2296e',
            'smart/button-close/css/button-close.min.css' => '01ba4719c80b6fe911b091a7c05124b64eeece964e09c058ef8f9805daca546b',
            'smart/button-close/js/button-close.js' => '0b3c0ce0f693fbaf87d3299c51756589388005668b89927fc5c1b4f2a6d98943',
            'smart/button-close/js/button-close.min.js' => '1a5ff3713ff6b8c98cba98beecc9e0425b346b41ae34e1481f864a1dfd86b092',
            'smart/button-copy/css/button-copy.css' => '06e8a6e47f5b06b51bfdeceaaa1d108a90c99d8b0fca2698ff3a1a23566d6c66',
            'smart/button-copy/css/button-copy.min.css' => '68adea43e82ada4addeb92a450fd9488acdb1b7d6b79309137b10fa4836898bd',
            'smart/button-copy/js/button-copy.js' => '3c06164f4aabaac3861a8f5943848730c7b69b38ceac35b51f21381ee17ced97',
            'smart/button-copy/js/button-copy.min.js' => '53a20873d6eeee72d3f03538488248ca7ccb4dd60e9aa21495d2b0e843940599',
            'smart/button-icon/css/button-icon.css' => '44a32a6144036767c6a7c1bdb60bb3fce67eae3db446a1e34d83bc48484d86b7',
            'smart/button-icon/css/button-icon.min.css' => 'a8e4966b896823f60e67c061348ecb4567ee7544abb1626002b0eafbaae03835',
            'smart/button-icon/js/button-icon.js' => 'bb783a03ecd342b10db63566555aaab585a1003f03c5e1d0df8e10e6b863640f',
            'smart/button-icon/js/button-icon.min.js' => 'ac29e4424a3c4d59af50f27b8c57d466548f7e73d71dd2e8d485c6481885dc8b',
            'smart/button/js/button.js' => 'c47017fc31c76b495bda5fc1af7c761e7742c6edb4ee783c2217f5dd2c404015',
            'smart/button/js/button.min.js' => '3ee9fdc6f92203369ccd5a896ea4d63cc1ef9d091c828053c2a052c1d998db41',
            'smart/icon/js/icon.js' => '70c78f440f375632c2290e2685768b79f1d42b35572a3d2db84f8ffbd58b869d',
            'smart/icon/js/icon.min.js' => 'f699827e45589d4d25acbfb20768c535b511ed93c85346eb492837ebc5f90e6e',
            'smart/modal/js/modal.js' => 'b563b140b1c617e0ac24cc0b7e36a3e9e6e6e3a9db7930dab334bbdf0bd12f35',
            'smart/modal/js/modal.min.js' => '60908ce69ef5f940eb03a8c77aa7277cf86327bf36ac9467b19ec1094f620f34',
            'smart/progress-bar/js/progress-bar.js' => '91d106b4d3c19925636430f79bfed5542f02299eb310ea5091c16ed19f93e889',
            'smart/progress-bar/js/progress-bar.min.js' => '67ff8444405d5d3d245638848956c7fa10cc0df23208d1b1982e043d3f6599ef',
            'smart/reference-link/js/reference-link.js' => 'db26304c69da3419f61887ba16b32a60a9aac31d87ffdeb83250f0050a62154e',
            'smart/reference-link/js/reference-link.min.js' => '9d433c19c295c11411de699632ff20ac7bb5ea551b7b49f8a0c19f11833ad8cf',
            'smart/tag/css/tag.css' => 'a1a86edc4d8d7780d5bac9dbc4ff23f9590e2285da5198e319e22211a5499336',
            'smart/tag/css/tag.min.css' => '280a6d72fe06e07370ff5976e9826bac051934e8493c7d3be949cd9afb5c767f',
            'smart/tag/js/tag.js' => 'b4ea1d9f988af52388d6c439a57307499ee9fb1538423178bcf98d71187b8b82',
            'smart/tag/js/tag.min.js' => '9f636caa91e23833dfb536bc01c2ba68fb73d46f21b737991dad9f08afac6503',
            'smart/tooltip/js/tooltip.js' => '38c1059e31cd0e517debdbe1be02562359e824cf3a4d3936a240bbec54d840d0',
            'smart/tooltip/js/tooltip.min.js' => '1ed8cc403bfe5ba9aaa7fb9191b3d488d3854f7d8a56a7a447195b33b2f32f2e',
        ], array_map(
            static fn (array $record): string => $record['sha256'],
            $lock['asset_projection']['files'],
        ));
        $this->assertSame('docara.framework_dynamic_asset_projection.v1', $lock['dynamic_asset_projection']['schema']);
        $this->assertSame('_docara/framework-runtime', $lock['dynamic_asset_projection']['mount']);
        $this->assertSame($lock['runtime']['ui_smart']['commit'], $lock['dynamic_asset_projection']['source']['revision']);
        $this->assertCount(323, $lock['dynamic_asset_projection']['files']);
        $this->assertSame(
            '8f3e2e9cbaacf7935dfe2be7e0992f0336b067a3576e190f7693163226675d7e',
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
