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
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Tests\TestCase;

final class PortableComponentCatalogProjectorTest extends TestCase
{
    #[Test]
    public function it_projects_one_navigable_static_detail_page_for_every_supported_entry(): void
    {
        $build = $this->buildPortableSite();
        $catalog = $this->json($build . '/_docara/component-catalog.json');
        $receipt = $this->json($build . '/.docara/component-catalog-pages.json');
        $supported = array_values(array_filter(
            $catalog['entries'],
            static fn (array $entry): bool => $entry['lifecycle'] === 'supported'
                && $entry['family'] !== 'framework_smart',
        ));
        $supportedIds = array_column($supported, 'id');
        $authoredIds = [
            'docara.alert',
            'docara.backlinks',
            'docara.banner',
            'docara.button',
            'docara.card',
            'docara.details',
            'docara.download',
            'docara.icon',
            'docara.kbd',
            'docara.hero',
            'native.code',
            'native.footnotes_and_sources',
            'native.headings_and_text',
            'native.links_and_images',
            'native.lists_and_quotes',
            'native.table',
        ];
        $generated = array_values(array_filter(
            $supported,
            static fn (array $entry): bool => ! in_array($entry['id'], $authoredIds, true),
        ));
        $generatedIds = array_column($generated, 'id');
        $generatedSlugs = array_map(self::publicSlug(...), $generated);
        $allIds = array_column($catalog['entries'], 'id');

        $expectedPublicIds = [
            'docara.alert',
            'docara.backlinks',
            'docara.badge',
            'docara.banner',
            'docara.button',
            'docara.card',
            'docara.code',
            'docara.details',
            'docara.diagram',
            'docara.download',
            'docara.embed',
            'docara.example',
            'docara.figure',
            'docara.grid',
            'docara.hero',
            'docara.html',
            'docara.icon',
            'docara.kbd',
            'docara.logos',
            'docara.math',
            'docara.media',
            'docara.steps',
            'docara.tabs',
            'docara.tree',
            'native.code',
            'native.footnotes_and_sources',
            'native.headings_and_text',
            'native.links_and_images',
            'native.lists_and_quotes',
            'native.table',
        ];
        $actualPublicIds = $supportedIds;
        sort($expectedPublicIds, SORT_STRING);
        sort($actualPublicIds, SORT_STRING);
        self::assertSame($expectedPublicIds, $actualPublicIds);

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
        self::assertSame($generatedIds, array_column($receipt['pages'], 'id'));
        self::assertFileExists($build . '/components/index.html');
        self::assertSame(
            $generatedSlugs,
            array_map(
                static fn (array $page): string => basename(dirname($page['output'])),
                $receipt['pages'],
            ),
        );

        $index = (string) file_get_contents($build . '/components/index.html');
        self::assertStringContainsString('data-docara-component-catalog-index', $index);
        self::assertStringNotContainsString('data-docara-component-filter', $index);
        self::assertStringContainsString('>Компоненты<', $index);
        self::assertStringContainsString('>Текст и код<', $index);
        self::assertStringContainsString('>Структура<', $index);
        self::assertStringContainsString('>Медиа<', $index);
        self::assertStringContainsString('>Действия и сообщения<', $index);
        self::assertStringContainsString('class="flex flex-col gap-2"', $index);
        self::assertStringNotContainsString('lg:grid-col-4', $index);
        self::assertSame(4, substr_count($index, 'data-docara-component-group='));
        self::assertSame(count($supportedIds), substr_count($index, 'data-docara-component-item='));
        self::assertStringNotContainsString('<table', $index);
        self::assertStringNotContainsString('>Тип<', $index);
        self::assertStringNotContainsString('>Для чего нужен<', $index);
        self::assertStringNotContainsString('Smart-компонент SIMAI Framework', $index);
        self::assertStringNotContainsString('<code>docara.', $index);
        self::assertStringNotContainsString('<code>native.', $index);
        self::assertStringNotContainsString('<code>ui.', $index);
        self::assertStringNotContainsString('>Колонки<', $index);
        self::assertStringContainsString('>Уведомление<', $index);
        self::assertStringContainsString('>Вкладки<', $index);
        self::assertStringNotContainsString('data-docara-component-gap=', $index);
        self::assertStringNotContainsString('>Columns<', $index);
        self::assertStringNotContainsString('>Alert<', $index);
        self::assertStringNotContainsString('>Tabs<', $index);
        self::assertStringNotContainsString('fetch(', $index);
        self::assertStringNotContainsString('docara-document-link flex flex-col', $index);
        foreach ($supported as $entry) {
            $id = $entry['id'];
            $slug = self::publicSlug($entry);
            self::assertStringContainsString(
                'href="/components/' . $slug . '/"',
                $index,
            );

            $detailPath = $build . '/components/' . $slug . '/index.html';
            self::assertFileExists($detailPath);
            $detail = (string) file_get_contents($detailPath);
            if (in_array($id, $authoredIds, true)) {
                self::assertStringContainsString('<h1 ', $detail);
                self::assertStringContainsString('data-docara-example=', $detail);
                self::assertStringContainsString('data-docara-example-tab="example"', $detail);
                self::assertStringContainsString('data-docara-example-tab="markdown"', $detail);
                self::assertStringContainsString('data-docara-example-copy', $detail);
            }
            if ($id === 'docara.alert') {
                self::assertStringContainsString('<h1 id="уведомление">Уведомление</h1>', $detail);
                self::assertSame(5, substr_count($detail, 'data-docara-block="alert"'));
                self::assertStringContainsString('Параметр <code>type</code>', $detail);
                self::assertStringContainsString('Параметр <code>variant</code>', $detail);
                self::assertStringContainsString('data-docara-code-block', $detail);

                continue;
            }
            if ($id === 'docara.details') {
                self::assertStringContainsString('<h1 id="раскрывающийся-блок">Раскрывающийся блок</h1>', $detail);
                self::assertSame(3, substr_count($detail, 'data-docara-block="details"'));

                continue;
            }
            if ($id === 'docara.backlinks') {
                self::assertStringContainsString('<h1 id="обратные-ссылки">Обратные ссылки</h1>', $detail);
                self::assertStringContainsString('data-docara-block="backlinks"', $detail);

                continue;
            }
            if ($id === 'docara.banner') {
                self::assertStringContainsString('<h1 id="баннер">Баннер</h1>', $detail);
                self::assertSame(4, substr_count($detail, 'data-docara-block="banner"'));

                continue;
            }
            if ($id === 'docara.download') {
                self::assertStringContainsString('<h1 id="скачивание">Скачивание</h1>', $detail);
                self::assertSame(3, substr_count($detail, 'data-docara-block="download"'));

                continue;
            }
            if ($id === 'docara.button') {
                self::assertStringContainsString('<h1 id="кнопка-ссылка">Кнопка-ссылка</h1>', $detail);
                self::assertStringContainsString('class="sf-button ', $detail);

                continue;
            }
            if ($id === 'docara.icon') {
                self::assertStringContainsString('<h1 id="значок">Значок</h1>', $detail);
                self::assertStringContainsString('class="docara-icon inline-grid"', $detail);

                continue;
            }
            if ($id === 'docara.kbd') {
                self::assertStringContainsString('<h1 id="клавиатурный-ввод">Клавиатурный ввод</h1>', $detail);
                self::assertStringContainsString('<kbd class="inline-flex ', $detail);

                continue;
            }
            if ($id === 'docara.card') {
                self::assertStringContainsString('<h1 id="карточка">Карточка</h1>', $detail);
                self::assertStringContainsString('data-docara-block="card"', $detail);

                continue;
            }
            if ($id === 'docara.hero') {
                self::assertStringContainsString('<h1 id="первый-экран">Первый экран</h1>', $detail);
                self::assertStringContainsString('data-docara-block="hero"', $detail);

                continue;
            }
            if ($id === 'native.headings_and_text') {
                self::assertStringContainsString('<h1 id="заголовки-и-текст">Заголовки и текст</h1>', $detail);
                self::assertStringContainsString('Не пропускайте уровни заголовков', $detail);

                continue;
            }
            if ($id === 'native.lists_and_quotes') {
                self::assertStringContainsString('<h1 id="списки-и-цитаты">Списки и цитаты</h1>', $detail);
                self::assertStringContainsString('<blockquote', $detail);

                continue;
            }
            if ($id === 'native.links_and_images') {
                self::assertStringContainsString('<h1 id="ссылки-и-изображения">Ссылки и изображения</h1>', $detail);
                self::assertStringContainsString('alt="Знак Docara"', $detail);

                continue;
            }
            if ($id === 'native.table') {
                self::assertStringContainsString('<h1 id="таблица-markdown">Таблица Markdown</h1>', $detail);
                self::assertStringContainsString('data-docara-table-scroll', $detail);

                continue;
            }
            if ($id === 'native.code') {
                self::assertStringContainsString('<h1 id="код">Код</h1>', $detail);
                self::assertStringContainsString('<code class="language-php">', $detail);

                continue;
            }
            if ($id === 'native.footnotes_and_sources') {
                self::assertStringContainsString('<h1 id="сноски-и-источники">Сноски и источники</h1>', $detail);
                self::assertStringContainsString('role="doc-noteref"', $detail);
                self::assertStringContainsString('role="doc-backlink"', $detail);

                continue;
            }
            self::assertStringContainsString('data-docara-component-detail="' . $id . '"', $detail);
            self::assertStringContainsString('data-docara-component-demo="' . $id . '"', $detail);
            self::assertStringContainsString('data-docara-component-example', $detail);
            self::assertStringContainsString('data-docara-component-source="' . $id . '"', $detail);
            self::assertStringContainsString('data-docara-component-source-display="' . $id . '"', $detail);
            self::assertStringContainsString('>Пример<', $detail);
            self::assertDoesNotMatchRegularExpression(
                '/<h[2-6][^>]*>\s*(?:Пример|Параметры|Важно|Варианты)\s*<\/h[2-6]>/u',
                $detail,
            );
            self::assertLessThan(
                strpos($detail, 'data-docara-component-parameters') ?: PHP_INT_MAX,
                strpos($detail, 'data-docara-component-source-display="' . $id . '"'),
                "Component [$id] must show the main example before parameter sections.",
            );
            $parameters = is_array($entry['authoring']['parameters'] ?? null)
                ? array_values($entry['authoring']['parameters'])
                : [];
            self::assertSame(
                count($parameters),
                substr_count($detail, 'data-docara-component-parameter="'),
                "Component [$id] must render exactly one section per parameter.",
            );
            foreach ($parameters as $parameter) {
                if (! is_array($parameter) || ! is_string($parameter['name'] ?? null)) {
                    continue;
                }
                self::assertStringContainsString(
                    'data-docara-component-parameter="' . $parameter['name'] . '"',
                    $detail,
                );
            }
            self::assertStringContainsString('role="tablist"', $detail);
            self::assertStringContainsString('data-docara-example-tab="example"', $detail);
            self::assertStringContainsString('data-docara-example-tab="markdown"', $detail);
            self::assertStringContainsString('data-docara-example-copy', $detail);
            self::assertDoesNotMatchRegularExpression(
                '/<h[1-6][^>]*>\s*(?:О компоненте|Состояния|Что учесть)\s*<\/h[1-6]>/u',
                $detail,
            );
            self::assertStringNotContainsString('data-docara-component-metadata', $detail);
            self::assertStringNotContainsString('data-docara-component-source-reference', $detail);
            self::assertStringNotContainsString('data-docara-component-variants', $detail);
            self::assertStringNotContainsString('data-docara-component-parameter-examples', $detail);
            self::assertStringNotContainsString('fetch(', $detail);
        }
        foreach (array_diff($allIds, $supportedIds) as $id) {
            self::assertStringNotContainsString('data-docara-component-item="' . $id . '"', $index);
        }

        self::assertFileDoesNotExist($build . '/components/ui.alert/index.html');
        self::assertFileDoesNotExist($build . '/components/ui.button/index.html');

        $docaraAlert = (string) file_get_contents($build . '/components/alert/index.html');
        self::assertStringContainsString('<h1 id="уведомление">Уведомление</h1>', $docaraAlert);
        self::assertStringContainsString('<sf-icon icon="info"', $docaraAlert);
        self::assertStringContainsString('<sf-icon icon="check_circle"', $docaraAlert);
        self::assertStringContainsString('<sf-icon icon="warning"', $docaraAlert);
        self::assertStringContainsString('<sf-icon icon="error"', $docaraAlert);
        self::assertStringContainsString(':::alert {type=info variant=default}', $docaraAlert);
        self::assertStringNotContainsString('data-docara-component-detail', $docaraAlert);
        self::assertSame(5, substr_count($docaraAlert, 'data-docara-block="alert"'));
        self::assertStringNotContainsString('>Тип уведомления <code>type</code>', $docaraAlert);
        self::assertStringNotContainsString('>Оформление <code>variant</code>', $docaraAlert);
        self::assertStringNotContainsString('docara-variant:', $docaraAlert);

        $badge = (string) file_get_contents($build . '/components/badge/index.html');
        foreach (['type', 'scheme', 'size'] as $parameter) {
            self::assertStringContainsString(
                'data-docara-component-parameter-example="' . $parameter . '"',
                $badge,
            );
            self::assertStringContainsString(
                'data-docara-example="component-badge-parameter-' . $parameter . '"',
                $badge,
            );
        }
        self::assertSame(3, substr_count($badge, 'data-docara-component-parameter-example='));
        self::assertStringContainsString(':badge[Новое]{type=tonal scheme=primary size=1}', $badge);
        self::assertStringContainsString(':badge[Основной]{type=main scheme=primary size=1}', $badge);
        self::assertStringContainsString(':badge[Основная]{scheme=primary size=1}', $badge);
        self::assertStringContainsString(':badge[Маленький]{size=1/3}', $badge);
        self::assertStringContainsString('<h2 id="тип-бейджа">Тип бейджа</h2>', $badge);
        self::assertStringContainsString(
            '<p>Параметр <code>type</code> определяет, как бейдж выделяется на странице.</p>',
            $badge,
        );
        self::assertStringContainsString('<table class="table table-border table-stripe">', $badge);
        self::assertStringContainsString('<th>Значение</th><th>Результат</th>', $badge);
        self::assertStringNotContainsString('<th>Значения</th>', $badge);
        self::assertStringNotContainsString('<th>Назначение</th>', $badge);
        self::assertStringContainsString('<code>on-surface</code>', $badge);
        self::assertStringContainsString(
            'data-docara-component-parameters><section data-docara-component-parameter="type" class="m-bottom-1">',
            $badge,
        );
        self::assertStringNotContainsString(
            'border-bottom-1 border-outline-variant p-y-2',
            $badge,
        );
        self::assertStringNotContainsString('docara-parameter:', $badge);

        $nativeCode = (string) file_get_contents(
            $build . '/components/code/index.html',
        );
        self::assertStringNotContainsString('data-docara-component-source="native.code"', $nativeCode);
        self::assertStringContainsString('<h1 id="код">Код</h1>', $nativeCode);
        self::assertStringContainsString(
            '```php',
            $nativeCode,
        );
        self::assertSame(3, substr_count($nativeCode, 'data-docara-code-block'));

        $search = $this->json($build . '/_docara/search-index.json');
        $indexedUrls = array_column($search['documents'], 'url');
        self::assertContains('/components/', $indexedUrls);
        foreach ($supportedSlugs as $slug) {
            self::assertContains('/components/' . $slug . '/', $indexedUrls);
        }
    }

    #[Test]
    public function catalog_shell_and_entry_metadata_follow_the_inherited_english_locale(): void
    {
        $build = $this->buildPortableSite('/', 'en');
        $index = (string) file_get_contents($build . '/components/index.html');
        $alert = (string) file_get_contents($build . '/components/alert/index.html');

        self::assertStringContainsString('>Components<', $index);
        self::assertStringNotContainsString('>Find a component<', $index);
        self::assertStringContainsString('>Text and code<', $index);
        self::assertStringContainsString('>Structure<', $index);
        self::assertStringContainsString('>Media<', $index);
        self::assertStringContainsString('>Actions and messages<', $index);
        self::assertStringNotContainsString('>Type<', $index);
        self::assertStringNotContainsString('>What it is for<', $index);
        self::assertStringNotContainsString('>Docara component<', $index);
        self::assertStringNotContainsString('>SIMAI Framework Smart component<', $index);
        self::assertStringNotContainsString('>Unavailable in this build<', $index);
        self::assertStringNotContainsString('Каталог компонентов', $index);
        self::assertStringNotContainsString('Недоступно сейчас', $index);

        self::assertStringContainsString('<h1 id="alert">Alert</h1>', $alert);
        self::assertStringContainsString('The <code>type</code> parameter ', $alert);
        self::assertStringContainsString('<th>Value</th><th>Result</th>', $alert);
        self::assertStringContainsString('>Example<', $alert);
        self::assertStringContainsString('data-docara-example-tab="example"', $alert);
        self::assertStringNotContainsString('<h2 id="result">Result</h2>', $alert);
        self::assertStringNotContainsString('<h2 id="parameters">Parameters</h2>', $alert);
        self::assertStringNotContainsString('<h2 id="important">Important</h2>', $alert);
        self::assertStringNotContainsString('>Call<', $alert);
        self::assertStringNotContainsString('>States<', $alert);
        self::assertStringNotContainsString('>What to consider<', $alert);
        self::assertStringNotContainsString('>Source<', $alert);
        self::assertStringNotContainsString('>Variants and states<', $alert);
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
            self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $page['contract_fragment_sha256']);
            if ($page['lifecycle'] !== 'supported') {
                self::assertNull($page['example_ref']);
                self::assertNull($page['example_sha256']);
                self::assertNull($page['rendered_fragment_sha256']);
                $html = (string) file_get_contents($build . '/' . $page['output']);
                self::assertStringContainsString('data-docara-component-unavailable', $html);
                self::assertSame(1, substr_count($html, 'data-docara-component-detail='));

                continue;
            }
            self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $page['example_sha256']);
            self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $page['rendered_fragment_sha256']);
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
    public function accepted_prototype_capabilities_remain_executable_catalog_fixtures(): void
    {
        $hero = (string) file_get_contents(
            dirname(__DIR__, 2) . '/docs/site/content/ru/components/hero.md',
        );
        foreach (['variant=split', 'variant=compact', 'variant=centered', '![Компоненты Docara]'] as $marker) {
            self::assertStringContainsString($marker, $hero);
        }

        $fixtures = [
            'docara.logos.ru.md' => [
                'docara-variant:state.text',
                'docara-variant:state.linked',
                'docara-variant:state.image',
                'tone=muted',
            ],
            'docara.example.ru.md' => [
                ':::example {label="Интерактивный пример"}',
                '```html',
                '```css',
                '```javascript',
            ],
            'docara.diagram.ru.md' => [':::diagram', 'flowchart LR'],
            'docara.math.ru.md' => [':::math {display=inline', ':::math {display=block'],
            'docara.html.ru.md' => [':::html', 'Изолированный HTML'],
            'docara.tabs.ru.md' => [':::tabs', '### Composer', '### Вручную', '### Результат'],
        ];

        foreach ($fixtures as $fixture => $markers) {
            $path = dirname(__DIR__, 2) . '/resources/component-catalog/examples/' . $fixture;
            self::assertFileExists($path);
            $source = (string) file_get_contents($path);

            foreach ($markers as $marker) {
                self::assertStringContainsString($marker, $source, $fixture . ': ' . $marker);
            }
        }
    }

    #[Test]
    public function details_are_available_from_the_left_menu_and_keep_context_and_adjacency(): void
    {
        $build = $this->buildPortableSite();
        $receipt = $this->json($build . '/.docara/component-catalog-pages.json');
        $catalog = $this->json($build . '/_docara/component-catalog.json');
        $publicDetails = count(array_filter(
            $catalog['entries'],
            static fn (array $entry): bool => $entry['lifecycle'] === 'supported'
                && $entry['family'] !== 'framework_smart',
        ));
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
                . '//a[@href="/components/"]',
            )?->length,
        );
        self::assertSame(
            $publicDetails,
            $xpath->query(
                '//aside[contains(concat(" ", normalize-space(@class), " "), " docara-sidebar ")]'
                . '//a[starts-with(@href, "/components/") and @href!="/components/"]',
            )?->length,
        );
        self::assertSame(
            1,
            $xpath->query(
                '//aside[contains(concat(" ", normalize-space(@class), " "), " docara-sidebar ")]'
                . '//a[@href="' . $middle['route'] . '" and @aria-current="page"]',
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

        self::assertSame('/project/docs/components/', $receipt['index']['route']);
        foreach ($receipt['pages'] as $page) {
            self::assertStringStartsWith('/project/docs/components/', $page['route']);
        }

        $index = (string) file_get_contents($build . '/components/index.html');
        self::assertStringContainsString('href="/project/docs/components/', $index);
    }

    #[Test]
    public function public_catalog_does_not_publish_framework_smart_details(): void
    {
        $build = $this->buildPortableSite();
        $diagnostics = $this->json($build . '/.docara/resolved-page-plans.json');
        $byUrl = array_column($diagnostics['pages'], null, 'url');
        $native = json_encode(
            $byUrl['/components/code/']['component_runtime']['asset_plan'],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        );

        $nativeCalls = array_column(
            $byUrl['/components/code/']['component_runtime']['normalized_calls'],
            'id',
        );

        self::assertStringNotContainsString('smart/alert/js/alert.js', $native);
        self::assertStringContainsString('smart/buttons/js/buttons.js', $native);
        self::assertNotContains('ui.alert', $nativeCalls);
        self::assertNotContains('ui.button', $nativeCalls);
        self::assertArrayNotHasKey('/components/ui.alert/', $byUrl);
        self::assertArrayNotHasKey('/components/ui.button/', $byUrl);
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
        $receipt = $this->json($build . '/.docara/component-catalog-pages.json');
        $diagnostics = $this->json($build . '/.docara/resolved-page-plans.json');
        $catalogPages = array_values(array_filter(
            $diagnostics['pages'],
            static fn (array $page): bool => str_starts_with(
                (string) $page['output'],
                'components/',
            ) && ($page['page_source_kind'] ?? null) === 'generated_projection',
        ));

        self::assertCount(count($receipt['pages']) + 1, $catalogPages);
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
        $this->filesystem->ensureDirectoryExists($this->tmpPath('content/components'));
        file_put_contents(
            $this->tmpPath('content/components/section.json'),
            json_encode([
                'schema' => 'docara.section.v1',
                'layout' => ['container' => ['max' => 8]],
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
        $receipt = $this->json($build . '/.docara/component-catalog-pages.json');
        $diagnostics = $this->json($build . '/.docara/resolved-page-plans.json');
        $catalogPages = array_values(array_filter(
            $diagnostics['pages'],
            static fn (array $page): bool => str_starts_with(
                (string) $page['output'],
                'components/',
            ) && ($page['page_source_kind'] ?? null) === 'generated_projection',
        ));

        self::assertCount(count($receipt['pages']) + 1, $catalogPages);
        foreach ($catalogPages as $page) {
            $configuration = $page['resolved_page_plan']['configuration'];
            self::assertSame(8, $configuration['layout']['container']['max']);
            self::assertFalse($configuration['search']['enabled']);
            self::assertFalse($configuration['search']['indexed']);
            self::assertFalse($configuration['reading']['breadcrumbs']);
            self::assertFalse($configuration['reading']['toc']);
            self::assertSame(2, $configuration['reading']['toc_depth']);
            self::assertFalse($configuration['reading']['previous_next']);
            self::assertTrue($configuration['navigation']['hidden']);
            self::assertSame(321, $configuration['navigation']['order']);

            $html = (string) file_get_contents($build . '/' . $page['output']);
            self::assertStringContainsString('class="bg-surface max-container-8"', $html);
            self::assertStringNotContainsString('data-docara-breadcrumbs', $html);
            self::assertStringNotContainsString('data-docara-previous-next', $html);
            self::assertStringNotContainsString('data-docara-search-trigger', $html);
        }

        $search = $this->json($build . '/_docara/search-index.json');
        foreach ($search['documents'] as $document) {
            self::assertStringNotContainsString('/components/', (string) $document['url']);
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
        $this->filesystem->ensureDirectoryExists($this->tmpPath('content/components'));
        file_put_contents($this->tmpPath('content/components/index.md'), "# Shadow\n");

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('COMPONENT_CATALOG_ROUTE_COLLISION');

        $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
    }

    #[Test]
    public function parameter_examples_reject_an_unknown_parameter(): void
    {
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('COMPONENT_CATALOG_PARAMETER_EXAMPLE_UNKNOWN');

        $this->exampleSourceGroups(
            "<!-- Unknown binding -->\n<!-- docara-parameter:unknown -->\n:badge[Новое]\n",
        );
    }

    #[Test]
    public function parameter_examples_allow_multiple_groups_for_one_parameter(): void
    {
        $groups = $this->exampleSourceGroups(
            "<!-- First binding -->\n<!-- docara-parameter:type -->\n:badge[Первый]\n"
                . "<!-- Second binding -->\n<!-- docara-parameter:type -->\n:badge[Второй]\n",
        );

        self::assertCount(2, $groups);
        self::assertSame(['type', 'type'], array_column($groups, 'parameter'));
    }

    private function buildPortableSite(string $baseUrl = '/', string $locale = 'ru'): string
    {
        $this->copyPortableFixture($this->tmp);
        if ($locale !== 'ru') {
            unlink($this->tmpPath('content/components/alert.md'));
        }
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

    /** @return list<array{label:string,source:string,parameter:?string}> */
    private function exampleSourceGroups(string $source): array
    {
        $projector = new PortableComponentCatalogProjector(new PortableMarkdownRenderer);
        $method = new \ReflectionMethod($projector, 'exampleSourceGroups');

        /** @var list<array{label:string,source:string,parameter:?string}> $groups */
        $groups = $method->invoke(
            $projector,
            [
                'id' => 'docara.badge',
                'authoring' => [
                    'parameters' => [
                        ['name' => 'type'],
                    ],
                ],
            ],
            $source,
            ['parameter_examples' => 'Example'],
        );

        return $groups;
    }

    private function builder(): PortableSiteBuilder
    {
        return new PortableSiteBuilder(
            new Filesystem,
            new PortableMarkdownRenderer,
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

    /** @param array<string, mixed> $entry */
    private static function publicSlug(array $entry): string
    {
        if (($entry['id'] ?? null) === 'docara.code') {
            return 'code-from-file';
        }

        $parts = explode('.', (string) $entry['id'], 2);

        return str_replace('_', '-', $parts[1] ?? $parts[0]);
    }
}
