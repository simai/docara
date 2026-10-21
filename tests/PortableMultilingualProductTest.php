<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Symfony\Component\Process\Process;

final class PortableMultilingualProductTest extends TestCase
{
    #[Test]
    public function physical_english_ltr_and_arabic_rtl_routes_use_the_same_full_and_single_pipeline(): void
    {
        $this->filesystem->copyDirectory(dirname(__DIR__) . '/stubs/portable', $this->tmp);
        $fixture = __DIR__ . '/fixtures/multilingual-site/content';
        foreach (['en', 'ar'] as $locale) {
            $this->filesystem->copyDirectory($fixture . '/' . $locale, $this->tmpPath('content/' . $locale));
        }
        $site = $this->json($this->tmpPath('docara.json'));
        $site['locales']['en'] = $this->locale('English', 'ltr', 'content/en', 'en');
        $site['locales']['ar'] = $this->locale('العربية', 'rtl', 'content/ar', 'ar');
        file_put_contents($this->tmpPath('docara.json'), CanonicalJson::encodePretty($site));

        $events = [];
        $builder = new PortableSiteBuilder(
            new Filesystem,
            new PortableMarkdownRenderer,
            observer: static function (string $event, string $subject) use (&$events): void {
                $events[] = [$event, $subject];
            },
        );
        $destination = $this->tmpPath('build_product');
        $first = $builder->build($this->tmp, $destination);
        // The root 404.html page adds one file: 42.
        self::assertCount(42, $first);
        self::assertFileExists($destination . '/404.html');
        $firstTree = $this->tree($destination);
        $builder->build($this->tmp, $destination);
        self::assertSame($firstTree, $this->tree($destination));

        foreach ([
            '/en/' => ['content/en/index.md', 'ltr', 'English fixture', 'Open documentation search'],
            '/ar/' => ['content/ar/index.md', 'rtl', 'نموذج عربي', 'فتح البحث في التوثيق'],
        ] as $route => [$owner, $direction, $heading, $searchLabel]) {
            $fullHash = hash_file('sha256', $destination . $route . 'index.html');
            $events = [];
            $result = $builder->build($this->tmp, $destination, $route);
            self::assertSame([['page.build', $owner]], $events);
            self::assertCount(1, $result);
            self::assertSame($fullHash, hash_file('sha256', $destination . $route . 'index.html'));
            $html = (string) file_get_contents($destination . $route . 'index.html');
            self::assertStringContainsString('dir="' . $direction . '"', $html);
            self::assertStringContainsString($heading, $html);
            self::assertStringContainsString($searchLabel, $html);
        }

        $receipt = $this->json($destination . '.docara/resolved-page-plans.json');
        self::assertSame('content/en/lang.json', $receipt['build']['locale_sources']['en']['path']);
        self::assertSame('content/ar/lang.json', $receipt['build']['locale_sources']['ar']['path']);
        self::assertSame('content/en/index.md', collect($receipt['pages'])->firstWhere('url', '/en/')['page_path']);
        self::assertSame('content/ar/index.md', collect($receipt['pages'])->firstWhere('url', '/ar/')['page_path']);

        $verify = new Process([PHP_BINARY, 'scripts/verify-static-build.php', $destination], dirname(__DIR__));
        $verify->run();
        self::assertSame(0, $verify->getExitCode(), $verify->getErrorOutput() . $verify->getOutput());
        self::assertStringContainsString('"broken": []', $verify->getOutput());
    }

    #[Test]
    public function a_page_showing_the_source_text_marks_that_text_with_the_source_language_and_direction(): void
    {
        $this->filesystem->copyDirectory(dirname(__DIR__) . '/stubs/portable', $this->tmp);
        $this->filesystem->deleteDirectory($this->tmpPath('content/ru'));
        $fixture = __DIR__ . '/fixtures/multilingual-site/content';
        $this->filesystem->copyDirectory($fixture . '/en', $this->tmpPath('content/en'));
        // A third-level heading also shows the outline in the mobile contents sheet.
        file_put_contents($this->tmpPath('content/en/index.md'), "\n### Shortcuts\n\nTab moves between controls.\n", FILE_APPEND);
        $this->filesystem->copyDirectory($fixture . '/en', $this->tmpPath('content/ar'));
        copy($fixture . '/ar/lang.json', $this->tmpPath('content/ar/lang.json'));
        file_put_contents($this->tmpPath('content/en/section.json'), "{\"schema\": \"docara.section.v1\", \"title\": \"Docs\"}\n");
        // Every Arabic page shows the English source, so Arabic has nothing to search.
        file_put_contents($this->tmpPath('content/ar/section.json'), "{\"schema\": \"docara.section.v1\", \"title\": \"التوثيق\", \"search\": {\"enabled\": false}}\n");
        $site = $this->json($this->tmpPath('docara.json'));
        $site['content_root'] = 'content/en';
        $site['default_locale'] = 'en';
        $site['locales'] = [
            'missing_page_policy' => 'skip',
            'en' => $this->locale('English', 'ltr', 'content/en', 'en'),
            'ar' => [...$this->locale('العربية', 'rtl', 'content/ar', 'ar'), 'fallbacks' => ['en']],
        ];
        $site['translation_tracking'] = [
            'enabled' => true,
            'source_locale' => 'en',
            'mode' => 'publish-current',
            'lock_file' => 'translations.lock.json',
        ];
        file_put_contents($this->tmpPath('docara.json'), CanonicalJson::encodePretty($site));

        $destination = $this->tmpPath('build_product');
        (new PortableSiteBuilder(new Filesystem, new PortableMarkdownRenderer))->build($this->tmp, $destination);

        $held = (string) file_get_contents($destination . '/ar/index.html');
        // The interface stays in the page locale ...
        self::assertStringContainsString('<html lang="ar" dir="rtl"', $held);
        self::assertMatchesRegularExpression('#<aside data-docara-translation-fallback="[a-z_]+" role="note" lang="ar" dir="rtl"#', $held);
        // ... and the source text, its headings and the outline entries read as English.
        self::assertSame(1, preg_match_all('#<article class="docara-prose[^"]*" lang="en" dir="ltr">#', $held));
        self::assertStringContainsString('English fixture', $held);
        self::assertSame(2, preg_match_all('#<ul class="docara-outline-list[^"]*" lang="en" dir="ltr">#', $held));
        self::assertStringContainsString('>Keyboard check</a>', $held);
        self::assertStringContainsString('>Shortcuts</a>', $held);

        $source = (string) file_get_contents($destination . '/en/index.html');
        self::assertStringNotContainsString('data-docara-translation-fallback', $source);
        self::assertMatchesRegularExpression('#<article class="docara-prose[^"]*">#', $source);
        self::assertDoesNotMatchRegularExpression('#<(article|ul) class="(docara-prose|docara-outline-list)[^"]*" lang=#', $source);
    }

    /** @return array<string, mixed> */
    private function locale(string $label, string $direction, string $contentRoot, string $prefix): array
    {
        return [
            'label' => $label,
            'direction' => $direction,
            'content_root' => $contentRoot,
            'public_prefix' => $prefix,
            'fallbacks' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function json(string $path): array
    {
        return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return array<string, string> */
    private function tree(string $root): array
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
