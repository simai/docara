<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;
use Simai\Docara\PortableSite\SiteDiscovery;
use Simai\Docara\PortableSite\SiteDiscoveryVerifier;
use Tests\TestCase;

final class SiteDiscoveryTest extends TestCase
{
    #[Test]
    public function the_site_url_is_the_public_origin_plus_the_base_path(): void
    {
        self::assertNull(SiteDiscovery::fromSite(['base_url' => '/']));
        $discovery = SiteDiscovery::fromSite(['base_url' => '/docara/', 'site_url' => 'https://Simai.GitHub.io/docara']);
        self::assertInstanceOf(SiteDiscovery::class, $discovery);
        self::assertSame('https://simai.github.io/docara/', $discovery->siteUrl);
        self::assertSame('https://simai.github.io', $discovery->origin);
        self::assertSame('https://simai.github.io/docara/ru/', $discovery->absolute('/docara/ru/'));
        self::assertSame('http://127.0.0.1:8000/', SiteDiscovery::fromSite(['site_url' => 'http://127.0.0.1:8000/'])?->siteUrl);

        foreach ([
            ['https://example.com/docs/', '/', 'SITE_URL_BASE_MISMATCH'],
            ['https://example.com/', '/docs/', 'SITE_URL_BASE_MISMATCH'],
            ['ftp://example.com/', '/', 'SITE_URL_INVALID'],
            ['https://example.com/?a=1', '/', 'SITE_URL_INVALID'],
            ['https://user@example.com/', '/', 'SITE_URL_INVALID'],
            ['https://example.com/../', '/', 'SITE_URL_INVALID'],
            ['/docs/', '/docs/', 'SITE_URL_INVALID'],
        ] as [$siteUrl, $base, $code]) {
            try {
                SiteDiscovery::fromSite(['site_url' => $siteUrl, 'base_url' => $base]);
                self::fail("[$siteUrl] was accepted.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($code, $exception->errorCode, $siteUrl);
            }
        }
        $this->expectException(PortableConfigurationException::class);
        (new SchemaRepository)->assertValid([
            'schema' => 'docara.site.v1',
            'preset' => 'docs',
            'framework_lock' => 'lock.json',
            'site_url' => 'https://example.com/docs/?x',
        ], 'site.schema.json');
    }

    #[Test]
    public function the_sitemap_lists_only_indexable_pages_with_their_translations(): void
    {
        $discovery = SiteDiscovery::fromSite(['base_url' => '/docs/', 'site_url' => 'https://example.com/docs/']);
        self::assertInstanceOf(SiteDiscovery::class, $discovery);
        $pages = $this->pages();
        $sitemap = $discovery->sitemap($pages);

        self::assertSame(<<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
  <url>
    <loc>https://example.com/docs/en/</loc>
    <xhtml:link rel="alternate" hreflang="en" href="https://example.com/docs/en/"/>
    <xhtml:link rel="alternate" hreflang="ru" href="https://example.com/docs/ru/"/>
  </url>
  <url>
    <loc>https://example.com/docs/ru/</loc>
    <xhtml:link rel="alternate" hreflang="en" href="https://example.com/docs/en/"/>
    <xhtml:link rel="alternate" hreflang="ru" href="https://example.com/docs/ru/"/>
  </url>
  <url>
    <loc>https://example.com/docs/ru/guide/</loc>
  </url>
</urlset>

XML, $sitemap);
        $monolingual = $discovery->sitemap([$pages[0]]);
        self::assertStringNotContainsString('xmlns:xhtml', $monolingual);
        self::assertSame("User-agent: *\nAllow: /\n\nSitemap: https://example.com/docs/sitemap.xml\n", $discovery->robots());
        self::assertFalse(SiteDiscovery::indexable(['url' => '/docs/404/']));
        self::assertFalse(SiteDiscovery::indexable(['url' => '/docs/ru/500/']));
        self::assertTrue(SiteDiscovery::indexable(['url' => '/docs/ru/4040/']));
    }

    #[Test]
    public function llms_txt_names_the_site_its_sections_languages_and_machine_readable_files(): void
    {
        $discovery = SiteDiscovery::fromSite([
            'base_url' => '/docs/',
            'site_url' => 'https://example.com/docs/',
            'discovery' => ['llms_links' => [['title' => 'AI [manifest]', 'path' => 'ai.json', 'description' => "Every\npage."]]],
        ]);
        self::assertInstanceOf(SiteDiscovery::class, $discovery);
        $llms = $discovery->llms(
            $this->pages(),
            [['title' => 'Guide', 'url' => '/docs/ru/guide/'], ['title' => 'Hidden', 'url' => '/docs/ru/hidden/']],
            'Docs',
            'Home  description.',
            [['label' => 'Русский', 'url' => '/docs/ru/', 'locale' => 'ru'], ['label' => 'English', 'url' => '/docs/en/', 'locale' => 'en']],
        );

        self::assertSame(<<<'TEXT'
# Docs

> Home description.

## Sections

- [Guide](https://example.com/docs/ru/guide/): Install it.

## Languages

- [Русский](https://example.com/docs/ru/)
- [English](https://example.com/docs/en/)

## Machine-readable

- [AI \[manifest\]](https://example.com/docs/ai.json): Every page.
- [Sitemap](https://example.com/docs/sitemap.xml)
- [Page metadata](https://example.com/docs/_docara/page-metadata.json): URL, title, locale and source of every page

TEXT, $llms);
    }

    #[Test]
    public function verify_static_checks_the_discovery_files_against_the_built_pages(): void
    {
        $discovery = SiteDiscovery::fromSite(['base_url' => '/docs/', 'site_url' => 'https://example.com/docs/']);
        self::assertInstanceOf(SiteDiscovery::class, $discovery);
        $pages = $this->pages();
        $records = [];
        foreach ($pages as $page) {
            $output = trim(substr($page['url'], strlen('/docs/')), '/') . '/index.html';
            $this->createSource([$output => '<!doctype html>']);
            $records[] = [
                'url' => $page['url'],
                'locale' => $page['locale'],
                'output' => $output,
                'translation_key' => $page['translation_key'],
                ...($page['translation_fallback'] === null ? [] : ['translation_fallback' => $page['translation_fallback']]),
                'resolved_page_plan' => [
                    'configuration' => ['base_url' => '/docs/', 'site_url' => 'https://example.com/docs/'],
                    'front_matter' => $page['noindex'] ? ['noindex' => true] : [],
                ],
            ];
        }
        $files = $discovery->files($pages, [['title' => 'Guide', 'url' => '/docs/ru/guide/']], 'Docs', null, []);
        $files['_docara/page-metadata.json'] = '{}';
        $this->createSource($files);
        $verifier = new SiteDiscoveryVerifier;
        self::assertSame([], $verifier->verify($this->tmp, $records));

        // A site without site_url is not checked.
        $plain = $records;
        foreach ($plain as &$record) {
            unset($record['resolved_page_plan']['configuration']['site_url']);
        }
        unset($record);
        self::assertSame([], $verifier->verify($this->tmp, $plain));

        file_put_contents($this->tmpPath('sitemap.xml'), str_replace(
            ['https://example.com/docs/ru/guide/', '</urlset>'],
            ['https://example.com/docs/ru/hidden/', "  <url>\n    <loc>https://example.com/other/</loc>\n  </url>\n</urlset>"],
            $files['sitemap.xml'],
        ));
        file_put_contents($this->tmpPath('llms.txt'), $files['llms.txt'] . "- [Gone](https://example.com/docs/gone/)\n- [Away](https://example.org/)\n");
        file_put_contents($this->tmpPath('robots.txt'), "User-agent: *\nDisallow: /\n");
        $problems = implode("\n", $verifier->verify($this->tmp, $records));
        self::assertStringContainsString('[robots.txt] must allow all crawlers', $problems);
        self::assertStringContainsString('lists [https://example.com/docs/ru/hidden/], which is not a built indexable page', $problems);
        self::assertStringContainsString('lists [https://example.com/other/] outside site_url', $problems);
        self::assertStringContainsString('misses the indexable page [https://example.com/docs/ru/guide/]', $problems);
        self::assertStringContainsString('links to [https://example.com/docs/gone/], which is not a file of the build', $problems);
        self::assertStringContainsString('links to [https://example.org/] outside site_url', $problems);

        file_put_contents($this->tmpPath('sitemap.xml'), '<urlset>');
        self::assertContains('[sitemap.xml] is not a well-formed sitemaps.org urlset.', $verifier->verify($this->tmp, $records));
        unlink($this->tmpPath('llms.txt'));
        self::assertSame(['[llms.txt] is missing or unsafe although site_url is set.'], $verifier->verify($this->tmp, $records));
    }

    /** @return list<array<string, mixed>> */
    private function pages(): array
    {
        $page = static fn (string $url, string $locale, string $key, string $description = '', bool $noindex = false, ?array $fallback = null): array => [
            'url' => $url,
            'locale' => $locale,
            'title' => $key,
            'description' => $description,
            'translation_key' => $key,
            'noindex' => $noindex,
            'translation_fallback' => $fallback,
        ];

        return [
            $page('/docs/ru/', 'ru', 'index.md'),
            $page('/docs/en/', 'en', 'index.md'),
            $page('/docs/ru/guide/', 'ru', 'guide.md', 'Install it.'),
            $page('/docs/en/guide/', 'en', 'guide.md', 'Held back.', fallback: ['status' => 'stale']),
            $page('/docs/ru/hidden/', 'ru', 'hidden.md', 'Internal.', noindex: true),
            $page('/docs/ru/404/', 'ru', '404.md'),
        ];
    }
}
