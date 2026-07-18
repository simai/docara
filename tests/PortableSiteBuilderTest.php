<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Framework\FrameworkComponentRuntime;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableHtmlRenderer;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Symfony\Component\Process\Process;

final class PortableSiteBuilderTest extends TestCase
{
    #[Test]
    public function it_builds_docs_landing_inheritance_components_and_explainable_plans(): void
    {
        $this->copyPortableFixture($this->tmp);
        $section = $this->jsonFile($this->tmpPath('content/guides/_section.json'));
        $section['navigation'] = ['$reset' => true];
        file_put_contents(
            $this->tmpPath('content/guides/_section.json'),
            json_encode($section, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
        file_put_contents(
            $this->tmpPath('content/index.md'),
            file_get_contents($this->tmpPath('content/index.md')) . "\n<script id=\"unsafe\">alert(1)</script>\n",
        );

        $result = $this->builder()->build($this->tmp, $this->tmpPath('build_local'));

        self::assertCount(3, $result);
        self::assertFileExists($this->tmpPath('build_local/index.html'));
        self::assertFileExists($this->tmpPath('build_local/guides/getting-started/index.html'));
        self::assertFileExists($this->tmpPath('build_local/landing/index.html'));

        $index = (string) file_get_contents($this->tmpPath('build_local/index.html'));
        $guide = (string) file_get_contents($this->tmpPath('build_local/guides/getting-started/index.html'));
        $landing = (string) file_get_contents($this->tmpPath('build_local/landing/index.html'));

        self::assertStringContainsString('docara-docs-layout gap-3 p-3', $index);
        self::assertStringContainsString('class="docara-landing flex flex-col gap-4 p-4"', $landing);
        self::assertStringContainsString('aria-current="page"', $index);
        self::assertStringContainsString('aria-current="page"', $guide);
        self::assertStringContainsString('<sf-alert', $index);
        self::assertStringContainsString('<sf-alert', $guide);
        self::assertStringContainsString('<sf-button', $guide);
        self::assertStringContainsString('<sf-button', $landing);
        self::assertStringNotContainsString('<script id="unsafe">', $index);
        self::assertStringNotContainsString('alert(1)', $index);

        foreach ([$index, $guide, $landing] as $html) {
            self::assertStringContainsString('theme-light', $html);
            self::assertStringContainsString('theme-dark', $html);
            self::assertStringContainsString('href="#docara-main">К содержанию</a>', $html);
            self::assertStringContainsString('id="docara-main" tabindex="-1"', $html);
            self::assertStringContainsString('id="docara-theme-toggle"', $html);
            self::assertStringContainsString('aria-live="polite"', $html);
            self::assertStringContainsString("Переключить на '+actions[next]+' тему", $html);
            self::assertStringNotContainsString('aria-pressed', $html);
            self::assertStringContainsString('.docara-theme-toggle:focus-visible', $html);
            self::assertStringContainsString('sf-button>button:focus-visible', $html);
            self::assertStringContainsString('@7e836d8a9414d5da553fb1ab0404721e5b48769a/', $html);
            self::assertStringNotContainsString('simai/ui-smart@', $html);
            self::assertStringContainsString('window.sfSmartPath="/_docara/framework"', $html);
            self::assertStringContainsString('/distr/fonts/MaterialSymbols-Outlined.woff2', $html);
            self::assertDoesNotMatchRegularExpression('~@(?:main|master|latest)(?:/|$)~i', $html);
        }

        foreach ([
            'smart/alert/js/alert.js' => 'e994066dd2a7f9c4d15c573ea66bb47ccb0f12c24f4cf2e7dedee29eaddf9f1c',
            'smart/buttons/js/buttons.js' => 'fe977fc7c608b7bacb79b7641a302c30a6195659ac2351594ae5aef0656d0a27',
            'smart/icons/js/icons.js' => 'c810be681b51f98002e01fb8852e992e454fa607af005033f9cc10309016fa09',
        ] as $relativePath => $sha256) {
            $published = $this->tmpPath('build_local/_docara/framework/' . $relativePath);
            self::assertFileExists($published);
            self::assertSame($sha256, hash_file('sha256', $published));
        }

        $diagnosticPath = $this->tmpPath('build_local/.docara/resolved-page-plans.json');
        $diagnosticJson = (string) file_get_contents($diagnosticPath);
        self::assertMatchesRegularExpression('/"navigation":\s*\{\}/', $diagnosticJson);
        self::assertDoesNotMatchRegularExpression('/"navigation":\s*\[\]/', $diagnosticJson);
        $diagnostics = $this->jsonFile($diagnosticPath);
        self::assertSame('docara.resolved_page_plans.v1', $diagnostics['schema']);
        self::assertCount(3, $diagnostics['pages']);
        $guidePlan = collect($diagnostics['pages'])->firstWhere('output', 'guides/getting-started/index.html');
        self::assertIsArray($guidePlan);
        self::assertSame(1, $guidePlan['resolved_page_plan']['contract_version']);
        self::assertSame('docs', $guidePlan['resolved_page_plan']['configuration']['preset']);
        self::assertSame('wide', $guidePlan['resolved_page_plan']['configuration']['layout']['max_width']);
        self::assertSame('left', $guidePlan['resolved_page_plan']['configuration']['layout']['sidebar']['position']);
        self::assertTrue($guidePlan['resolved_page_plan']['configuration']['settings']['table_of_contents']);
        self::assertSame(
            ['ui.alert', 'ui.button'],
            array_column($guidePlan['component_runtime']['normalized_calls'], 'component'),
        );
        self::assertSame(
            ['docara.json', 'simai-framework.lock.json', 'content/_section.json', 'content/guides/_section.json', 'content/guides/getting-started.page.json', 'content/guides/getting-started.md'],
            array_column($guidePlan['resolved_page_plan']['trace'], 'source'),
        );
    }

    #[Test]
    public function two_builds_are_byte_for_byte_deterministic(): void
    {
        $this->copyPortableFixture($this->tmp);
        $builder = $this->builder();

        $builder->build($this->tmp, $this->tmpPath('build_local'));
        $first = $this->treeHashes($this->tmpPath('build_local'));
        $builder->build($this->tmp, $this->tmpPath('build_local'));

        self::assertSame($first, $this->treeHashes($this->tmpPath('build_local')));
    }

    #[Test]
    public function clean_cli_install_and_build_work_without_legacy_scaffold(): void
    {
        $site = $this->tmpPath('empty-site');
        $this->filesystem->ensureDirectoryExists($site);
        $binary = dirname(__DIR__) . '/docara';
        $environment = ['TZ' => 'UTC', 'PATH' => dirname(PHP_BINARY) . ':/usr/bin:/bin:/usr/sbin:/sbin'];

        $init = new Process([PHP_BINARY, $binary, 'init', '--portable', '--no-interaction'], $site, $environment);
        $init->run();
        self::assertSame(0, $init->getExitCode(), $init->getErrorOutput() . $init->getOutput());
        self::assertFileExists($site . '/docara.json');
        self::assertFileDoesNotExist($site . '/config.php');
        self::assertDirectoryDoesNotExist($site . '/source');

        $build = new Process([PHP_BINARY, $binary, 'build', 'local', '--pretty=true', '--no-interaction'], $site, $environment);
        $build->run();
        self::assertSame(0, $build->getExitCode(), $build->getErrorOutput() . $build->getOutput());
        self::assertFileExists($site . '/build_local/index.html');
        self::assertFileExists($site . '/build_local/guides/getting-started/index.html');
        self::assertFileExists($site . '/build_local/landing/index.html');
        self::assertFileExists($site . '/build_local/.docara/resolved-page-plans.json');
        self::assertFileExists($site . '/build_local/_docara/framework/smart/alert/js/alert.js');
    }

    #[Test]
    public function russian_json_component_payload_is_not_split_as_a_unicode_newline(): void
    {
        $lock = $this->jsonFile(dirname(__DIR__) . '/stubs/portable/simai-framework.lock.json');
        $runtime = FrameworkComponentRuntime::fromLock($lock);
        $markdown = <<<'MD'
# Проверка

Русский текст содержит байты, которые не являются переводом строки.

:::ui.alert
{"type":"info","title":"Наследование работает","supporting-text":"Параметры страницы сохраняются целиком."}
:::
MD;

        $document = $runtime->extract($markdown, 'content/unicode.md');

        self::assertCount(1, $document->normalizedCalls);
        self::assertSame('Наследование работает', $document->normalizedCalls[0]['props']['title']);
        self::assertSame('Параметры страницы сохраняются целиком.', $document->normalizedCalls[0]['props']['supporting-text']);
    }

    #[Test]
    public function base_url_scopes_routes_and_revisioned_local_framework_assets(): void
    {
        $this->copyPortableFixture($this->tmp);
        $site = $this->jsonFile($this->tmpPath('docara.json'));
        $site['base_url'] = '/project~/docs/';
        file_put_contents(
            $this->tmpPath('docara.json'),
            json_encode($site, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );

        $result = $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
        self::assertTrue($result->has('/project~/docs/'));
        $html = (string) file_get_contents($this->tmpPath('build_local/index.html'));
        self::assertStringContainsString('href="/project~/docs/"', $html);
        self::assertStringContainsString(
            'window.sfSmartPath="/project~/docs/_docara/framework"',
            $html,
        );
        $diagnostics = (string) file_get_contents(
            $this->tmpPath('build_local/.docara/resolved-page-plans.json'),
        );
        self::assertMatchesRegularExpression(
            '#/project~/docs/_docara/framework/smart/alert/js/alert\.js\?sf_v=sf-v5\.3\.2-7e836d8a-dd786bba-[a-f0-9]{16}#',
            $diagnostics,
        );
        self::assertFileExists($this->tmpPath('build_local/_docara/framework/smart/alert/js/alert.js'));
    }

    #[Test]
    public function reserved_and_nonportable_derived_slugs_fail_before_existing_output_is_cleaned(): void
    {
        $this->copyPortableFixture($this->tmp);
        $this->filesystem->ensureDirectoryExists($this->tmpPath('build_local'));
        file_put_contents($this->tmpPath('build_local/sentinel.txt'), 'keep-output');
        file_put_contents($this->tmpPath('content/_docara.md'), "# Reserved\n");

        try {
            $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
            self::fail('A reserved derived slug unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('PAGE_SLUG_RESERVED', $exception->errorCode);
        }
        unlink($this->tmpPath('content/_docara.md'));
        file_put_contents($this->tmpPath('content/Bad Name.md'), "# Unsafe\n");

        try {
            $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
            self::fail('A nonportable derived slug unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('PAGE_SLUG_INVALID', $exception->errorCode);
        }
        self::assertSame('keep-output', file_get_contents($this->tmpPath('build_local/sentinel.txt')));
    }

    #[Test]
    public function destination_symlinks_and_input_overlap_fail_before_any_source_is_cleaned(): void
    {
        $this->copyPortableFixture($this->tmp);
        $outside = $this->tmpPath('outside');
        $this->filesystem->ensureDirectoryExists($outside);
        file_put_contents($outside . '/sentinel.txt', 'keep');
        if (! @symlink($outside, $this->tmpPath('build_local'))) {
            self::markTestSkipped('Symbolic links are not supported by this test environment.');
        }

        try {
            $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
            self::fail('A symbolic-link destination unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('DESTINATION_SYMLINK_FORBIDDEN', $exception->errorCode);
        }
        self::assertSame('keep', file_get_contents($outside . '/sentinel.txt'));
        unlink($this->tmpPath('build_local'));

        rename($this->tmpPath('content'), $this->tmpPath('build_local'));
        $site = $this->jsonFile($this->tmpPath('docara.json'));
        $site['content_root'] = 'build_local';
        file_put_contents(
            $this->tmpPath('docara.json'),
            json_encode($site, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
        file_put_contents($this->tmpPath('build_local/source-sentinel.txt'), 'keep-source');

        try {
            $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
            self::fail('A destination overlapping portable input unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('DESTINATION_INPUT_OVERLAP_FORBIDDEN', $exception->errorCode);
        }
        self::assertSame('keep-source', file_get_contents($this->tmpPath('build_local/source-sentinel.txt')));
    }

    #[Test]
    public function symbolic_link_site_roots_are_rejected_before_the_resolved_destination_is_touched(): void
    {
        $site = $this->tmpPath('portable-site');
        $link = $this->tmpPath('portable-site-link');
        $this->copyPortableFixture($site);
        $this->filesystem->ensureDirectoryExists($site . '/build_local');
        file_put_contents($site . '/build_local/sentinel.txt', 'keep');
        if (! @symlink($site, $link)) {
            self::markTestSkipped('Symbolic links are not supported by this test environment.');
        }

        try {
            foreach ([$link, $link . '/', $link . '/.'] as $root) {
                try {
                    $this->builder()->build($root, $site . '/build_local');
                    self::fail("A symbolic-link site root [$root] unexpectedly passed.");
                } catch (PortableConfigurationException $exception) {
                    self::assertSame('ROOT_SYMLINK_FORBIDDEN', $exception->errorCode);
                }

                self::assertSame('keep', file_get_contents($site . '/build_local/sentinel.txt'));
            }
        } finally {
            @unlink($link);
        }
    }

    #[Test]
    public function generated_and_reserved_asset_collisions_fail_before_existing_output_is_cleaned(): void
    {
        $this->copyPortableFixture($this->tmp);
        $this->filesystem->ensureDirectoryExists($this->tmpPath('content/_docara/framework'));
        file_put_contents($this->tmpPath('content/_docara/framework/tamper.js'), 'tamper');
        $this->filesystem->ensureDirectoryExists($this->tmpPath('build_local'));
        file_put_contents($this->tmpPath('build_local/sentinel.txt'), 'keep-output');

        try {
            $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
            self::fail('A reserved output collision unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('PORTABLE_ASSET_OUTPUT_COLLISION', $exception->errorCode);
        }
        self::assertSame('keep-output', file_get_contents($this->tmpPath('build_local/sentinel.txt')));
    }

    private function builder(): PortableSiteBuilder
    {
        return new PortableSiteBuilder(
            new Filesystem,
            new PortableMarkdownRenderer,
            new PortableHtmlRenderer,
        );
    }

    private function copyPortableFixture(string $target): void
    {
        $this->filesystem->copyDirectory(dirname(__DIR__) . '/stubs/portable', $target);
    }

    /** @return array<string, mixed> */
    private function jsonFile(string $path): array
    {
        return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return array<string, string> */
    private function treeHashes(string $root): array
    {
        $hashes = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            $hashes[$relative] = hash_file('sha256', $file->getPathname());
        }
        ksort($hashes, SORT_STRING);

        return $hashes;
    }
}
