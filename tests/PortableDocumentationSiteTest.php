<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as PHPUnit;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Simai\Docara\ComponentCatalog\PublicComponentPage;
use Simai\Docara\File\Filesystem;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use SplFileInfo;
use Symfony\Component\Process\Process;

final class PortableDocumentationSiteTest extends PHPUnit
{
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

        self::assertNotEmpty($this->filesWithExtension($source . '/content', 'md'));

        $pages = (new PortableSiteBuilder(
            $filesystem,
            new PortableMarkdownRenderer,
        ))->build($site, $build);

        $htmlPages = $this->filesWithExtension($build, 'html');
        $catalog = $this->json($build . '/_docara/component-catalog.json');
        $redirectReceipt = $this->json($build . '/.docara/redirects.json');
        $localeRouteReceipt = $this->json($build . '/.docara/locale-routes.json');
        $search = $this->json($build . '/_docara/search-index.json');
        $supported = array_values(array_filter(
            $catalog['entries'],
            static fn (array $entry): bool => $entry['lifecycle'] === 'supported'
                && $entry['family'] !== 'framework_smart',
        ));
        $unavailable = array_values(array_filter(
            $catalog['entries'],
            static fn (array $entry): bool => $entry['lifecycle'] !== 'supported',
        ));
        $authoredComponentAliases = array_map(
            static fn (string $path): string => pathinfo($path, PATHINFO_FILENAME),
            $this->filesWithExtension($source . '/content/ru/components', 'md'),
        );
        $projectedSupported = array_values(array_filter(
            $supported,
            static fn (array $entry): bool => ! in_array(
                PublicComponentPage::slug($entry),
                $authoredComponentAliases,
                true,
            ),
        ));

        self::assertCount(103, $pages);
        foreach ($pages as $page) {
            self::assertSame('pagebuilder_document_ir', $page['declarative_pipeline']['main_source']);
            self::assertSame('docara.document_ir.v1', $page['declarative_pipeline']['document_ir']['schema']);
            self::assertGreaterThan(0, $page['declarative_pipeline']['document_ir']['nodes']);
        }
        self::assertCount(206, $htmlPages);
        $nonIndexedOutput = $build . '/ru/demonstrator-results/composition-inheritance/page/index.html';
        $nonIndexedHash = hash_file('sha256', $nonIndexedOutput);
        $singleNonIndexed = (new PortableSiteBuilder(
            $filesystem,
            new PortableMarkdownRenderer,
        ))->build($site, $build, '/ru/demonstrator-results/composition-inheritance/page/');
        self::assertCount(1, $singleNonIndexed);
        self::assertSame($nonIndexedHash, hash_file('sha256', $nonIndexedOutput));
        self::assertFileDoesNotExist($build . '/ru/lang.json');
        self::assertCount(89, $search['documents']);
        self::assertCount(37, $catalog['entries']);
        self::assertCount(30, $supported);
        self::assertCount(5, $unavailable);
        self::assertFileDoesNotExist($build . '/.docara/component-catalog-pages.json');
        self::assertFileDoesNotExist($build . '/.docara/declarative-example-pages.json');
        self::assertFileDoesNotExist($build . '/_docara/declarative-examples.json');
        self::assertSame(
            14,
            $pages->filter(
                static fn (array $page): bool => str_starts_with((string) ($page['url'] ?? ''), '/ru/examples/')
                    && ($page['page_source_kind'] ?? null) === 'authored_markdown',
            )->count(),
        );
        self::assertCount(0, $redirectReceipt['redirects']);
        self::assertCount(103, $localeRouteReceipt['redirects']);
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
        self::assertSame([], $projectedSupported);
        self::assertFileExists($build . '/ru/components/index.html');

        $catalogIndex = (string) file_get_contents($build . '/ru/components/index.html');
        $alertPage = (string) file_get_contents($build . '/ru/components/alert/index.html');
        self::assertStringContainsString('data-docara-component-index-view', $catalogIndex);
        self::assertStringNotContainsString('data-docara-component-catalog-index', $catalogIndex);
        self::assertStringContainsString('"code.copy":"Скопировать"', $alertPage);
        self::assertStringContainsString('"code.copied":"Скопировано"', $alertPage);
        $shellCss = (string) file_get_contents($build . '/_docara/declarative-shell.css');
        $shellJs = (string) file_get_contents($build . '/_docara/declarative-shell.js');
        self::assertStringContainsString('localizeCodeCopy', $shellJs);
        self::assertStringContainsString('showCopyState(false);', $shellJs);
        self::assertSame(1, substr_count($catalogIndex, 'class="docara-navigation docara-header-navigation"'));
        self::assertStringContainsString('docara-header-navigation-link h-d0', $catalogIndex);
        self::assertSame(1, substr_count($catalogIndex, 'data-docara-primary-navigation'));
        self::assertSame(1, substr_count($catalogIndex, 'id="docara-mobile-navigation"'));
        self::assertSame(
            1,
            substr_count(
                $catalogIndex,
                'data-docara-sheet-trigger aria-haspopup="dialog" aria-controls="docara-mobile-navigation"',
            ),
        );
        self::assertStringContainsString(
            '.docara-mobile-sheet{position:fixed;inset-block:0;inset-inline-start:0;inset-inline-end:auto;',
            $shellCss,
            'The mobile navigation sheet must follow the logical inline direction in LTR and RTL.',
        );
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
            '.docara-outline-rail{position:relative;align-self:stretch;box-shadow:inset var(--sf-px) var(--sf-0) var(--sf-0) var(--sf-outline-variant)}',
            $shellCss,
            'The desktop outline divider must span the row without clipping its active marker.',
        );
        self::assertStringContainsString(
            '.docara-outline-scroll{position:sticky;inset-block-start:4.5rem;block-size:calc(100vh - 4.5rem);direction:ltr}',
            $shellCss,
            'The Framework scrollbar root must remain aligned below the header.',
        );
        self::assertStringContainsString(
            '.docara-sidebar{align-self:stretch;border-inline-end:',
            $shellCss,
            'The desktop navigation divider must span the full layout row.',
        );
        self::assertStringContainsString(
            '.docara-sidebar-scroll{position:sticky;inset-block-start:3.5rem;block-size:calc(100vh - 3.5rem)}',
            $shellCss,
            'The navigation must use a bounded Framework scrollbar root.',
        );
        foreach ($unavailable as $entry) {
            self::assertStringNotContainsString(
                'data-docara-component-item="' . $entry['id'] . '"',
                $catalogIndex,
                (string) $entry['id'],
            );
            self::assertFileDoesNotExist($build . '/ru/components/' . $entry['id'] . '/index.html');
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
        self::assertSame(206, $report['html_pages'] ?? null);
        self::assertSame([], $report['broken'] ?? null);
        self::assertGreaterThan(0, $report['local_references_checked'] ?? 0);
    }

    #[Test]
    public function real_russian_site_uses_content_lang_and_not_package_component_prose(): void
    {
        $root = dirname(__DIR__);
        $site = $this->temporary . '/badge-source-boundary';
        $filesystem = new Filesystem;
        $filesystem->copyDirectory($root . '/docs/site', $site);
        $site = realpath($site);
        self::assertIsString($site);
        $languagePackPath = $site . '/language-pack-under-test.json';
        $languagePack = $this->json($root . '/resources/language-packs/ru.json');
        file_put_contents(
            $languagePackPath,
            json_encode($languagePack, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
        $configuration = $this->json($site . '/docara.json');
        $configuration['locales']['ru']['language_pack'] = 'language-pack-under-test.json';
        file_put_contents(
            $site . '/docara.json',
            json_encode($configuration, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
        $builder = new PortableSiteBuilder($filesystem, new PortableMarkdownRenderer);
        $builder->build($site, $site . '/build_baseline');
        $baselineBadgeHash = hash_file('sha256', $site . '/build_baseline/ru/components/badge/index.html');
        $baselineIndexHash = hash_file('sha256', $site . '/build_baseline/ru/components/index.html');
        $baselineExamplesHash = hash_file('sha256', $site . '/build_baseline/ru/examples/index.html');

        $languagePack['messages']['examples.title'] = 'FORBIDDEN PACKAGE MESSAGE';
        $languagePack['components']['docara.badge'] = ['title' => 'FORBIDDEN PACKAGE PROSE'];
        file_put_contents(
            $languagePackPath,
            json_encode($languagePack, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );

        $pages = $builder->build($site, $site . '/build_test');

        self::assertCount(103, $pages);
        self::assertSame(
            $baselineBadgeHash,
            hash_file('sha256', $site . '/build_test/ru/components/badge/index.html'),
        );
        self::assertSame(
            $baselineIndexHash,
            hash_file('sha256', $site . '/build_test/ru/components/index.html'),
        );
        self::assertSame(
            $baselineExamplesHash,
            hash_file('sha256', $site . '/build_test/ru/examples/index.html'),
        );
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
