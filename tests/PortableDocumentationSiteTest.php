<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as PHPUnit;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Simai\Docara\File\Filesystem;
use Simai\Docara\PortableSite\PortableHtmlRenderer;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use SplFileInfo;
use Symfony\Component\Process\Process;

final class PortableDocumentationSiteTest extends PHPUnit
{
    private const RETIRED_COMPONENT_SLUGS = [
        'alert',
        'button',
        'card',
        'code',
        'cta',
        'features',
        'steps',
        'table',
        'tabs',
    ];

    private string $temporary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporary = sys_get_temp_dir() . '/docara-documentation-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->temporary, 0700));
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->temporary);

        parent::tearDown();
    }

    #[Test]
    public function real_documentation_build_matches_the_exact_product_matrix_and_static_verifier(): void
    {
        $source = dirname(__DIR__) . '/docs/site';
        $site = $this->temporary . '/documentation-site';
        $filesystem = new Filesystem;
        $filesystem->copyDirectory($source, $site);
        $site = realpath($site);
        self::assertIsString($site);
        $build = $site . '/build_test';

        self::assertCount(66, $this->filesWithExtension($source . '/content', 'md'));

        $pages = (new PortableSiteBuilder(
            $filesystem,
            new PortableMarkdownRenderer,
            new PortableHtmlRenderer,
        ))->build($site, $build);

        $htmlPages = $this->filesWithExtension($build, 'html');
        $catalog = $this->json($build . '/_docara/component-catalog.json');
        $receipt = $this->json($build . '/.docara/component-catalog-pages.json');
        $exampleReceipt = $this->json($build . '/.docara/declarative-example-pages.json');
        $redirectReceipt = $this->json($build . '/.docara/redirects.json');
        $localeRouteReceipt = $this->json($build . '/.docara/locale-routes.json');
        $search = $this->json($build . '/_docara/search-index.json');
        $supported = array_values(array_filter(
            $catalog['entries'],
            static fn (array $entry): bool => $entry['lifecycle'] === 'supported',
        ));
        $unavailable = array_values(array_filter(
            $catalog['entries'],
            static fn (array $entry): bool => $entry['lifecycle'] !== 'supported',
        ));

        self::assertCount(93, $pages);
        self::assertCount(271, $htmlPages);
        self::assertCount(79, $search['documents']);
        self::assertCount(17, $catalog['entries']);
        self::assertCount(12, $supported);
        self::assertCount(5, $unavailable);
        self::assertCount(12, $receipt['pages']);
        self::assertCount(13, $exampleReceipt['pages']);
        self::assertCount(18, $redirectReceipt['redirects']);
        self::assertCount(93, $localeRouteReceipt['redirects']);
        $rootLocaleRoutes = array_values(array_filter(
            $localeRouteReceipt['redirects'],
            static fn (array $redirect): bool => $redirect['kind'] === 'root',
        ));
        self::assertCount(1, $rootLocaleRoutes);
        self::assertSame('/ru/', $rootLocaleRoutes[0]['target_url']);
        $extensionsSearchDocument = array_values(array_filter(
            $search['documents'],
            static fn (array $document): bool => $document['url'] === '/ru/development/extensions/',
        ));
        self::assertCount(1, $extensionsSearchDocument);
        self::assertStringContainsString(
            'расширение',
            mb_strtolower(implode(' ', [
                $extensionsSearchDocument[0]['title'],
                $extensionsSearchDocument[0]['description'],
                ...array_column($extensionsSearchDocument[0]['headings'], 'text'),
                $extensionsSearchDocument[0]['text'],
            ])),
            'The development page must be discoverable by the exact reader query [расширение].',
        );
        self::assertSame(
            13,
            1 + count($receipt['pages']),
            'The generated catalogue surface must be one index plus twelve supported details.',
        );
        self::assertSame(
            array_column($supported, 'id'),
            array_column($receipt['pages'], 'id'),
        );
        self::assertFileExists($build . '/' . $receipt['index']['output']);

        $catalogIndex = (string) file_get_contents($build . '/' . $receipt['index']['output']);
        $shellCss = (string) file_get_contents($build . '/_docara/declarative-shell.css');
        self::assertStringContainsString(
            'scroll-margin-block-start:4.5rem',
            $shellCss,
            'Heading anchors must reserve space for the compact sticky documentation header.',
        );
        self::assertStringContainsString(
            'scroll-margin-block-start:4rem',
            $shellCss,
            'Mobile heading anchors must reserve space for the compact mobile header.',
        );
        self::assertStringContainsString(
            '.docara-outline-rail{align-self:stretch;border-inline-start:',
            $shellCss,
            'The desktop outline divider must span the full layout row.',
        );
        self::assertStringContainsString(
            '.docara-outline-rail>[data-docara-section]{position:sticky;',
            $shellCss,
            'Only the outline content, not its full-height divider rail, should be sticky.',
        );
        self::assertStringContainsString(
            '.docara-sidebar{align-self:stretch;border-inline-end:',
            $shellCss,
            'The desktop navigation divider must span the full layout row.',
        );
        self::assertStringContainsString(
            '.docara-sidebar>[data-docara-section]{position:sticky;',
            $shellCss,
            'Only the navigation content, not its full-height divider rail, should be sticky.',
        );
        foreach ($unavailable as $entry) {
            self::assertStringContainsString(
                'data-docara-component-gap="' . $entry['id'] . '"',
                $catalogIndex,
                (string) $entry['id'],
            );
        }

        foreach (self::RETIRED_COMPONENT_SLUGS as $slug) {
            self::assertContains(
                "components/$slug",
                array_column($redirectReceipt['redirects'], 'from'),
                "Retired manual component route [$slug] has no declarative migration redirect.",
            );
            $redirectHtml = (string) file_get_contents($build . "/components/$slug/index.html");
            self::assertStringContainsString(
                '<meta name="robots" content="noindex,follow">',
                $redirectHtml,
                "Retired manual component route [$slug] is not a safe redirect page.",
            );
        }

        $verification = new Process([
            PHP_BINARY,
            dirname(__DIR__) . '/scripts/verify-static-build.php',
            $build,
        ]);
        $verification->setTimeout(60);
        $verification->run();

        self::assertTrue(
            $verification->isSuccessful(),
            $verification->getErrorOutput() . "\n" . $verification->getOutput(),
        );
        $report = json_decode(
            $verification->getOutput(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertSame('docara.static_build_verification.v1', $report['schema'] ?? null);
        self::assertSame(271, $report['html_pages'] ?? null);
        self::assertSame([], $report['broken'] ?? null);
        self::assertGreaterThan(0, $report['local_references_checked'] ?? 0);
    }

    /** @return list<string> */
    private function filesWithExtension(string $root, string $extension): array
    {
        $paths = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        );
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === strtolower($extension)) {
                $paths[] = $file->getPathname();
            }
        }
        sort($paths, SORT_STRING);

        return $paths;
    }

    /** @return array<string, mixed> */
    private function json(string $path): array
    {
        $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }
}
