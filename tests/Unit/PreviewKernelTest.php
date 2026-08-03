<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Console\PreviewCommand;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Simai\Docara\Preview\PreviewKernel;
use Simai\Docara\Preview\PreviewShell;
use Simai\Docara\Preview\PreviewTarget;
use Simai\Docara\Preview\PreviewWatcher;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class PreviewKernelTest extends TestCase
{
    private PreviewKernel $kernel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $this->tmp);
        $files = new Filesystem;
        $this->kernel = new PreviewKernel(
            new PortableSiteBuilder($files, new PortableMarkdownRenderer),
            $files,
        );
    }

    #[Test]
    public function all_targets_are_extracted_from_one_isolated_production_build(): void
    {
        $page = $this->kernel->render($this->tmp, '/ru/components/alert/', PreviewTarget::Page);
        $layout = $this->kernel->render($this->tmp, '/ru/components/alert/', PreviewTarget::Layout);
        $region = $this->kernel->render($this->tmp, '/ru/components/alert/', PreviewTarget::Region, 'main');
        $smart = $this->kernel->render($this->tmp, '/ru/components/alert/', PreviewTarget::Smart, 'ui.alert');

        self::assertSame($this->body($page->html), $layout->html);
        self::assertSame($this->node($page->html, '//*[@data-docara-region="main"][1]'), $region->html);
        self::assertSame($this->node($page->html, '//*[@data-docara-block="alert"][1]'), $smart->html);
        self::assertStringContainsString('data-docara-region="main"', $region->html);
        self::assertStringContainsString('data-docara-block="alert"', $smart->html);
        self::assertSame($page->assets, $layout->assets);
        self::assertSame('portable_site_builder', $smart->provenance['runtime']);
        self::assertSame($page->provenance['plan_hash'], $smart->provenance['plan_hash']);
        self::assertFileExists($this->tmpPath('build_preview-cache/.docara/resolved-page-plans.json'));
        $receipt = json_decode(
            (string) file_get_contents($this->tmpPath('build_preview-cache/.docara/resolved-page-plans.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertSame('preview', $receipt['build']['purpose']);
        self::assertFileDoesNotExist($this->tmpPath('build_preview-cache/.docara-preview-cache.json'));
        $verification = new Process([
            PHP_BINARY,
            dirname(__DIR__, 2) . '/scripts/verify-static-build.php',
            $this->tmpPath('build_preview-cache'),
        ]);
        $verification->run();
        self::assertSame(1, $verification->getExitCode());
        self::assertStringContainsString(
            'PREVIEW_BUILD_PURPOSE_FORBIDDEN',
            $verification->getOutput() . $verification->getErrorOutput(),
        );

        $result = (new PreviewShell(new Filesystem))->publish($this->tmp, $smart);
        self::assertFalse($result['accepted_build_receipt']);
        self::assertFileExists($this->tmpPath('.docara-preview/output/smart/artifact.html'));
        self::assertSame($page->html, $smart->pageHtml);
        self::assertSame($smart->pageHtml, (string) file_get_contents($this->tmpPath('.docara-preview/output/smart/index.html')));
        self::assertFileDoesNotExist($this->tmpPath('.docara-preview/output/smart/.docara/resolved-page-plans.json'));
        foreach ([
            'ru/_docara/declarative-shell.js',
            'ru/_docara/smart/navigation.js',
            'ru/_docara/smart/preferences.js',
            'ru/_docara/framework/smart/alert/js/alert.js',
        ] as $asset) {
            $published = $this->tmpPath('.docara-preview/output/smart/' . $asset);
            $production = $this->tmpPath('build_preview-cache/' . $asset);
            self::assertFileExists($published);
            self::assertSame(hash_file('sha256', $production), hash_file('sha256', $published));
        }
        self::assertGreaterThan(0, $result['published_files']['count']);

        $application = new Application;
        $application->add((new PreviewCommand($this->kernel, new PreviewShell(new Filesystem)))->setBase($this->tmp));
        $tester = new CommandTester($application->find('preview'));
        self::assertSame(0, $tester->execute([
            'target' => 'region',
            '--page' => '/ru/components/alert/',
            '--selector' => 'main',
            '--json' => true,
        ]));
        $json = json_decode(trim($tester->getDisplay()), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('docara.preview_artifact.v1', $json['schema']);
        self::assertFalse($json['accepted_build_receipt']);

        self::assertSame(0, $tester->execute([
            'target' => 'layout',
            '--page' => '/ru/components/alert/',
            '--json' => true,
            '--watch' => true,
            '--interval' => '50',
            '--max-cycles' => '1',
        ]));
        $watched = json_decode(trim($tester->getDisplay()), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(['cycles_rebuilt' => 0, 'target_only' => true], $watched['watch']);

        self::assertSame(1, $tester->execute([
            'target' => 'smart',
            '--page' => '/ru/components/alert/',
            '--selector' => '../unsafe',
            '--json' => true,
        ]));
        $error = json_decode(trim($tester->getDisplay()), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('docara.cli_error.v1', $error['schema']);
        self::assertSame('error', $error['status']);

    }

    #[Test]
    public function unknown_target_and_symlink_dependency_fail_closed(): void
    {
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('PREVIEW_TARGET_NOT_FOUND');

        $this->kernel->render($this->tmp, '/ru/components/alert/', PreviewTarget::Smart, 'project.missing');
    }

    #[Test]
    public function php_watch_invalidates_only_the_selected_target_dependency_closure(): void
    {
        $artifact = $this->kernel->render($this->tmp, '/ru/components/alert/', PreviewTarget::Region, 'main');
        self::assertContains('content/ru/components/alert.md', $artifact->dependencies);
        self::assertContains('content/ru/lang.json', $artifact->dependencies);
        self::assertContains('@package-tree:resources/smart/ui.alert', $artifact->dependencies);
        self::assertNotContains('@project-tree:smart/project.notice', $artifact->dependencies);
        $watcher = new PreviewWatcher;
        $watcher->prime($this->tmp, $artifact);
        $this->filesystem->append($this->tmpPath('smart/project.notice/template/default.php'), "\n");
        $unrelatedCalls = 0;
        $unrelated = $watcher->run(
            $this->tmp,
            $artifact,
            function () use (&$unrelatedCalls, $artifact) {
                $unrelatedCalls++;

                return $artifact;
            },
            50,
            1,
        );
        self::assertSame(0, $unrelatedCalls);
        self::assertSame([], $unrelated);

        $this->filesystem->append($this->tmpPath('content/ru/components/alert.md'), "\n");
        $calls = 0;
        $rebuilt = $watcher->run(
            $this->tmp,
            $artifact,
            function () use (&$calls) {
                $calls++;

                return $this->kernel->render($this->tmp, '/ru/components/alert/', PreviewTarget::Region, 'main');
            },
            50,
            1,
        );

        self::assertSame(1, $calls);
        self::assertCount(1, $rebuilt);
        self::assertSame('/ru/components/alert/', $rebuilt[0]->page);

        $packageRoot = $this->tmpPath('package-fixture');
        $this->filesystem->copyDirectory(
            dirname(__DIR__, 2) . '/resources/smart/ui.alert',
            $packageRoot . '/resources/smart/ui.alert',
        );
        $packageWatcher = new PreviewWatcher($packageRoot);
        $packageWatcher->prime($this->tmp, $artifact);
        $this->filesystem->append($packageRoot . '/resources/smart/ui.alert/templates/default.php', "\n");
        self::assertCount(1, $packageWatcher->run($this->tmp, $artifact, fn () => $artifact, 50, 1));
    }

    #[Test]
    public function selected_project_smart_tree_detects_edit_create_and_delete_once(): void
    {
        $artifact = $this->kernel->render($this->tmp, '/ru/', PreviewTarget::Page);
        self::assertContains('@project-tree:smart/project.notice', $artifact->dependencies);
        $watcher = new PreviewWatcher;
        $watcher->prime($this->tmp, $artifact);
        $created = $this->tmpPath('smart/project.notice/assets/runtime-created.css');
        $this->filesystem->put($created, '.fixture{}');
        self::assertCount(1, $watcher->run($this->tmp, $artifact, fn () => $artifact, 50, 1));

        $watcher->prime($this->tmp, $artifact);
        unlink($created);
        self::assertCount(1, $watcher->run($this->tmp, $artifact, fn () => $artifact, 50, 1));
    }

    private function body(string $html): string
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            self::assertTrue($document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return (string) $document->saveHTML($document->getElementsByTagName('body')->item(0));
    }

    private function node(string $html, string $query): string
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            self::assertTrue($document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $node = (new \DOMXPath($document))->query($query)?->item(0);
        self::assertNotNull($node);

        return (string) $document->saveHTML($node);
    }
}
