<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\PortableSite\PortableMarkdownProfile;

final class GoalCPublicDocumentationTest extends TestCase
{
    #[Test]
    public function components_root_has_exactly_six_physical_entry_owners(): void
    {
        $root = dirname(__DIR__, 2) . '/docs/site/content/ru/components';
        $entries = [
            'native-markdown', 'inline-docara', 'block-docara',
            'containers', 'framework', 'project',
        ];
        $index = (string) file_get_contents(dirname($root) . '/components.md');
        foreach ($entries as $entry) {
            self::assertFileExists($root . '/' . $entry . '.md');
            self::assertStringContainsString('/ru/components/' . $entry . '/', $index);
        }
        self::assertSame(6, preg_match_all('#/ru/components/(?:native-markdown|inline-docara|block-docara|containers|framework|project)/#', $index));
    }

    #[Test]
    public function native_markdown_guide_covers_every_enabled_profile_capability(): void
    {
        $guide = (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/content/ru/components/native-markdown.md');
        $refs = [
            'native.code' => '/ru/components/code/',
            'native.footnotes_and_sources' => '/ru/components/footnotes-and-sources/',
            'native.headings_and_text' => '/ru/components/headings-and-text/',
            'native.links_and_images' => '/ru/components/links-and-images/',
            'native.lists_and_quotes' => '/ru/components/lists-and-quotes/',
            'native.table' => '/ru/components/table/',
        ];
        self::assertSame(array_keys($refs), array_column(PortableMarkdownProfile::bundled()->entries(), 'id'));
        foreach ($refs as $route) {
            self::assertStringContainsString($route, $guide);
        }
        self::assertStringContainsString('MARKDOWN_RAW_HTML_FORBIDDEN', $guide);
    }

    #[Test]
    public function container_and_framework_guides_expose_registry_contract_and_nonclaims(): void
    {
        $containers = (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/content/ru/components/containers.md');
        foreach (['allowed_children', 'slots', 'min_children', 'max_children', 'order', 'max_depth'] as $field) {
            self::assertStringContainsString('`' . $field . '`', $containers);
        }
        foreach (['MARKDOWN_GRID_CARD_REQUIRED', 'MARKDOWN_COLUMNS_REGION_COUNT_INVALID', 'MARKDOWN_STEPS_ORDERED_LIST_REQUIRED', 'MARKDOWN_BLOCK_UNCLOSED'] as $code) {
            self::assertStringContainsString('`' . $code . '`', $containers);
        }
        $framework = (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/content/ru/components/framework.md');
        self::assertStringContainsString('ui.dropdown', $framework);
        self::assertStringContainsString('ui.list-item', $framework);
        self::assertStringContainsString('icons, avatars, tags', $framework);
        self::assertStringContainsString(':::atlas_index {kind=smart owner=ui support=supported}', $framework);
    }

    #[Test]
    public function design_root_maps_every_shell_surface_and_real_insertion_file(): void
    {
        $base = dirname(__DIR__, 2) . '/docs/site/content/ru/design';
        foreach (['composition', 'interface', 'project-shell', 'previews'] as $route) {
            self::assertFileExists($base . '/' . $route . '.md');
        }
        $composition = (string) file_get_contents($base . '/composition.md');
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
        $interface = (string) file_get_contents($base . '/interface.md');
        foreach (['Brand', 'Header navigation', 'Sidebar tree', 'Mobile/compact navigation', 'Outline / TOC', 'Search', 'Breadcrumbs', 'Previous/next pager', 'Reader preferences', 'Footer', 'Outer document'] as $surface) {
            self::assertStringContainsString($surface, $interface);
        }
        $previews = (string) file_get_contents($base . '/previews.md');
        self::assertSame(3, substr_count($previews, ':::internal_preview'));
        self::assertStringContainsString('PREVIEW_BUILD_PURPOSE_FORBIDDEN', $previews);
    }

    #[Test]
    public function settings_root_implements_the_complete_route_map_and_schema_derived_references(): void
    {
        $base = dirname(__DIR__, 2) . '/docs/site/content/ru/settings';
        $routes = [
            'levels-and-inheritance', 'site', 'section', 'page', 'locales-and-routing',
            'branding-and-theme', 'layout-and-regions', 'navigation', 'search-and-reading',
            'reader-preferences', 'framework-lock-and-providers', 'security',
            'diagnostics-and-provenance',
        ];
        $root = (string) file_get_contents(dirname($base) . '/settings.md');
        foreach ($routes as $route) {
            self::assertFileExists($base . '/' . $route . '.md');
            self::assertStringContainsString('/ru/settings/' . $route . '/', $root);
        }
        self::assertStringContainsString(':::schema_reference {schema=presentation scope=shared}', $root);
        self::assertStringContainsString(':::schema_reference {schema=site scope=site}', (string) file_get_contents($base . '/site.md'));
        self::assertStringContainsString(':::schema_reference {schema=section scope=section}', (string) file_get_contents($base . '/section.md'));
        self::assertStringContainsString(':::schema_reference {schema=page scope=page}', (string) file_get_contents($base . '/page.md'));
        self::assertStringContainsString(':::schema_reference {schema=framework-lock scope=lock}', (string) file_get_contents($base . '/framework-lock-and-providers.md'));
    }

    #[Test]
    public function agent_journey_and_extension_demos_preserve_safe_service_and_support_boundaries(): void
    {
        $root = dirname(__DIR__, 2) . '/docs/site/content/ru';
        $journey = (string) file_get_contents($root . '/development/agent-journey.md');
        foreach (['discover', 'plan', 'preview', 'dry-run', 'Hash-bound apply', 'DesignAtlasService', 'ScaffoldService', 'SDK_WRITE_PATH_UNSAFE', '--allow-writes'] as $marker) {
            self::assertStringContainsStringIgnoringCase($marker, $journey);
        }
        foreach (['traversal', 'symlink/hardlink', 'engine/lock/generated/external root'] as $negative) {
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
