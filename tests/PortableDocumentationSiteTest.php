<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as PHPUnit;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Simai\Docara\ComponentCatalog\PublicComponentPage;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\PortableConfigurationException;
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
        $resolvedPlans = $this->json($build . '/.docara/resolved-page-plans.json');
        $atlasProjection = $this->json($build . '/.docara/design-atlas.json');
        $schemaProjection = $this->json($build . '/.docara/schema-reference.json');
        $exampleProjection = $this->json($build . '/.docara/examples.json');
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

        self::assertCount(128, $pages);
        $performanceProjection = json_decode(
            (string) file_get_contents($build . '/.docara/performance.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertSame([
            'design_atlas_sha256' => $atlasProjection['content_sha256'],
            'examples_sha256' => $exampleProjection['content_sha256'],
            'performance_sha256' => $performanceProjection['content_sha256'],
            'schema_reference_sha256' => $schemaProjection['content_sha256'],
        ], $resolvedPlans['build']['public_projections']);
        foreach ($pages as $page) {
            self::assertSame('pagebuilder_document_ir', $page['declarative_pipeline']['main_source']);
            self::assertSame('docara.document_ir.v1', $page['declarative_pipeline']['document_ir']['schema']);
            self::assertGreaterThan(0, $page['declarative_pipeline']['document_ir']['nodes']);
        }
        self::assertCount(263, $htmlPages);
        $nonIndexedOutput = $build . '/ru/demonstrator-results/composition-inheritance/page/index.html';
        $nonIndexedHash = hash_file('sha256', $nonIndexedOutput);
        $singleNonIndexed = (new PortableSiteBuilder(
            $filesystem,
            new PortableMarkdownRenderer,
        ))->build($site, $build, '/ru/demonstrator-results/composition-inheritance/page/');
        self::assertCount(1, $singleNonIndexed);
        self::assertSame($nonIndexedHash, hash_file('sha256', $nonIndexedOutput));
        self::assertFileDoesNotExist($build . '/ru/lang.json');
        self::assertCount(114, $search['documents']);
        self::assertCount(38, $catalog['entries']);
        self::assertCount(31, $supported);
        self::assertCount(5, $unavailable);
        self::assertFileDoesNotExist($build . '/.docara/component-catalog-pages.json');
        self::assertFileDoesNotExist($build . '/.docara/declarative-example-pages.json');
        self::assertFileDoesNotExist($build . '/_docara/declarative-examples.json');
        self::assertSame(
            15,
            $pages->filter(
                static fn (array $page): bool => str_starts_with((string) ($page['url'] ?? ''), '/ru/examples/')
                    && ($page['page_source_kind'] ?? null) === 'authored_markdown',
            )->count(),
        );
        self::assertCount(7, $redirectReceipt['redirects']);
        self::assertCount(128, $localeRouteReceipt['redirects']);
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

        $retiredComponentOverviews = [
            'block-docara',
            'containers',
            'framework',
            'inline-docara',
            'native-markdown',
            'project',
            'syntax',
        ];
        foreach ($retiredComponentOverviews as $route) {
            $redirect = collect($redirectReceipt['redirects'])->firstWhere('from', 'ru/components/' . $route);
            self::assertIsArray($redirect, $route);
            self::assertSame('ru/start/component-model', $redirect['to'], $route);
            $redirectHtml = (string) file_get_contents($build . '/ru/components/' . $route . '/index.html');
            self::assertStringContainsString('/ru/start/component-model/', $redirectHtml, $route);
            self::assertStringContainsString('content="noindex,follow"', $redirectHtml, $route);
        }
        $componentModel = (string) file_get_contents($build . '/ru/start/component-model/index.html');
        self::assertStringContainsString('Контейнеры и композиция', $componentModel);
        self::assertStringContainsString('SIMAI Framework', $componentModel);
        self::assertStringContainsString('Компоненты проекта', $componentModel);
        self::assertStringContainsString('MARKDOWN_RAW_HTML_FORBIDDEN', $componentModel);
        $settingsReference = (string) file_get_contents($build . '/ru/settings/index.html');
        self::assertStringContainsString('/layout/regions/', $settingsReference);
        self::assertStringContainsString('uniqueItems=true', $settingsReference);
        self::assertStringContainsString('minimum=0', $settingsReference);
        self::assertDoesNotMatchRegularExpression('~resources/schemas/[a-z0-9.-]+\\.schema\\.json#</code>~', $settingsReference);

        $catalogIndex = (string) file_get_contents($build . '/ru/components/index.html');
        $alertPage = (string) file_get_contents($build . '/ru/components/alert/index.html');
        self::assertSame(2, substr_count($catalogIndex, 'data-docara-table-scroll'));
        preg_match_all('~href="(/ru/components/[a-z0-9-]+/)"~', $catalogIndex, $componentLinks);
        self::assertSame(31, count(array_unique($componentLinks[1] ?? [])));
        self::assertStringContainsString('Текст и Markdown', $catalogIndex);
        self::assertStringContainsString('Содержательные блоки', $catalogIndex);
        self::assertStringContainsString('Код и данные', $catalogIndex);
        self::assertStringContainsString('Макет', $catalogIndex);
        self::assertStringContainsString('Медиа', $catalogIndex);
        self::assertStringContainsString('Inline и действия', $catalogIndex);
        self::assertStringNotContainsString('data-docara-component-index-view', $catalogIndex);
        self::assertStringNotContainsString('data-docara-component-catalog-index', $catalogIndex);
        self::assertStringContainsString('"code.copy":"Скопировать"', $alertPage);
        self::assertStringContainsString('"code.copied":"Скопировано"', $alertPage);
        $shellCss = (string) file_get_contents($build . '/_docara/declarative-shell.css');
        $shellJs = (string) file_get_contents($build . '/_docara/declarative-shell.js');
        self::assertStringContainsString('localizeCodeCopy', $shellJs);
        self::assertStringContainsString('showCopyState(false);', $shellJs);
        self::assertStringContainsString("event.data.type!=='docara:example-height'", $shellJs);
        self::assertStringContainsString("frame.style.blockSize=Math.max(32,Math.min(4096,Math.ceil(height)))+'px'", $shellJs);
        self::assertStringContainsString('link[data-docara-framework-asset][rel="stylesheet"]', $shellJs);
        self::assertStringContainsString('exampleEnvironment(frame).then(function(environment)', $shellJs);
        self::assertStringContainsString("Object.assign({type:'docara:example-measure'},environment)", $shellJs);
        self::assertStringContainsString('link[data-docara-declarative-shell-style][rel="stylesheet"]', $shellJs);
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
        self::assertSame(263, $report['html_pages'] ?? null);
        self::assertSame([], $report['broken'] ?? null);
        self::assertGreaterThan(0, $report['local_references_checked'] ?? 0);

        $resolvedPlans['build']['public_projections']['design_atlas_sha256'] = str_repeat('0', 64);
        file_put_contents(
            $build . '/.docara/resolved-page-plans.json',
            json_encode($resolvedPlans, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
        $verification->run();
        self::assertFalse($verification->isSuccessful());
        self::assertStringContainsString(
            'Public projections do not match the accepted build receipt.',
            $verification->getOutput(),
        );
    }

    #[Test]
    public function real_russian_site_uses_content_lang_and_rejects_the_retired_pack_field(): void
    {
        $root = dirname(__DIR__);
        $site = $this->temporary . '/badge-source-boundary';
        $filesystem = new Filesystem;
        $filesystem->copyDirectory($root . '/docs/site', $site);
        $site = realpath($site);
        self::assertIsString($site);
        $builder = new PortableSiteBuilder($filesystem, new PortableMarkdownRenderer);
        $builder->build($site, $site . '/build_baseline');
        $baseline = (string) file_get_contents($site . '/build_baseline/ru/index.html');
        self::assertStringContainsString('Поиск', $baseline);

        $langPath = $site . '/content/ru/lang.json';
        $lang = $this->json($langPath);
        $lang['search']['label'] = 'Поиск из content lang';
        file_put_contents(
            $langPath,
            json_encode($lang, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );

        $pages = $builder->build($site, $site . '/build_test');
        self::assertCount(128, $pages);
        self::assertStringContainsString(
            'Поиск из content lang',
            (string) file_get_contents($site . '/build_test/ru/index.html'),
        );

        $configuration = $this->json($site . '/docara.json');
        $configuration['locales']['ru']['language_pack'] = '@docara/ru';
        file_put_contents(
            $site . '/docara.json',
            json_encode($configuration, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
        try {
            $builder->build($site, $site . '/build_rejected');
            self::fail('The retired public language_pack field unexpectedly passed site validation.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('SCHEMA_VALIDATION_FAILED', $exception->errorCode);
        }
    }

    #[Test]
    public function independent_dist_sources_with_different_mtimes_produce_identical_complete_trees(): void
    {
        $filesystem = new Filesystem;
        $source = dirname(__DIR__) . '/docs/site';
        $firstSite = $this->temporary . '/dist-consumer-one/site';
        $secondSite = $this->temporary . '/dist-consumer-two/site';
        $filesystem->copyDirectory($source, $firstSite);
        $filesystem->copyDirectory($source, $secondSite);
        $firstSite = realpath($firstSite);
        $secondSite = realpath($secondSite);
        self::assertIsString($firstSite);
        self::assertIsString($secondSite);

        $firstTimestamp = 946684800;
        $secondTimestamp = 1893456000;
        foreach ($this->filesWithExtension($firstSite . '/content', 'md') as $path) {
            self::assertTrue(touch($path, $firstTimestamp));
        }
        foreach ($this->filesWithExtension($secondSite . '/content', 'md') as $path) {
            self::assertTrue(touch($path, $secondTimestamp));
        }

        $builder = new PortableSiteBuilder($filesystem, new PortableMarkdownRenderer);
        $firstBuild = $firstSite . '/build_dist';
        $secondBuild = $secondSite . '/build_dist';
        $builder->build($firstSite, $firstBuild);
        $builder->build($secondSite, $secondBuild);

        $firstFiles = $this->treeHashes($firstBuild);
        $secondFiles = $this->treeHashes($secondBuild);
        self::assertCount(2132, $firstFiles);
        self::assertSame($firstFiles, $secondFiles);
        self::assertArrayHasKey('_docara/page-metadata.json', $firstFiles);
        self::assertArrayHasKey('.docara/examples.json', $firstFiles);
        self::assertArrayHasKey('.docara/performance.json', $firstFiles);

        $metadata = $this->json($firstBuild . '/_docara/page-metadata.json');
        self::assertCount(128, $metadata['pages']);
        foreach ($metadata['pages'] as $page) {
            self::assertNull($page['updated_at']);
            self::assertNull($page['revision']);
            self::assertNull($page['author']);
        }
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

    /** @return array<string, string> */
    private function treeHashes(string $root): array
    {
        $hashes = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        );
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            $hash = hash_file('sha256', $file->getPathname());
            self::assertIsString($hash);
            $hashes[$relative] = $hash;
        }
        ksort($hashes, SORT_STRING);

        return $hashes;
    }

    /** @return array<string, mixed> */
    private function json(string $path): array
    {
        $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }
}
