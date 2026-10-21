<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * The 404.html page (docara.json `not_found`), built and verified through the
 * real command line: Docara's own content, a site's 404.md, the off switch
 * and the portal refusal.
 */
final class NotFoundPageBuildTest extends TestCase
{
    #[Test]
    public function a_site_gets_a_noindexed_404_page_whose_links_work_from_any_depth(): void
    {
        $site = $this->tmpPath('site');
        $this->filesystem->ensureDirectoryExists($site);
        $this->docara(['init', '--template=minimal', '--lang=ru'], $site);
        $configuration = $this->readJson($site . '/docara.json');
        $configuration['base_url'] = '/docs/';
        $configuration['site_url'] = 'https://example.org/docs/';
        $this->writeJson($site . '/docara.json', $configuration);
        $lang = $this->readJson($site . '/content/ru/lang.json');
        $lang['error'] = ['not_found' => ['home' => 'Вернуться на главную']];
        $this->writeJson($site . '/content/ru/lang.json', $lang);

        $this->docara(['build', 'production'], $site);
        $build = $site . '/build_production';
        self::assertFileExists($build . '/404.html');
        self::assertDirectoryDoesNotExist($build . '/404');
        $page = (string) file_get_contents($build . '/404.html');

        self::assertStringContainsString('<html lang="ru"', $page);
        self::assertStringContainsString('<meta name="robots" content="noindex">', $page);
        self::assertStringContainsString('<title>Страница не найдена — Моя документация</title>', $page);
        self::assertMatchesRegularExpression('#<h1 id="[^"]+">Страница не найдена</h1>#u', $page);
        self::assertStringContainsString('<p>По этому адресу страницы нет. Возможно, её перенесли или в адресе опечатка.</p>', $page);
        // The site's lang.json overrides one string; the others are Docara's.
        self::assertStringContainsString('<li><a href="/docs/">Вернуться на главную</a></li>', $page);
        self::assertStringContainsString('<li><a href="/docs/" data-docara-search-open>Искать в документации</a></li>', $page);
        self::assertStringNotContainsString('hreflang=', $page);
        // The normal shell: header, navigation, search and theme.
        self::assertStringContainsString('data-docara-region="header"', $page);
        self::assertStringContainsString('data-docara-region="sidebar"', $page);
        self::assertStringContainsString('href="/docs/writing/"', $page);
        self::assertMatchesRegularExpression('#<script defer src="/docs/_docara/search\.js\?docara_v=[a-f0-9]{64}" data-docara-search-runtime>#', $page);
        self::assertMatchesRegularExpression('#data-docara-search-index="/docs/_docara/search-index\.json\?docara_v=[a-f0-9]{64}"#', $page);
        self::assertStringContainsString('<!-- docara:pager empty -->', $page);
        self::assertStringNotContainsString('data-docara-breadcrumbs', $page);

        // The host serves the page at any depth: nothing is addressed relatively.
        preg_match_all('/\s(?:href|src|srcset|poster|action|data-docara-search-index)="([^"]*)"/', $page, $urls);
        self::assertNotSame([], $urls[1]);
        foreach ($urls[1] as $url) {
            self::assertMatchesRegularExpression('~\A(?:/docs/|#|https://)~', $url, "[$url] is not addressed from the site root.");
        }

        // Out of the navigation, search, sitemap, llms.txt and page metadata.
        foreach (['index.html', 'writing/index.html', 'components/index.html'] as $output) {
            self::assertStringNotContainsString('404', (string) file_get_contents($build . '/' . $output), $output);
        }
        $search = $this->readJson($build . '/_docara/search-index.json');
        self::assertNotContains('/docs/404.html', array_column($search['documents'], 'url'));
        self::assertCount(3, $search['documents']);
        self::assertStringNotContainsString('404', (string) file_get_contents($build . '/sitemap.xml'));
        self::assertStringNotContainsString('404', (string) file_get_contents($build . '/llms.txt'));
        $metadata = $this->readJson($build . '/_docara/page-metadata.json');
        self::assertNotContains('/docs/404.html', array_column($metadata['pages'], 'url'));

        $plans = $this->readJson($site . '/build_production.docara/resolved-page-plans.json');
        $records = array_values(array_filter($plans['pages'], static fn (array $record): bool => $record['output'] === '404.html'));
        self::assertCount(1, $records);
        self::assertTrue($records[0]['not_found']);
        self::assertSame('/docs/404.html', $records[0]['url']);
        self::assertSame('noindex', $records[0]['robots']);
        self::assertSame('generated_not_found', $records[0]['page_source_kind']);
        self::assertFalse($records[0]['resolved_page_plan']['configuration']['search']['indexed']);
        self::assertTrue($records[0]['resolved_page_plan']['configuration']['navigation']['hidden']);

        $verification = $this->verify($site);
        self::assertSame([], $verification['broken']);
        self::assertSame(4, $verification['html_pages']);
    }

    #[Test]
    public function a_site_overrides_the_404_page_with_its_own_markdown_or_turns_it_off(): void
    {
        $site = $this->tmpPath('site');
        $this->filesystem->ensureDirectoryExists($site);
        $this->docara(['init', '--template=minimal', '--lang=en'], $site);

        $this->docara(['build', 'production'], $site);
        $page = (string) file_get_contents($site . '/build_production/404.html');
        self::assertStringContainsString('<html lang="en"', $page);
        self::assertMatchesRegularExpression('#<h1 id="[^"]+">Page not found</h1>#', $page);
        self::assertStringContainsString('>Go to the home page</a>', $page);
        self::assertStringContainsString('>Search the documentation</a>', $page);

        $this->filesystem->ensureDirectoryExists($site . '/content/en/assets');
        file_put_contents($site . '/content/en/assets/lost.png', $this->png());
        file_put_contents(
            $site . '/content/en/404.md',
            "---\ndescription: Nothing lives here.\n---\n\n# Lost in the docs\n\n"
            . "Try [writing pages](../writing/), the [components](/components/) or the [home page](../?from=404).\n\n"
            . "![Lost](/assets/lost.png)\n",
        );
        $this->docara(['build', 'production'], $site);
        $build = $site . '/build_production';
        self::assertDirectoryDoesNotExist($build . '/404');
        $page = (string) file_get_contents($build . '/404.html');
        self::assertMatchesRegularExpression('#<h1 id="[^"]+">Lost in the docs</h1>#', $page);
        self::assertStringNotContainsString('Page not found', $page);
        self::assertStringNotContainsString('data-docara-not-found-links', $page);
        self::assertStringContainsString('<meta name="robots" content="noindex">', $page);
        self::assertStringContainsString('<meta name="description" content="Nothing lives here.">', $page);
        // Relative URLs resolve from the page's ordinary route, /404/.
        self::assertStringContainsString('href="/writing/"', $page);
        self::assertStringContainsString('href="/components/"', $page);
        self::assertStringContainsString('href="/?from=404"', $page);
        self::assertStringContainsString('src="/assets/lost.png"', $page);
        self::assertFileExists($build . '/assets/lost.png');
        self::assertStringNotContainsString('404', (string) file_get_contents($build . '/writing/index.html'));
        $plans = $this->readJson($site . '/build_production.docara/resolved-page-plans.json');
        $record = array_values(array_filter($plans['pages'], static fn (array $record): bool => $record['output'] === '404.html'))[0];
        self::assertSame('authored_markdown', $record['page_source_kind']);
        self::assertSame('content/en/404.md', $record['page_path']);
        self::assertSame([], $this->verify($site)['broken']);

        $configuration = $this->readJson($site . '/docara.json');
        $configuration['not_found'] = ['enabled' => false];
        $this->writeJson($site . '/docara.json', $configuration);
        $this->docara(['build', 'production'], $site);
        // Off: the build is what it was before 404.html existed.
        self::assertFileDoesNotExist($build . '/404.html');
        self::assertFileExists($build . '/404/index.html');
        self::assertSame([], $this->verify($site)['broken']);
    }

    #[Test]
    public function a_portal_build_refuses_to_enable_the_404_page(): void
    {
        $site = $this->tmpPath('site');
        $this->filesystem->ensureDirectoryExists($site);
        $this->docara(['init', '--template=minimal', '--lang=ru'], $site);
        $configuration = $this->readJson($site . '/docara.json');
        $configuration['base_url'] = '/ru/product/docs/';
        $configuration['site_url'] = 'https://portal.example/ru/product/docs/';
        $configuration['portal'] = ['product' => 'product-docs', 'home_url' => 'https://portal.example/ru/'];
        $configuration['not_found'] = ['enabled' => true];
        $this->writeJson($site . '/docara.json', $configuration);

        $output = $this->docara(['build', 'portal'], $site, pass: false);
        self::assertStringContainsString('PORTAL_NOT_FOUND_PAGE_FORBIDDEN', $output);
        self::assertDirectoryDoesNotExist($site . '/build_portal');
    }

    /** @return array<string, mixed> */
    private function verify(string $site): array
    {
        return json_decode(
            $this->docara(['verify-static', 'build_production', '--no-ansi'], $site, jsonOutput: true),
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
        $output = $process->getOutput() . $process->getErrorOutput();
        if ($jsonOutput) {
            $output = $process->getOutput();
            $start = strpos($output, '{');
            self::assertNotFalse($start, $output);
            $output = substr($output, $start);
        }

        return $output;
    }
}
