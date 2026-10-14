<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;
use Simai\Docara\PortableSite\PortablePerformanceReceipt;

final class PortablePerformanceReceiptTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/docara-performance-' . bin2hex(random_bytes(6));
        mkdir($this->root . '/guide', 0777, true);
        mkdir($this->root . '/_docara', 0777, true);
        file_put_contents($this->root . '/_docara/shell.css', 'body{color:CanvasText}');
        file_put_contents($this->root . '/_docara/app.js', 'window.docaraReady=true;');
        file_put_contents($this->root . '/_docara/icons.woff2', 'local-font');
        file_put_contents($this->root . '/guide/index.html', <<<'HTML'
<!doctype html><html><head>
<style>html{color-scheme:light dark}</style>
<link rel="stylesheet" href="/project/_docara/shell.css?v=1">
<link rel="preload" as="font" href="/project/_docara/icons.woff2" crossorigin>
<script>window.inlineBoot=true;</script>
<script defer src="/project/_docara/app.js?v=1"></script>
<script defer src="https://example.test/optional.js"></script>
</head><body><h1>Guide</h1></body></html>
HTML);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->root);
        parent::tearDown();
    }

    #[Test]
    public function it_reports_exact_initial_resources_without_enforcing_budgets(): void
    {
        $reporter = new PortablePerformanceReceipt(new Filesystem);
        $pages = [['output' => 'guide/index.html', 'url' => '/project/guide/']];
        $receipt = $reporter->publish($this->root, '/project/', $pages);

        self::assertSame('docara.performance_receipt.v1', $receipt['schema']);
        self::assertSame(1, $receipt['site']['page_count']);
        self::assertSame(4, $receipt['pages'][0]['initial_requests']);
        self::assertSame(3, $receipt['site']['unique_initial_local_resources']);
        self::assertSame(
            strlen('body{color:CanvasText}') + strlen('window.docaraReady=true;') + strlen('local-font'),
            $receipt['pages'][0]['initial_local_bytes'],
        );
        self::assertSame(strlen('html{color-scheme:light dark}'), $receipt['pages'][0]['inline_css_bytes']);
        self::assertSame(strlen('window.inlineBoot=true;'), $receipt['pages'][0]['inline_javascript_bytes']);
        self::assertFalse($receipt['pages'][0]['resources'][3]['local']);
        self::assertSame(
            hash('sha256', CanonicalJson::encode(array_diff_key($receipt, ['content_sha256' => true]))),
            $receipt['content_sha256'],
        );
        self::assertSame(
            $receipt,
            $reporter->build($this->root, '/project/', array_reverse($pages)),
        );
        (new SchemaRepository)->assertValid($receipt, 'performance-receipt.schema.json');
        self::assertFileExists($this->root . '/.docara/performance.json');
    }

    #[Test]
    public function unsafe_or_missing_local_resources_fail_closed(): void
    {
        unlink($this->root . '/_docara/app.js');

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('PORTABLE_PERFORMANCE_RESOURCE_UNSAFE');
        (new PortablePerformanceReceipt(new Filesystem))->build(
            $this->root,
            '/project/',
            [['output' => 'guide/index.html', 'url' => '/project/guide/']],
        );
    }
}
