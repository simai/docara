<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;

final class PageWeightScriptTest extends TestCase
{
    #[Test]
    public function it_counts_the_html_and_only_the_assets_a_page_makes_the_browser_fetch(): void
    {
        $site = $this->site('.sf-icon.sf-icon-full-font{font-family:"Icons Full"}');

        $process = $this->weigh($site, ['--json']);

        self::assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $report = json_decode($process->getOutput(), true);
        self::assertSame(1, $report['pages']);
        self::assertSame(1, $report['redirect_pages_skipped']);
        $page = $report['heaviest'][0];
        self::assertSame('index.html', $page['page']);
        $expected = filesize($site . '/index.html') + 300 + 3_000 + 5_000 + 20_000
            + filesize($site . '/assets/site.css');
        self::assertSame($expected, $page['total_bytes']);
        $paths = array_column($page['largest'], 'path');
        self::assertContains('assets/body.woff2', $paths);
        self::assertNotContains('assets/MaterialSymbolsOutlined.woff2', $paths, 'A full font behind a class the page lacks is not fetched.');
        self::assertNotContains('assets/greek.woff2', $paths, 'A unicode-range face without matching text is not fetched.');
        self::assertNotContains('assets/unused.png', $paths, 'A background for a class the page lacks is not fetched.');
    }

    #[Test]
    public function it_fails_over_budget_pages_large_images_full_icon_fonts_and_oversized_bitmaps(): void
    {
        $site = $this->site('.sf-icon{font-family:"Icons Full"}', '<img src="assets/hero.png" width="4" alt="">');

        $process = $this->weigh($site, ['--max-page-bytes=10000', '--max-image-bytes=4000']);

        self::assertSame(1, $process->getExitCode());
        $errors = $process->getErrorOutput();
        self::assertStringContainsString('FAIL index.html weighs', $errors);
        self::assertStringContainsString('uses the image assets/hero.png of 5.0 KB, over the 4.0 KB image budget', $errors);
        self::assertStringContainsString('has only shell-subset icons but makes the browser fetch the full Material Symbols font assets/MaterialSymbolsOutlined.woff2', $errors);
        self::assertStringContainsString('shows assets/hero.png (40x20) at 4x0', $errors);

        $config = $site . '/budget.json';
        file_put_contents($config, json_encode(['max_page_bytes' => 10_000, 'pages' => ['/' => ['max_page_bytes' => 60_000_000]]]));
        $overridden = $this->weigh($site, ['--config=' . $config]);
        self::assertStringNotContainsString('FAIL index.html weighs', $overridden->getErrorOutput());
    }

    private function site(string $iconRule, string $extraBody = ''): string
    {
        $site = $this->tmpPath('page-weight');
        mkdir($site . '/assets', 0777, true);
        mkdir($site . '/legacy', 0777, true);
        mkdir($site . '/_docara/vendor/docara/icon-subset/1', 0777, true);
        file_put_contents($site . '/_docara/vendor/docara/icon-subset/1/subset.manifest.json', json_encode(['icons' => ['home']]));
        file_put_contents($site . '/assets/site.css', implode("\n", [
            '@font-face{font-family:"Body";src:url(body.woff2) format("woff2"),url(body.woff) format("woff")}',
            '@font-face{font-family:"Body";src:url(greek.woff2);unicode-range:U+0370-03FF}',
            '@font-face{font-family:"Icons Full";src:url("MaterialSymbolsOutlined.woff2")}',
            ':root{--font:"Body",sans-serif}',
            'body{font-family:var(--font)}',
            '.sf-icon-shape{--font:"Icons Full"}',
            '@media (min-width:1px){.unused{background:url(unused.png)}}',
            $iconRule,
        ]));
        foreach (['app.js' => 3_000, 'hero.png' => 5_000, 'body.woff2' => 20_000, 'body.woff' => 30_000, 'greek.woff2' => 40_000, 'MaterialSymbolsOutlined.woff2' => 4_000_000, 'unused.png' => 50_000, 'favicon.ico' => 300] as $file => $bytes) {
            file_put_contents($site . '/assets/' . $file, str_repeat('x', $bytes));
        }
        // A 40x20 PNG header with padding up to 5000 bytes, so getimagesize reads it.
        $png = "\x89PNG\r\n\x1a\n" . pack('N', 13) . 'IHDR' . pack('NNCCCCC', 40, 20, 8, 2, 0, 0, 0) . pack('N', 0);
        file_put_contents($site . '/assets/hero.png', $png . str_repeat("\0", 5_000 - strlen($png)));
        file_put_contents($site . '/index.html', '<!doctype html><html><head><link rel="stylesheet" href="/assets/site.css">'
            . '<link rel="icon" href="/assets/favicon.ico"><script src="assets/app.js" defer></script></head>'
            . '<body><p>Hello</p><i class="sf-icon">home</i><img src="/assets/hero.png" alt="">' . $extraBody . '</body></html>');
        file_put_contents($site . '/legacy/index.html', '<!doctype html><meta http-equiv="refresh" content="0; url=/">');

        return $site;
    }

    /** @param list<string> $arguments */
    private function weigh(string $site, array $arguments): Process
    {
        $process = new Process([PHP_BINARY, dirname(__DIR__) . '/scripts/verify-page-weight.php', $site, ...$arguments]);
        $process->run();

        return $process;
    }
}
