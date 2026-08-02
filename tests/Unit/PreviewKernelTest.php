<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Simai\Docara\Preview\PreviewKernel;
use Simai\Docara\Preview\PreviewShell;
use Simai\Docara\Preview\PreviewTarget;
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
        self::assertFileExists($this->tmpPath('build_preview-cache/.docara-preview-cache.json'));

        $result = (new PreviewShell(new Filesystem))->publish($this->tmp, $smart);
        self::assertFalse($result['accepted_build_receipt']);
        self::assertFileExists($this->tmpPath('.docara-preview/output/smart/artifact.html'));
        self::assertFileDoesNotExist($this->tmpPath('.docara-preview/output/smart/.docara/resolved-page-plans.json'));

    }

    #[Test]
    public function unknown_target_and_symlink_dependency_fail_closed(): void
    {
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('PREVIEW_TARGET_NOT_FOUND');

        $this->kernel->render($this->tmp, '/ru/components/alert/', PreviewTarget::Smart, 'project.missing');
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
