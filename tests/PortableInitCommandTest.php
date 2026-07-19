<?php

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Console\InitCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class PortableInitCommandTest extends TestCase
{
    #[Test]
    public function portable_init_creates_only_the_json_and_markdown_site_surface(): void
    {
        [$status, $console] = $this->executeInit(['--portable' => true]);

        $this->assertSame(Command::SUCCESS, $status, $console->getDisplay());
        $this->assertFileExists($this->tmpPath('docara.json'));
        $this->assertFileExists($this->tmpPath('simai-framework.lock.json'));
        $this->assertFileExists($this->tmpPath('content/guides/getting-started.md'));
        $this->assertFileExists($this->tmpPath('content/guides/getting-started.page.json'));
        $this->assertFileExists($this->tmpPath('content/guides/platform/configuration/layout.md'));
        $this->assertFileExists($this->tmpPath('assets/docara-mark.svg'));
        $this->assertFileExists($this->tmpPath('content/index.page.json'));
        $this->assertFileExists($this->tmpPath('content/landing.md'));
        $this->assertFileExists($this->tmpPath('content/landing.page.json'));

        $this->assertFileDoesNotExist($this->tmpPath('.env'));
        $this->assertFileDoesNotExist($this->tmpPath('config.php'));
        $this->assertDirectoryDoesNotExist($this->tmpPath('source'));
        $this->assertFileDoesNotExist($this->tmpPath('package.json'));
        $this->assertFileDoesNotExist($this->tmpPath('vite.config.js'));

        $this->assertStringContainsString('portable Docara site was initialized', $console->getDisplay());
    }

    #[Test]
    public function portable_fixture_uses_versioned_schema_ids_and_the_exact_framework_pair(): void
    {
        [$status] = $this->executeInit(['--portable' => true]);
        $this->assertSame(Command::SUCCESS, $status);

        $site = $this->json('docara.json');
        $rootSection = $this->json('content/_section.json');
        $nestedSection = $this->json('content/guides/_section.json');
        $indexPage = $this->json('content/index.page.json');
        $docsPage = $this->json('content/guides/getting-started.page.json');
        $landingPage = $this->json('content/landing.page.json');
        $lock = $this->json('simai-framework.lock.json');

        $this->assertSame('docara.site.v1', $site['schema']);
        $this->assertSame('Docara', $site['branding']['title']);
        $this->assertSame('assets/docara-mark.svg', $site['branding']['logo']);
        $this->assertSame('assets/docara-mark.svg', $site['branding']['logo_dark']);
        $this->assertSame('assets/docara-mark.svg', $site['branding']['favicon']);
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

        $this->assertSame('docara.framework_lock.v1', $lock['schema']);
        $this->assertSame('larena.ui.frontend_runtime_lock.v3', $lock['runtime']['schema']);
        $this->assertSame('sf-v5.3.2-7e836d8a-dd786bba', $lock['runtime']['pair_id']);
        $this->assertSame('7e836d8a9414d5da553fb1ab0404721e5b48769a', $lock['runtime']['ui']['commit']);
        $this->assertSame('dd786bbae98391fb21df9b4e1e6cd402ead0614c', $lock['runtime']['ui_smart']['commit']);
        $this->assertSame('b7e8a2e810c0d49e31cb749a7ab34c373dd48bc6', $lock['runtime']['framework_registry']['source']['commit']);
        $this->assertSame('4b055d09926fec4c32f2ae43b2e7e0a6f64d7663', $lock['manifests']['ui.button']['provider_revision']);
        $this->assertSame('84f61a452422814ef4ca11e5c5787ba48cdb36e923466c6309a8d389b84576fb', $lock['manifests']['ui.button']['sha256']);
        $this->assertSame('699b79d012d8e8af9a55f013ff19bafbc421cd16ee37990cb5ff070a0b1f490f', $lock['manifests']['ui.alert']['sha256']);
        $this->assertSame('docara.framework_asset_projection.v1', $lock['asset_projection']['schema']);
        $this->assertSame('_docara/framework', $lock['asset_projection']['mount']);
        $this->assertSame('simai/ui-smart', $lock['asset_projection']['source']['provider']);
        $this->assertSame(
            $lock['runtime']['ui_smart']['commit'],
            $lock['asset_projection']['source']['revision'],
        );
        $this->assertSame([
            'smart/alert/js/alert.js' => 'e994066dd2a7f9c4d15c573ea66bb47ccb0f12c24f4cf2e7dedee29eaddf9f1c',
            'smart/buttons/js/buttons.js' => 'fe977fc7c608b7bacb79b7641a302c30a6195659ac2351594ae5aef0656d0a27',
            'smart/icons/js/icons.js' => 'c810be681b51f98002e01fb8852e992e454fa607af005033f9cc10309016fa09',
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
        [$status] = $this->executeInit(['--portable' => true]);
        $this->assertSame(Command::SUCCESS, $status);

        $markdown = file_get_contents($this->tmpPath('content/guides/getting-started.md'));
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
    public function portable_update_preserves_all_existing_json_and_markdown_and_restores_only_missing_files(): void
    {
        [$status] = $this->executeInit(['--portable' => true]);
        $this->assertSame(Command::SUCCESS, $status);

        $preserved = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tmp, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (! $file->isFile() || ! in_array(strtolower($file->getExtension()), ['json', 'md', 'markdown'], true)) {
                continue;
            }

            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($this->tmp))), '/');
            if ($relative === 'content/landing.page.json') {
                continue;
            }

            $contents = "user-owned: {$relative}\n";
            file_put_contents($file->getPathname(), $contents);
            $preserved[$relative] = $contents;
        }
        unlink($this->tmpPath('content/landing.page.json'));

        [$updateStatus, $console] = $this->executeInit(['--portable' => true, '--update' => true]);

        $this->assertSame(Command::SUCCESS, $updateStatus, $console->getDisplay());
        foreach ($preserved as $relative => $contents) {
            $this->assertSame($contents, file_get_contents($this->tmpPath($relative)), "Portable update overwrote {$relative}");
        }
        $this->assertFileExists($this->tmpPath('content/landing.page.json'));
        $this->assertSame('docara.page.v1', $this->json('content/landing.page.json')['schema']);
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

        [$status, $console] = $this->executeInit(['--portable' => true, '--update' => true]);

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

        [$status, $console] = $this->executeInit(['--portable' => true, '--update' => true]);

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
    public function ordinary_update_does_not_enable_the_portable_mode(): void
    {
        $this->createSource([
            '.env' => "DOCS_DIR=docs\nAZURE_KEY=\nAZURE_REGION=\nAZURE_ENDPOINT=https://api.cognitive.microsofttranslator.com\n",
            'source' => [
                'docs' => [
                    'index.md' => '# Legacy',
                ],
            ],
        ]);

        $previousSkip = getenv('DOCARA_SKIP_FRONTEND_INSTALL');
        $previousDocs = getenv('DOCS_DIR');
        putenv('DOCARA_SKIP_FRONTEND_INSTALL=true');
        putenv('DOCS_DIR=docs');
        $_ENV['DOCARA_SKIP_FRONTEND_INSTALL'] = 'true';
        $_ENV['DOCS_DIR'] = 'docs';

        try {
            [$status, $console] = $this->executeInit(['--update' => true]);
        } finally {
            $this->restoreEnv('DOCARA_SKIP_FRONTEND_INSTALL', $previousSkip);
            $this->restoreEnv('DOCS_DIR', $previousDocs);
        }

        $this->assertSame(Command::SUCCESS, $status, $console->getDisplay());
        $this->assertFileDoesNotExist($this->tmpPath('docara.json'));
        $this->assertFileDoesNotExist($this->tmpPath('simai-framework.lock.json'));
        $this->assertSame('# Legacy', file_get_contents($this->tmpPath('source/docs/index.md')));
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{int, CommandTester}
     */
    private function executeInit(array $arguments): array
    {
        $command = $this->app->make(InitCommand::class);
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

    private function restoreEnv(string $name, string|false $value): void
    {
        if ($value === false) {
            putenv($name);
            unset($_ENV[$name]);

            return;
        }

        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
    }
}
