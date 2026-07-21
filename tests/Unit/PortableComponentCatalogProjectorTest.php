<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableComponentCatalogProjector;
use Simai\Docara\PortableSite\PortableHtmlRenderer;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Tests\TestCase;

final class PortableComponentCatalogProjectorTest extends TestCase
{
    #[Test]
    public function it_projects_one_searchable_static_detail_page_for_every_supported_entry(): void
    {
        $build = $this->buildPortableSite();
        $catalog = $this->json($build . '/_docara/component-catalog.json');
        $receipt = $this->json($build . '/.docara/component-catalog-pages.json');
        $supported = array_values(array_filter(
            $catalog['entries'],
            static fn (array $entry): bool => $entry['lifecycle'] === 'supported',
        ));
        $supportedIds = array_column($supported, 'id');

        self::assertSame('docara.component_catalog_pages.v1', $receipt['schema']);
        self::assertSame($catalog['content_sha256'], $receipt['catalog_content_sha256']);
        self::assertSame(
            hash('sha256', CanonicalJson::encode([
                'catalog_content_sha256' => $receipt['catalog_content_sha256'],
                'index' => $receipt['index'],
                'pages' => $receipt['pages'],
            ])),
            $receipt['content_sha256'],
        );
        self::assertMatchesRegularExpression(
            '/\A[a-f0-9]{64}\z/D',
            $receipt['index']['contract_fragment_sha256'],
        );
        self::assertSame($supportedIds, array_column($receipt['pages'], 'id'));
        self::assertFileExists($build . '/components/catalog/index.html');
        self::assertSame(
            $supportedIds,
            array_map(
                static fn (array $page): string => basename(dirname($page['output'])),
                $receipt['pages'],
            ),
        );

        $index = (string) file_get_contents($build . '/components/catalog/index.html');
        self::assertStringContainsString('data-docara-component-catalog-index', $index);
        self::assertStringContainsString('data-docara-component-filter', $index);
        self::assertStringContainsString('data-docara-component-filter-query', $index);
        self::assertStringContainsString('data-docara-component-filter-family', $index);
        self::assertStringContainsString('data-docara-component-filter-availability', $index);
        self::assertStringContainsString('data-docara-component-filter-status', $index);
        self::assertStringContainsString('data-docara-component-filter-reset', $index);
        self::assertStringContainsString('data-docara-component-filter-empty', $index);
        self::assertStringContainsString('data-docara-component-filter-controller', $index);
        self::assertSame(17, substr_count($index, 'data-docara-component-item='));
        self::assertStringContainsString(
            'data-docara-component-family="framework_smart"',
            $index,
        );
        self::assertStringContainsString(
            'data-docara-component-availability="unavailable"',
            $index,
        );
        self::assertStringContainsString(
            'data-docara-component-search="ui.button Кнопка Выводит визуальный элемент действия;',
            $index,
        );
        self::assertStringContainsString(
            'data-docara-component-search="ui.tabs Вкладки Доступный набор вкладок',
            $index,
        );
        self::assertStringContainsString('Smart-компоненты Simai Framework', $index);
        self::assertStringContainsString('Недоступно сейчас', $index);
        self::assertStringContainsString('>Колонки<', $index);
        self::assertStringContainsString('>Уведомление<', $index);
        self::assertStringContainsString('>Вкладки<', $index);
        self::assertStringContainsString(
            'Используйте заголовки и последовательные разделы в порядке исходного текста.',
            $index,
        );
        self::assertStringContainsString(
            'Опубликовать канонический доступный контракт и манифест вкладок',
            $index,
        );
        self::assertStringContainsString(
            'Закреплённые исходники Core и Smart не содержат полного принятого контракта доступности.',
            $index,
        );
        self::assertStringContainsString('simai/ui and larena/ui', $index);
        self::assertStringContainsString('data-docara-component-gap="ui.tabs"', $index);
        self::assertStringNotContainsString('>Columns<', $index);
        self::assertStringNotContainsString('>Alert<', $index);
        self::assertStringNotContainsString('>Tabs<', $index);
        self::assertStringNotContainsString('fetch(', $index);
        self::assertStringContainsString('docara-document-link flex flex-col', $index);
        self::assertStringContainsString('h-full w-full', $index);
        foreach ($supportedIds as $id) {
            self::assertStringContainsString(
                'href="/components/catalog/' . $id . '/"',
                $index,
            );

            $detailPath = $build . '/components/catalog/' . $id . '/index.html';
            self::assertFileExists($detailPath);
            $detail = (string) file_get_contents($detailPath);
            self::assertStringContainsString('data-docara-component-detail="' . $id . '"', $detail);
            self::assertStringContainsString('data-docara-component-demo="' . $id . '"', $detail);
            self::assertStringContainsString('>Пример<', $detail);
            self::assertStringContainsString('>Вызов<', $detail);
            self::assertStringContainsString('Ограничения и источник', $detail);
            self::assertStringNotContainsString('fetch(', $detail);
        }

        $alert = (string) file_get_contents($build . '/components/catalog/ui.alert/index.html');
        self::assertStringContainsString('<h1 id="уведомление">Уведомление</h1>', $alert);
        self::assertStringContainsString('Доступное имя', $alert);
        self::assertStringContainsString('Кратко называет уведомление для вспомогательных технологий.', $alert);
        self::assertStringContainsString('Информация — info', $alert);
        self::assertStringContainsString('Обычное состояние', $alert);
        self::assertStringContainsString('"type":"info"', $alert);
        self::assertStringContainsString('class="language-markdown overflow-auto"', $alert);
        self::assertStringContainsString('<table class="min-w-full">', $alert);
        self::assertStringContainsString('<code class="wrap-none">aria-label</code>', $alert);
        self::assertStringContainsString('<code>min_length</code>', $alert);
        self::assertStringContainsString('<code>max_length</code>', $alert);
        self::assertStringContainsString('<code>pattern</code>', $alert);
        self::assertStringContainsString('data-docara-component-details-summary', $alert);
        self::assertStringNotContainsString('Optional author override', $alert);
        self::assertStringNotContainsString('closable=true is not admitted', $alert);

        $button = (string) file_get_contents($build . '/components/catalog/ui.button/index.html');
        self::assertStringContainsString('Связи параметров', $button);
        self::assertStringContainsString('allowed_combinations', $button);
        self::assertStringContainsString('loading', $button);
        self::assertStringContainsString('disabled', $button);
        self::assertStringContainsString('<code>mirrors</code>', $button);

        $search = $this->json($build . '/_docara/search-index.json');
        $indexedUrls = array_column($search['documents'], 'url');
        self::assertContains('/components/catalog/', $indexedUrls);
        foreach ($supportedIds as $id) {
            self::assertContains('/components/catalog/' . $id . '/', $indexedUrls);
        }
    }

    #[Test]
    public function catalog_shell_and_entry_metadata_follow_the_inherited_english_locale(): void
    {
        $build = $this->buildPortableSite('/', 'en');
        $index = (string) file_get_contents($build . '/components/catalog/index.html');
        $alert = (string) file_get_contents($build . '/components/catalog/ui.alert/index.html');

        self::assertStringContainsString('>Component catalog<', $index);
        self::assertStringContainsString('>Find a component<', $index);
        self::assertStringContainsString('>All types<', $index);
        self::assertStringContainsString('>All availability states<', $index);
        self::assertStringContainsString('>Reset filters<', $index);
        self::assertStringContainsString('>Docara components<', $index);
        self::assertStringContainsString('>Simai Framework Smart components<', $index);
        self::assertStringContainsString('>Unavailable in this build<', $index);
        self::assertStringNotContainsString('Каталог компонентов', $index);
        self::assertStringNotContainsString('Недоступно сейчас', $index);

        self::assertStringContainsString('<h1 id="alert">Alert</h1>', $alert);
        self::assertStringContainsString('>Example<', $alert);
        self::assertStringContainsString('>Call<', $alert);
        self::assertStringContainsString('>Parameters<', $alert);
        self::assertStringContainsString('>States<', $alert);
        self::assertStringContainsString('Limitations and source', $alert);
        self::assertStringContainsString(
            'Optional author override for exact manifest property [aria-label].',
            $alert,
        );
        self::assertStringContainsString('Verified Smart component', $alert);
        self::assertStringContainsString(
            'This example uses the exact pinned Simai Framework contract.',
            $alert,
        );
        self::assertStringNotContainsString('Проверенный Smart-компонент', $alert);
        self::assertStringNotContainsString('Пример использует точный закреплённый контракт', $alert);
        self::assertStringNotContainsString('>Пример<', $alert);
        self::assertStringNotContainsString('Ограничения и источник', $alert);
    }

    #[Test]
    public function detail_pages_use_exact_fixture_and_render_hashes_with_one_generic_shell(): void
    {
        $build = $this->buildPortableSite();
        $receipt = $this->json($build . '/.docara/component-catalog-pages.json');

        foreach ($receipt['pages'] as $page) {
            self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $page['catalog_entry_sha256']);
            self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $page['example_sha256']);
            self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $page['rendered_fragment_sha256']);
            self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $page['contract_fragment_sha256']);
            self::assertSame(
                hash_file('sha256', dirname(__DIR__, 2) . '/' . $page['example_ref']),
                $page['example_sha256'],
            );

            $html = (string) file_get_contents($build . '/' . $page['output']);
            self::assertStringContainsString(
                'data-docara-component-source="' . $page['id'] . '"',
                $html,
            );
            self::assertStringContainsString(
                'data-docara-example-source-sha256="' . $page['example_sha256'] . '"',
                $html,
            );
            self::assertStringContainsString(
                'data-docara-example-render-sha256="' . $page['rendered_fragment_sha256'] . '"',
                $html,
            );
            self::assertSame(1, substr_count($html, 'data-docara-component-detail='));
        }
    }

    #[Test]
    public function details_are_hidden_from_the_left_menu_but_keep_context_and_adjacency(): void
    {
        $build = $this->buildPortableSite();
        $receipt = $this->json($build . '/.docara/component-catalog-pages.json');
        self::assertGreaterThanOrEqual(3, count($receipt['pages']));
        $middle = $receipt['pages'][1];
        $html = (string) file_get_contents($build . '/' . $middle['output']);
        $xpath = $this->xpath($html);

        self::assertSame(
            1,
            $xpath->query('//nav[@data-docara-breadcrumbs]//*[@aria-current="page"]')?->length,
        );
        self::assertGreaterThanOrEqual(
            3,
            $xpath->query('//nav[@data-docara-breadcrumbs]//*[@class]')?->length ?? 0,
        );
        self::assertSame(
            1,
            $xpath->query(
                '//aside[contains(concat(" ", normalize-space(@class), " "), " docara-sidebar ")]'
                . '//a[@href="/components/catalog/"]',
            )?->length,
        );
        self::assertSame(
            0,
            $xpath->query(
                '//aside[contains(concat(" ", normalize-space(@class), " "), " docara-sidebar ")]'
                . '//a[starts-with(@href, "/components/catalog/") and @href!="/components/catalog/"]',
            )?->length,
        );
        self::assertSame(1, $xpath->query('//nav[@data-docara-previous-next]/a[@rel="prev"]')?->length);
        self::assertSame(1, $xpath->query('//nav[@data-docara-previous-next]/a[@rel="next"]')?->length);
    }

    #[Test]
    public function component_routes_and_catalog_assets_follow_a_nested_base_url(): void
    {
        $build = $this->buildPortableSite('/project/docs/');
        $receipt = $this->json($build . '/.docara/component-catalog-pages.json');

        self::assertSame('/project/docs/components/catalog/', $receipt['index']['route']);
        foreach ($receipt['pages'] as $page) {
            self::assertStringStartsWith('/project/docs/components/catalog/', $page['route']);
        }

        $index = (string) file_get_contents($build . '/components/catalog/index.html');
        self::assertStringContainsString('href="/project/docs/components/catalog/', $index);
    }

    #[Test]
    public function smart_demo_assets_are_scoped_to_smart_details(): void
    {
        $build = $this->buildPortableSite();
        $diagnostics = $this->json($build . '/.docara/resolved-page-plans.json');
        $byUrl = array_column($diagnostics['pages'], null, 'url');
        $native = json_encode(
            $byUrl['/components/catalog/native.code/']['component_runtime']['asset_plan'],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        );
        $alert = json_encode(
            $byUrl['/components/catalog/ui.alert/']['component_runtime']['asset_plan'],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        );
        $button = json_encode(
            $byUrl['/components/catalog/ui.button/']['component_runtime']['asset_plan'],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        );

        self::assertStringNotContainsString('smart/alert/js/alert.js', $native);
        self::assertStringNotContainsString('smart/buttons/js/buttons.js', $native);
        self::assertStringContainsString('smart/alert/js/alert.js', $alert);
        self::assertStringNotContainsString('smart/buttons/js/buttons.js', $alert);
        self::assertStringContainsString('smart/buttons/js/buttons.js', $button);
        self::assertStringNotContainsString('smart/alert/js/alert.js', $button);
    }

    #[Test]
    public function catalog_plans_do_not_inherit_an_unrelated_first_authored_page(): void
    {
        $this->copyPortableFixture($this->tmp);
        file_put_contents($this->tmpPath('content/aaa.md'), "# Unrelated\n");
        file_put_contents(
            $this->tmpPath('content/aaa.page.json'),
            json_encode([
                'schema' => 'docara.page.v1',
                'locale' => 'ru',
                'settings' => ['theme' => 'dark'],
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
        $build = $this->tmpPath('build_local');
        $this->builder()->build($this->tmp, $build);
        $diagnostics = $this->json($build . '/.docara/resolved-page-plans.json');
        $catalogPages = array_values(array_filter(
            $diagnostics['pages'],
            static fn (array $page): bool => str_starts_with(
                (string) $page['output'],
                'components/catalog/',
            ),
        ));

        self::assertCount(13, $catalogPages);
        foreach ($catalogPages as $page) {
            $plan = $page['resolved_page_plan'];
            $sources = array_column($plan['trace'], 'source');
            self::assertNotContains('content/aaa.md', $sources);
            self::assertNotContains('content/aaa.page.json', $sources);
            self::assertSame('system', $plan['configuration']['settings']['theme']);
            self::assertSame('ru', $plan['configuration']['default_locale']);
            self::assertArrayNotHasKey('locale', $plan['configuration']);
            self::assertSame('generated-content', $plan['trace'][array_key_last($plan['trace'])]['role']);
            self::assertStringStartsWith(
                '@docara/component-catalog/',
                $plan['trace'][array_key_last($plan['trace'])]['source'],
            );
        }
    }

    #[Test]
    public function catalog_preserves_inherited_layout_search_reading_and_index_navigation(): void
    {
        $this->copyPortableFixture($this->tmp);
        $this->filesystem->ensureDirectoryExists($this->tmpPath('content/components/catalog'));
        file_put_contents(
            $this->tmpPath('content/components/catalog/section.json'),
            json_encode([
                'schema' => 'docara.section.v1',
                'layout' => ['max_width' => 'full'],
                'navigation' => ['hidden' => true, 'order' => 321],
                'search' => ['enabled' => false, 'indexed' => false],
                'reading' => [
                    'breadcrumbs' => false,
                    'toc' => false,
                    'toc_depth' => 2,
                    'previous_next' => false,
                ],
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );

        $build = $this->tmpPath('build_local');
        $this->builder()->build($this->tmp, $build);
        $diagnostics = $this->json($build . '/.docara/resolved-page-plans.json');
        $catalogPages = array_values(array_filter(
            $diagnostics['pages'],
            static fn (array $page): bool => str_starts_with(
                (string) $page['output'],
                'components/catalog/',
            ),
        ));

        self::assertCount(13, $catalogPages);
        foreach ($catalogPages as $page) {
            $configuration = $page['resolved_page_plan']['configuration'];
            self::assertSame('full', $configuration['layout']['max_width']);
            self::assertFalse($configuration['search']['enabled']);
            self::assertFalse($configuration['search']['indexed']);
            self::assertFalse($configuration['reading']['breadcrumbs']);
            self::assertFalse($configuration['reading']['toc']);
            self::assertSame(2, $configuration['reading']['toc_depth']);
            self::assertFalse($configuration['reading']['previous_next']);
            self::assertTrue($configuration['navigation']['hidden']);
            self::assertSame(321, $configuration['navigation']['order']);

            $html = (string) file_get_contents($build . '/' . $page['output']);
            self::assertStringContainsString('data-width="full"', $html);
            self::assertStringNotContainsString('data-docara-breadcrumbs', $html);
            self::assertStringNotContainsString('data-docara-previous-next', $html);
            self::assertStringNotContainsString('data-docara-search-trigger', $html);
        }

        $search = $this->json($build . '/_docara/search-index.json');
        foreach ($search['documents'] as $document) {
            self::assertStringNotContainsString('/components/catalog/', (string) $document['url']);
        }
    }

    #[Test]
    public function catalog_assets_reject_an_intermediate_symbolic_link(): void
    {
        $package = $this->tmpPath('unsafe-package');
        $outside = $this->tmpPath('outside-assets');
        $this->filesystem->ensureDirectoryExists($package . '/resources/component-catalog');
        $this->filesystem->ensureDirectoryExists($outside);
        file_put_contents($outside . '/docara-mark.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
        if (! @symlink($outside, $package . '/resources/component-catalog/assets')) {
            self::markTestSkipped('Symbolic links are not supported by this test environment.');
        }

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('COMPONENT_CATALOG_ASSET_INVALID');

        (new PortableComponentCatalogProjector(
            new PortableMarkdownRenderer,
            $package,
        ))->assets();
    }

    #[Test]
    public function catalog_assets_reject_a_hardlinked_file(): void
    {
        $package = $this->tmpPath('hardlinked-package');
        $outside = $this->tmpPath('outside-mark.svg');
        $target = $package . '/resources/component-catalog/assets/docara-mark.svg';
        $this->filesystem->ensureDirectoryExists(dirname($target));
        file_put_contents($outside, '<svg xmlns="http://www.w3.org/2000/svg"/>');
        if (! @link($outside, $target)) {
            self::markTestSkipped('Hard links are not supported by this test environment.');
        }

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('COMPONENT_CATALOG_ASSET_INVALID');

        (new PortableComponentCatalogProjector(
            new PortableMarkdownRenderer,
            $package,
        ))->assets();
    }

    #[Test]
    public function authored_content_cannot_shadow_a_generated_catalog_route(): void
    {
        $this->copyPortableFixture($this->tmp);
        $this->filesystem->ensureDirectoryExists($this->tmpPath('content/components/catalog'));
        file_put_contents($this->tmpPath('content/components/catalog/index.md'), "# Shadow\n");

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('COMPONENT_CATALOG_ROUTE_COLLISION');

        $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
    }

    private function buildPortableSite(string $baseUrl = '/', string $locale = 'ru'): string
    {
        $this->copyPortableFixture($this->tmp);
        if ($baseUrl !== '/' || $locale !== 'ru') {
            $sitePath = $this->tmpPath('docara.json');
            $site = $this->json($sitePath);
            $site['base_url'] = $baseUrl;
            $site['default_locale'] = $locale;
            file_put_contents(
                $sitePath,
                json_encode($site, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            );
        }

        $build = $this->tmpPath('build_local');
        $this->builder()->build($this->tmp, $build);

        return $build;
    }

    private function builder(): PortableSiteBuilder
    {
        return new PortableSiteBuilder(
            new Filesystem,
            new PortableMarkdownRenderer,
            new PortableHtmlRenderer,
        );
    }

    private function copyPortableFixture(string $destination): void
    {
        $this->filesystem->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $destination);
        rename($destination . '/content/ru', $destination . '/content-legacy');
        rmdir($destination . '/content');
        rename($destination . '/content-legacy', $destination . '/content');
        $site = $this->json($destination . '/docara.json');
        $site['content_root'] = 'content';
        unset($site['locales']);
        $site['locale_routing'] = [
            'strategy' => 'default_unprefixed',
            'root' => 'default_locale',
            'detect_browser_language' => false,
            'legacy_unprefixed_redirects' => false,
        ];
        file_put_contents(
            $destination . '/docara.json',
            json_encode($site, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
        $redirects = $this->json($destination . '/redirects.json');
        $redirects['redirects'] = array_values(array_map(
            static fn (array $redirect): array => [
                'from' => $redirect['from'],
                'to' => preg_replace('#^ru/#', '', (string) $redirect['to']),
            ],
            array_filter(
                $redirects['redirects'],
                static fn (array $redirect): bool => ! str_starts_with((string) $redirect['from'], 'ru/'),
            ),
        ));
        file_put_contents(
            $destination . '/redirects.json',
            json_encode($redirects, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
    }

    /** @return array<string, mixed> */
    private function json(string $path): array
    {
        $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }
}
