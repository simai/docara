<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Application\DesignAtlasService;
use Simai\Docara\PortableSite\PortableAtlasIndexHydrator;
use Simai\Docara\PortableSite\PortableMarkdownProfile;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSchemaReferenceProjector;

final class DocumentationSiteStructureTest extends TestCase
{
    #[Test]
    public function components_root_has_six_catalogue_categories_and_only_real_component_pages(): void
    {
        $root = dirname(__DIR__, 2) . '/docs/site/content/ru/components';
        $retired = [
            'native-markdown', 'inline-docara', 'block-docara', 'containers',
            'framework', 'project', 'syntax',
        ];
        $index = (string) file_get_contents(dirname($root) . '/components.md');
        foreach ($retired as $entry) {
            self::assertFileDoesNotExist($root . '/' . $entry . '.md');
            self::assertStringNotContainsString('/ru/components/' . $entry . '/', $index);
        }
        $pages = glob($root . '/*.md') ?: [];
        self::assertSame(29, count($pages));
        self::assertSame(29, preg_match_all('#/ru/components/[a-z0-9-]+/#', $index));
        foreach (['Текст и Markdown', 'Содержательные блоки', 'Код и данные', 'Макет', 'Медиа', 'Inline и действия', 'SIMAI Framework'] as $category) {
            self::assertStringContainsString('## ' . $category, $index);
        }
        // Every component page is listed once, with a one-line description;
        // the Markdown constructions point at the Markdown guide instead.
        self::assertSame(6, substr_count($index, "| Компонент | Для чего |\n| --- | --- |"));
        self::assertSame(1, substr_count($index, "| Конструкция | Для чего |\n| --- | --- |"));
        foreach ($pages as $page) {
            $slug = basename($page, '.md');
            self::assertMatchesRegularExpression(
                '#^\| \[[^]]+\]\(/ru/components/' . preg_quote($slug, '#') . '/\) \| \S[^|]* \|$#mu',
                $index,
                $slug,
            );
        }
        self::assertStringContainsString('/ru/start/component-model/', $index);
    }

    #[Test]
    public function native_markdown_guide_covers_every_enabled_profile_capability(): void
    {
        $content = dirname(__DIR__, 2) . '/docs/site/content/ru';
        $guide = (string) file_get_contents($content . '/components.md');
        $markdown = (string) file_get_contents($content . '/authoring/markdown.md');
        // Each native capability is listed in the catalogue and described in
        // its own part of the Markdown guide; fenced code also has a page.
        $refs = [
            'native.code' => ['/ru/components/code/', '## Код'],
            'native.footnotes_and_sources' => ['/ru/authoring/markdown/#сноски-и-источники', '## Сноски и источники'],
            'native.headings_and_text' => ['/ru/authoring/markdown/#заголовки-и-текст', '## Заголовки и текст'],
            'native.links_and_images' => ['/ru/authoring/markdown/#ссылки-и-изображения', '## Ссылки и изображения'],
            'native.lists_and_quotes' => ['/ru/authoring/markdown/#списки-и-цитаты', '## Списки и цитаты'],
            'native.table' => ['/ru/authoring/markdown/#таблицы', '## Таблицы'],
        ];
        self::assertSame(array_keys($refs), array_column(PortableMarkdownProfile::bundled()->entries(), 'id'));
        foreach ($refs as [$route, $heading]) {
            self::assertStringContainsString($route, $guide);
            self::assertMatchesRegularExpression('/^' . preg_quote($heading, '/') . '$/mu', $markdown);
        }
        self::assertStringContainsString(
            'MARKDOWN_RAW_HTML_FORBIDDEN',
            (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/content/ru/start/component-model.md'),
        );
    }

    #[Test]
    public function consolidated_component_model_exposes_container_and_framework_boundaries(): void
    {
        $model = (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/content/ru/start/component-model.md');
        foreach (['вложенные блоки', 'слоты', 'min_children', 'max_children', 'порядок'] as $field) {
            self::assertStringContainsString($field, $model);
        }
        self::assertStringContainsString('ui.dropdown', $model);
        self::assertStringContainsString('ui.list-item', $model);
        self::assertStringContainsString('точной закреплённой версии', $model);
        self::assertStringContainsString('хеши входных файлов', $model);
        self::assertStringContainsString('MARKDOWN_RAW_HTML_FORBIDDEN', $model);
    }

    #[Test]
    public function real_docs_project_renders_truthful_nonempty_component_groups(): void
    {
        $root = dirname(__DIR__, 2) . '/docs/site';
        $atlas = (new DesignAtlasService)->atlas($root)->toArray()['data'];
        self::assertSame(86, $atlas['count']);
        $groups = [
            'native' => [':::atlas_index {origin=native authoring=markdown support=supported}', ['native.code', 'native.table']],
            'inline' => [':::atlas_index {kind=block origin=docara authoring=inline support=supported}', ['docara.badge', 'docara.button', 'docara.icon', 'docara.kbd']],
            'block' => [':::atlas_index {kind=block origin=docara authoring=block support=supported}', ['docara.alert', 'docara.card']],
            'containers' => [':::atlas_index {kind=block origin=docara authoring=container support=supported}', ['docara.grid']],
            'framework' => [':::atlas_index {kind=smart namespace=ui origin=framework support=supported}', ['ui.input', 'ui.dropdown', 'ui.checkbox', 'ui.list-item']],
            'project' => [':::atlas_index {origin=project}', ['project.install-builder', 'project.footer-links']],
        ];
        foreach ($groups as $name => [$directive, $ids]) {
            $placeholder = (new PortableMarkdownRenderer)->render($directive . "\n:::\n");
            $html = (new PortableAtlasIndexHydrator)->hydrate([['content_html' => $placeholder]], $atlas)[0]['content_html'];
            self::assertStringNotContainsString('нет элементов', $html, $name);
            foreach ($ids as $id) {
                self::assertStringContainsString('<code>' . $id . '</code>', $html, $name . ':' . $id);
            }
            if ($name === 'containers') {
                self::assertStringNotContainsString('<code>docara.docs</code>', $html);
                self::assertStringNotContainsString('<code>docara.article</code>', $html);
                foreach (['allowed_children=[docara.card]', 'min_children=1', 'max_children=12', 'order=declared', 'max_depth=2', 'depth_semantics=relative_subtree_root_level_1'] as $rule) {
                    self::assertStringContainsString($rule, $html);
                }
            }
        }
    }

    #[Test]
    public function composition_page_maps_every_shell_surface_and_real_insertion_file(): void
    {
        $root = dirname(__DIR__, 2) . '/docs/site/content/ru';
        self::assertDirectoryDoesNotExist($root . '/design');
        // The former design overview and its four pages are one page now.
        $composition = (string) file_get_contents($root . '/development/composition.md');
        foreach ([
            'resources/layouts/docara.docs.json',
            'resources/views/layout.docara.docs.json',
            'resources/sections/docara.article.json',
            'resources/blocks/content.document.json',
            'SmartComponentGateway',
            'LayoutComposer',
            'PageBuilder',
        ] as $marker) {
            self::assertStringContainsString($marker, $composition);
        }
        foreach (['Brand', 'Header navigation', 'Sidebar tree', 'Mobile/compact navigation', 'Outline / TOC', 'Search', 'Breadcrumbs', 'Previous/next pager', 'Reader preferences', 'Footer', 'Outer document'] as $surface) {
            self::assertStringContainsString($surface, $composition);
        }
        self::assertSame(3, substr_count($composition, ':::internal_preview'));
        self::assertStringContainsString('PREVIEW_BUILD_PURPOSE_FORBIDDEN', $composition);
    }

    #[Test]
    public function settings_root_implements_the_complete_route_map_and_schema_derived_references(): void
    {
        $content = dirname(__DIR__, 2) . '/docs/site/content/ru';
        $routes = [
            'levels-and-inheritance', 'site-address', 'branding-and-theme', 'layout-and-regions',
            'search', 'reading', 'reader-preferences',
        ];
        $root = (string) file_get_contents($content . '/settings.md');
        foreach ($routes as $route) {
            self::assertFileExists($content . '/settings/' . $route . '.md');
            self::assertStringContainsString('/ru/settings/' . $route . '/', $root);
        }
        self::assertCount(count($routes), glob($content . '/settings/*.md') ?: []);
        // The schema-derived tables live in the reference, one place per file.
        self::assertStringContainsString(
            ':::schema_reference {schema=presentation scope=shared}',
            (string) file_get_contents($content . '/reference/section-and-page.md'),
        );
        // A long reference is shown in titled parts; together they still list
        // every top-level field once.
        foreach ([
            'docara-json' => [['site', 'site']],
            'section-and-page' => [['section', 'section'], ['page', 'page']],
            'framework-lock' => [['framework-lock', 'lock']],
        ] as $route => $tables) {
            $markdown = (string) file_get_contents($content . '/reference/' . $route . '.md');
            foreach ($tables as [$schema, $scope]) {
                preg_match_all('/^:::schema_reference \{schema=' . preg_quote($schema, '/') . ' scope=' . $scope . ' fields=([a-z0-9_,]+)\}$/m', $markdown, $matches);
                $listed = array_merge(...array_map(static fn (string $fields): array => explode(',', $fields), $matches[1] ?: ['']));
                $known = PortableSchemaReferenceProjector::topLevelFields((new PortableSchemaReferenceProjector)->project($schema, $scope));
                sort($known);
                $sortedListed = $listed;
                sort($sortedListed);
                self::assertSame($known, $sortedListed, "reference/$route.md must list every top-level $schema field exactly once.");
                self::assertGreaterThanOrEqual(4, count($matches[1]), "reference/$route.md needs titled groups for $schema.");
            }
            self::assertGreaterThanOrEqual(4, preg_match_all('/^#{2,3} /m', $markdown), "reference/$route.md needs titled groups.");
        }
    }

    #[Test]
    public function every_pre_restructure_output_still_resolves_to_a_page_or_a_redirect(): void
    {
        $site = dirname(__DIR__, 2) . '/docs/site';
        $configuration = json_decode((string) file_get_contents($site . '/docara.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('redirect', $configuration['locale_routing']['root']);
        self::assertTrue($configuration['locale_routing']['legacy_unprefixed_redirects']);

        // What the build publishes: the root locale redirect and the built-in
        // not-found page, every Markdown page under ru/, the legacy unprefixed
        // redirect of every page, and every redirects.json source.
        $outputs = ['index.html' => 'root', '404.html' => 'not_found'];
        $pages = [];
        $content = $site . '/content/ru';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($content, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (! $file instanceof \SplFileInfo || $file->getExtension() !== 'md') {
                continue;
            }
            $route = substr($file->getPathname(), strlen($content) + 1, -3);
            $sidecar = substr($file->getPathname(), 0, -3) . '.page.json';
            if (is_file($sidecar)) {
                $page = json_decode((string) file_get_contents($sidecar), true, 512, JSON_THROW_ON_ERROR);
                self::assertArrayNotHasKey('slug', $page, "$route: a slug would move the page away from its file route.");
            }
            $route = $route === 'index' ? '' : preg_replace('#/index$#', '', $route);
            $pages['ru' . ($route === '' ? '' : '/' . $route)] = true;
            $outputs['ru/' . ($route === '' ? '' : $route . '/') . 'index.html'] = 'page';
            if ($route !== '') {
                $outputs[$route . '/index.html'] = 'legacy';
            }
        }
        $redirects = json_decode((string) file_get_contents($site . '/redirects.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($redirects['redirects'] as $redirect) {
            self::assertArrayHasKey($redirect['to'], $pages, "Redirect [{$redirect['from']}] must target a page.");
            self::assertArrayNotHasKey($redirect['from'] . '/index.html', $outputs, "Redirect [{$redirect['from']}] collides with a page.");
            $outputs[$redirect['from'] . '/index.html'] = 'redirect';
        }

        $previous = array_values(array_filter(
            file(dirname(__DIR__) . '/fixtures/documentation-site/pre-restructure-outputs.txt', FILE_IGNORE_NEW_LINES) ?: [],
            static fn (string $line): bool => $line !== '' && ! str_starts_with($line, '#'),
        ));
        self::assertCount(292, $previous);
        $lost = array_values(array_filter($previous, static fn (string $output): bool => ! isset($outputs[$output])));
        self::assertSame([], $lost, 'A URL published before the restructure no longer resolves; add a redirect to redirects.json.');
    }

    #[Test]
    public function agent_journey_and_extension_demos_preserve_safe_service_and_support_boundaries(): void
    {
        $root = dirname(__DIR__, 2) . '/docs/site/content/ru';
        // The agent journey is a part of the SDK page.
        $journey = (string) file_get_contents($root . '/development/developer-sdk.md');
        foreach (['Найдите возможность', 'Составьте план', 'preview', 'dry-run', 'hash-bound apply', 'DesignAtlasService', 'ScaffoldService', 'SDK_WRITE_PATH_UNSAFE', '--allow-writes'] as $marker) {
            self::assertStringContainsStringIgnoringCase($marker, $journey);
        }
        foreach (['выход за каталог', 'символические и жёсткие ссылки', 'запись в файлы движка, lock, результаты сборки или'] as $negative) {
            self::assertStringContainsString($negative, $journey);
        }
        $demos = (string) file_get_contents($root . '/examples/extensions.md');
        foreach (['ui.input', 'ui.checkbox', 'ui.dropdown', 'ui.list-item(type=text)', 'project', 'network/order/payment/command side effects'] as $marker) {
            self::assertStringContainsString($marker, $demos);
        }
        self::assertStringContainsString('icons, avatars, tags', $demos);
        self::assertFileExists($root . '/project-demos.md');
        self::assertFileExists(dirname(__DIR__, 2) . '/docs/site/smart/project.install-builder/manifest.json');
        self::assertFileExists(dirname(__DIR__, 2) . '/docs/site/design/sections/project.footer.json');
    }
}
