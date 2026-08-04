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
        $this->assertSame('sf-v5.3.2-96c17a26-aa9f34a4', $lock['runtime']['pair_id']);
        $this->assertSame('96c17a2633a176bc77821632703930dc16276c7b', $lock['runtime']['ui']['commit']);
        $this->assertSame('aa9f34a4d2bf421e20970ab4eb0418f017c62059', $lock['runtime']['ui_smart']['commit']);
        $this->assertSame('b7e8a2e810c0d49e31cb749a7ab34c373dd48bc6', $lock['runtime']['framework_registry']['source']['commit']);
        $this->assertSame('4b055d09926fec4c32f2ae43b2e7e0a6f64d7663', $lock['manifests']['ui.button']['provider_revision']);
        $this->assertSame('16657a8d75efc86383039ec746ffe7b291c8669569995f5fd1c6161eba55d9e9', $lock['manifests']['ui.button']['sha256']);
        $this->assertSame('b6e7c91da1157335ac2a017571aeed8b518a95bb41c88688e6ef122bf39efc29', $lock['manifests']['ui.alert']['sha256']);
        $this->assertSame('docara.framework_asset_projection.v1', $lock['asset_projection']['schema']);
        $this->assertSame('_docara/framework', $lock['asset_projection']['mount']);
        $this->assertSame('simai/ui-smart', $lock['asset_projection']['source']['provider']);
        $this->assertSame(
            $lock['runtime']['ui_smart']['commit'],
            $lock['asset_projection']['source']['revision'],
        );
        $this->assertSame([
            'smart/alert/js/alert.js' => '6720a3dd126f35c46fc09ecb6aeb0f2d9ebfcce82388ba8cc031c24cead426a7',
            'smart/buttons/js/buttons.js' => 'f9d400cd9d88c23243f75b313e9d0040ebee4e12e763d12a5ba86e556cf5c48b',
            'smart/icons/js/icons.js' => '6fe9a1ac7436ba6017addd7c9d389633e1fe4be4ae86cc0cd7fb45c0b31902d1',
            'smart/modal/js/modal.js' => '7ddde60f8a85cc9496685e6d70299d84e67b4cfecde845714ba7e2825b61a045',
        ], array_map(
            static fn (array $record): string => $record['sha256'],
            $lock['asset_projection']['files'],
        ));

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
