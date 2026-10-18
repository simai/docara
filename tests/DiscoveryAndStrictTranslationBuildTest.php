<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * A bilingual site (Russian source, English target) under a sub-path with
 * site_url and translation_tracking mode publish-current, built and verified
 * through the real command line.
 */
final class DiscoveryAndStrictTranslationBuildTest extends TestCase
{
    #[Test]
    public function a_publish_current_site_with_a_site_url_publishes_only_current_translations_and_discovery_files(): void
    {
        $site = $this->tmpPath('site');
        $this->filesystem->ensureDirectoryExists($site);
        $this->docara(['init', '--template=minimal', '--lang=ru'], $site);

        $configuration = $this->readJson($site . '/docara.json');
        $configuration['base_url'] = '/docs/';
        $configuration['site_url'] = 'https://example.org/docs/';
        $configuration['discovery'] = [
            'description' => 'Example documentation.',
            'llms_links' => [['title' => 'AI manifest', 'path' => 'ai.json']],
        ];
        $configuration['branding']['share_image'] = 'assets/share.png';
        $configuration['locales']['ru']['public_prefix'] = 'ru';
        $configuration['locales']['en'] = [
            'label' => 'English',
            'direction' => 'ltr',
            'content_root' => 'content/en',
            'public_prefix' => 'en',
            'fallbacks' => ['ru'],
        ];
        $configuration['locale_routing'] = [
            'strategy' => 'prefixed',
            'root' => 'redirect',
            'detect_browser_language' => false,
            'legacy_unprefixed_redirects' => true,
        ];
        $configuration['translation_tracking'] = [
            'enabled' => true,
            'source_locale' => 'ru',
            'mode' => 'publish-current',
            'lock_file' => 'translations.lock.json',
        ];
        $this->writeJson($site . '/docara.json', $configuration);
        file_put_contents($site . '/assets/share.png', $this->png());

        $ru = $site . '/content/ru';
        $en = $site . '/content/en';
        $this->filesystem->ensureDirectoryExists($en);
        foreach (['index.md', 'writing.md', 'components.md', 'lang.json', 'section.json'] as $file) {
            copy($ru . '/' . $file, $en . '/' . $file);
        }
        file_put_contents($en . '/legacy.md', "# Legacy\n\nNo source page.\n");
        file_put_contents($ru . '/faq.md', "# Вопросы\n\nПока только по-русски.\n");
        file_put_contents($ru . '/internal.md', "---\nnoindex: true\n---\n# Служебная\n\nНе для поиска.\n");

        // A home page titled like the site is not named twice.
        $home = $this->readJson($ru . '/index.page.json');
        $home['title'] = 'Моя документация';
        $this->writeJson($ru . '/index.page.json', $home);

        // Accept the interface strings and two pages as a reviewed set.
        $this->accept($site, ['--kind=lang', '--status=unverified']);
        $this->accept($site, ['--key=index.md', '--key=writing.md']);
        // The source changes after review: writing.md and one string go stale.
        file_put_contents($ru . '/writing.md', (string) file_get_contents($ru . '/writing.md') . "\nНовый абзац.\n");
        $lang = $this->readJson($ru . '/lang.json');
        $lang['search']['placeholder'] = 'Новая подсказка поиска';
        $this->writeJson($ru . '/lang.json', $lang);

        $output = $this->docara(['build', 'production'], $site);
        self::assertStringContainsString('1 page(s) withheld', $output);
        self::assertStringContainsString('2 page(s) show the source text', $output);
        self::assertMatchesRegularExpression('/source_fallback\s+stale\s+writing\.md \[en\]/', $output);
        self::assertMatchesRegularExpression('/source_fallback\s+unverified\s+components\.md \[en\]/', $output);
        self::assertMatchesRegularExpression('/withheld\s+orphan\s+legacy\.md \[en\]/', $output);

        $build = $site . '/build_production';
        self::assertFileDoesNotExist($build . '/en/legacy/index.html');
        self::assertFileDoesNotExist($build . '/en/faq/index.html');

        $current = (string) file_get_contents($build . '/en/index.html');
        self::assertStringNotContainsString('data-docara-translation-fallback', $current);
        self::assertStringNotContainsString('name="robots"', $current);
        self::assertStringContainsString('<link rel="canonical" href="https://example.org/docs/en/">', $current);
        self::assertStringContainsString('<link rel="alternate" hreflang="ru" href="https://example.org/docs/ru/">', $current);
        self::assertStringContainsString('placeholder="Новая подсказка поиска"', $current);
        self::assertStringContainsString('<meta property="og:url" content="https://example.org/docs/en/">', $current);
        self::assertStringContainsString('<meta property="og:locale" content="en">', $current);
        self::assertStringContainsString('<meta property="og:locale:alternate" content="ru">', $current);
        self::assertStringContainsString('<meta property="og:type" content="website">', $current);
        self::assertMatchesRegularExpression('#<meta property="og:image" content="https://example\.org/docs/_docara/brand/[a-f0-9]{64}\.png">#', $current);
        self::assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $current);

        $ruHome = (string) file_get_contents($build . '/ru/index.html');
        self::assertStringContainsString('<title>Моя документация</title>', $ruHome);
        // Core's chunk preloads repeat core.js's URL, so it carries no query.
        self::assertMatchesRegularExpression('#<script defer src="/docs/_docara/vendor/simai-framework/runtime/[a-f0-9]{40}/distr/core/js/core\.js" data-docara-framework-asset="simai\.framework\.core\.js">#', $ruHome);
        $held = (string) file_get_contents($build . '/en/writing/index.html');
        self::assertStringContainsString('<html lang="en"', $held);
        self::assertStringContainsString('data-docara-translation-fallback="stale"', $held);
        self::assertStringContainsString('Новый абзац.', $held);
        self::assertStringContainsString('<meta name="robots" content="noindex">', $held);
        self::assertStringContainsString('<link rel="canonical" href="https://example.org/docs/ru/writing/">', $held);
        self::assertStringNotContainsString('hreflang="ru" href="https://example.org/docs/ru/writing/"', $held);
        self::assertStringContainsString('href="/docs/ru/writing/" hreflang="ru" lang="ru">Русский</a>', $held);
        self::assertStringNotContainsString('<link rel="alternate" hreflang="en"', (string) file_get_contents($build . '/ru/writing/index.html'));
        self::assertStringContainsString('<meta name="robots" content="noindex">', (string) file_get_contents($build . '/ru/internal/index.html'));

        $sitemap = (string) file_get_contents($build . '/sitemap.xml');
        self::assertStringContainsString('<loc>https://example.org/docs/ru/writing/</loc>', $sitemap);
        self::assertStringContainsString('<loc>https://example.org/docs/en/</loc>', $sitemap);
        self::assertStringContainsString('<xhtml:link rel="alternate" hreflang="en" href="https://example.org/docs/en/"/>', $sitemap);
        self::assertStringNotContainsString('/en/writing/', $sitemap);
        self::assertStringNotContainsString('/internal/', $sitemap);
        self::assertSame(
            "User-agent: *\nAllow: /\n\nSitemap: https://example.org/docs/sitemap.xml\n",
            (string) file_get_contents($build . '/robots.txt'),
        );
        $llms = (string) file_get_contents($build . '/llms.txt');
        self::assertStringStartsWith("# Моя документация\n\n> Example documentation.\n", $llms);
        self::assertStringContainsString('(https://example.org/docs/ru/writing/)', $llms);
        self::assertStringContainsString('- [English](https://example.org/docs/en/)', $llms);
        self::assertStringContainsString('- [AI manifest](https://example.org/docs/ai.json)', $llms);

        // A consumer writes ai.json after the build, then verifies.
        file_put_contents($build . '/ai.json', "{}\n");
        $verification = $this->verify($site);
        self::assertSame([], $verification['broken']);
        self::assertSame('/docs/', $verification['deployment_base']);

        file_put_contents($build . '/sitemap.xml', str_replace(
            '</urlset>',
            "  <url>\n    <loc>https://example.org/elsewhere/</loc>\n  </url>\n</urlset>",
            $sitemap,
        ));
        $broken = $this->verify($site, false)['broken'];
        self::assertContains('@discovery-files', array_column($broken, 'reference'));
    }

    /** @param list<string> $selection */
    private function accept(string $site, array $selection): void
    {
        $plan = json_decode(
            $this->docara(['translations', 'accept', '--locale=en', ...$selection, '--dry-run', '--json'], $site, jsonOutput: true),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertNotSame([], $plan['accepted']);
        $this->docara(['translations', 'accept', '--apply=' . $plan['plan_id'], '--json'], $site);
    }

    /** @return array<string, mixed> */
    private function verify(string $site, bool $pass = true): array
    {
        return json_decode(
            $this->docara(['verify-static', 'build_production', '--no-ansi'], $site, jsonOutput: true, pass: $pass),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    /** @return array<string, mixed> */
    private function readJson(string $path): array
    {
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    /** @param array<string, mixed> $value */
    private function writeJson(string $path, array $value): void
    {
        file_put_contents($path, json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    }

    private function png(): string
    {
        $chunk = static fn (string $type, string $data): string => pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
        $raw = str_repeat("\x00" . str_repeat("\x30\x60\xc0", 4), 2);

        return "\x89PNG\r\n\x1a\n" . $chunk('IHDR', pack('NNCCCCC', 4, 2, 8, 2, 0, 0, 0))
            . $chunk('IDAT', (string) gzcompress($raw)) . $chunk('IEND', '');
    }

    /** @param list<string> $arguments */
    private function docara(array $arguments, string $cwd, bool $jsonOutput = false, bool $pass = true): string
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
        if ($pass) {
            self::assertSame(0, $process->getExitCode(), implode(' ', $arguments) . "\n" . $process->getErrorOutput() . $process->getOutput());
        }
        $output = $process->getOutput();
        if ($jsonOutput) {
            $start = strpos($output, '{');
            self::assertNotFalse($start, $output);
            $output = substr($output, $start);
        }

        return $output;
    }
}
