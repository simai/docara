<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * docara.json `theme` through the real command line: init, build,
 * verify-static.
 */
final class SiteThemeBuildTest extends TestCase
{
    private const THEME_LINK = '/\n    <link rel="stylesheet" href="\/_docara\/docara-theme\.[a-f0-9]{64}\.css" data-docara-site-theme>/';

    #[Test]
    public function a_theme_colour_adds_one_stylesheet_and_without_it_the_site_is_unchanged(): void
    {
        $site = $this->tmpPath('site-theme');
        $this->filesystem->ensureDirectoryExists($site);
        $this->docara(['init', '--template=minimal', '--lang=en'], $site);

        $this->docara(['build', 'production'], $site);
        $plain = $this->tree($site . '/build_production');
        self::assertSame([], array_values(array_filter(array_keys($plain), static fn (string $path): bool => str_contains($path, 'docara-theme'))));
        foreach ($plain as $path => $bytes) {
            self::assertStringNotContainsString('data-docara-site-theme', $bytes, $path);
        }
        self::assertArrayNotHasKey('theme', $this->buildReceipt($site));

        $this->setTheme($site, ['color' => '#0a7d4f']);
        $this->docara(['build', 'production'], $site);
        $themed = $this->tree($site . '/build_production');
        $themeFiles = array_values(array_filter(array_keys($themed), static fn (string $path): bool => str_starts_with($path, '_docara/docara-theme.')));
        self::assertCount(1, $themeFiles);
        $css = $themed[$themeFiles[0]];
        self::assertSame('_docara/docara-theme.' . hash('sha256', $css) . '.css', $themeFiles[0]);
        self::assertStringContainsString("\n:root {\n", $css);
        self::assertStringContainsString('  --sf-primary-40: #006d44;', $css);
        self::assertSame(128, preg_match_all('/^  --sf-[a-z]+-[0-9]+: #[0-9a-f]{6};$/m', $css));

        // The themed site is the plain site plus the stylesheet and one link
        // per page, after the page's first-frame stylesheets.
        unset($themed[$themeFiles[0]]);
        self::assertSame(array_keys($plain), array_keys($themed));
        foreach ($themed as $path => $bytes) {
            if (! str_ends_with($path, '.html') || ! str_contains($plain[$path], 'data-docara-declarative-shell-style')) {
                self::assertSame($plain[$path], $bytes, $path);

                continue;
            }
            self::assertSame(1, preg_match_all(self::THEME_LINK, $bytes), $path);
            self::assertSame($plain[$path], preg_replace(self::THEME_LINK, '', $bytes), $path);
            self::assertGreaterThan(
                strrpos($bytes, 'data-docara-declarative-shell-style'),
                strpos($bytes, 'data-docara-site-theme'),
                $path,
            );
        }
        $receipt = $this->buildReceipt($site)['theme'];
        self::assertSame(['chroma' => null, 'color' => '#0a7d4f', 'hue' => null], $receipt['input']);
        self::assertSame(['chroma' => 85, 'hue' => 161], $receipt['palette']);
        self::assertSame(basename($themeFiles[0]), $receipt['stylesheet']['filename']);
        $verification = $this->docara(['verify-static', 'build_production', '--no-ansi'], $site);
        $verification = json_decode(substr($verification, (int) strpos($verification, '{')), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame([], $verification['broken']);

        // One page rebuilds against the same theme; a changed theme needs a
        // complete build, since every other page links the old stylesheet.
        $this->docara(['build', 'production', '--page=/writing/'], $site);
        $expected = $themed + [$themeFiles[0] => $css];
        ksort($expected, SORT_STRING);
        self::assertSame($expected, $this->tree($site . '/build_production'));
        $this->setTheme($site, ['color' => '#0a7d4f', 'hue' => 200]);
        $changed = $this->docara(['build', 'production', '--page=/writing/'], $site, expectSuccess: false);
        self::assertStringContainsString('PORTABLE_INCREMENTAL_THEME_CHANGED', $changed);
        $this->setTheme($site, ['color' => '#0a7d4f']);

        // A tampered theme stylesheet fails verification.
        file_put_contents($site . '/build_production/' . $themeFiles[0], $css . "body{color:red}\n");
        $tampered = $this->docara(['verify-static', 'build_production', '--no-ansi'], $site, expectSuccess: false);
        self::assertStringContainsString('Site theme stylesheet', $tampered);

        // Taking the setting out gives back the plain site, byte for byte.
        $this->setTheme($site, null);
        $this->docara(['build', 'production'], $site);
        self::assertSame($plain, $this->tree($site . '/build_production'));
    }

    #[Test]
    public function a_bad_colour_stops_the_build_with_its_code(): void
    {
        $site = $this->tmpPath('site-theme-invalid');
        $this->filesystem->ensureDirectoryExists($site);
        $this->docara(['init', '--template=minimal', '--lang=en'], $site);
        $this->setTheme($site, ['color' => 'green']);

        $output = $this->docara(['build', 'production'], $site, expectSuccess: false);
        self::assertStringContainsString('THEME_COLOR_INVALID', $output);
        self::assertStringContainsString('#rgb or #rrggbb', $output);
        self::assertDirectoryDoesNotExist($site . '/build_production');
    }

    /** @param array<string, mixed>|null $theme */
    private function setTheme(string $site, ?array $theme): void
    {
        $configuration = json_decode((string) file_get_contents($site . '/docara.json'), true, 512, JSON_THROW_ON_ERROR);
        unset($configuration['theme']);
        if ($theme !== null) {
            $configuration['theme'] = $theme;
        }
        file_put_contents($site . '/docara.json', json_encode($configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    }

    /** @return array<string, mixed> */
    private function buildReceipt(string $site): array
    {
        $plans = json_decode(
            (string) file_get_contents($site . '/build_production.docara/resolved-page-plans.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        return $plans['build'];
    }

    /** @return array<string, string> relative path => bytes */
    private function tree(string $root): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1))] = (string) file_get_contents($file->getPathname());
            }
        }
        ksort($files, SORT_STRING);

        return $files;
    }

    /** @param list<string> $arguments */
    private function docara(array $arguments, string $cwd, bool $expectSuccess = true): string
    {
        $node = getenv('DOCARA_NODE_BINARY') ?: (new ExecutableFinder)->find('node');
        self::assertIsString($node, 'Building a site requires Node.js.');
        $process = new Process(
            array_merge([PHP_BINARY, dirname(__DIR__) . '/docara'], $arguments, ['--no-interaction']),
            $cwd,
            [
                'TZ' => 'UTC',
                'PATH' => dirname(PHP_BINARY) . ':/usr/bin:/bin:/usr/sbin:/sbin',
                'SIMAI_UI_ROOT' => false,
                'DOCARA_SIMAI_UI_ROOT' => false,
                'SIMAI_UI_SMART_ROOT' => false,
                'DOCARA_NODE_BINARY' => $node,
            ],
            null,
            600,
        );
        $process->run();
        $output = $process->getOutput() . $process->getErrorOutput();
        if ($expectSuccess) {
            self::assertSame(0, $process->getExitCode(), implode(' ', $arguments) . "\n" . $output);

            return $process->getOutput();
        }
        self::assertNotSame(0, $process->getExitCode(), implode(' ', $arguments) . " succeeded\n" . $output);

        return $output;
    }
}
