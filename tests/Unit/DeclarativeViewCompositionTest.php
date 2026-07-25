<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Declarative\Composition\PageCompositionContext;
use Simai\Docara\Declarative\Composition\RegionCompositionResolver;
use Simai\Docara\Declarative\DeclarativePageCompiler;
use Simai\Docara\Declarative\Document\DocumentParser;
use Simai\Docara\Declarative\Rendering\FrameworkUtilityRegistry;
use Simai\Docara\Declarative\Rendering\ViewTreeInspector;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final class DeclarativeViewCompositionTest extends TestCase
{
    public function test_resolved_plan_expands_calls_slots_views_smart_and_diagnostics(): void
    {
        $plan = DeclarativePageCompiler::bundled($this->frameworkLock())->compile(
            (new DocumentParser)->parse("# Composition\n\nText.", 'content/composition.md'),
            'composition',
            'Composition',
            3,
            $this->context(),
        );
        $resolved = $plan->toArray();

        self::assertSame('docara.resolved_render_plan.v2', $resolved['schema']);
        self::assertSame('layout.docara.docs', $resolved['layout']['view']);
        self::assertSame('layout.docara.docs', $resolved['layout']['view_tree']['key']);
        self::assertSame('site-header', $resolved['regions']['header'][0]['id']);
        self::assertSame('docara.header', $resolved['regions']['header'][0]['section']);
        self::assertSame(['content'], $resolved['regions']['header'][0]['slots']);
        self::assertSame('content', $resolved['regions']['header'][0]['blocks'][0]['slot']);
        self::assertSame('site-header.branding', $resolved['regions']['header'][0]['blocks'][0]['id']);
        self::assertSame('docara.brand', $resolved['regions']['header'][0]['blocks'][0]['smart']['smart']);
        self::assertSame('SAFE_VIEW_TREE_VALIDATED', $resolved['diagnostics'][1]['code']);
        self::assertSame(
            'sf-v5.3.2-9d1cc46d-84c363da',
            $resolved['provenance']['view_runtime']['compatibility_id'],
        );
        self::assertSame($plan->canonicalHash(), DeclarativePageCompiler::bundled($this->frameworkLock())->compile(
            (new DocumentParser)->parse("# Composition\n\nText.", 'content/composition.md'),
            'composition',
            'Composition',
            3,
            $this->context(),
        )->canonicalHash());
    }

    public function test_view_tree_fails_closed_for_unknown_utility_tag_and_duplicate_slot(): void
    {
        foreach ([
            [
                'kind' => 'element',
                'tag' => 'div',
                'utilities' => ['larena-invented-class'],
            ],
            [
                'kind' => 'element',
                'tag' => 'script',
            ],
            [
                'kind' => 'element',
                'tag' => 'section',
                'children' => [
                    ['kind' => 'slot', 'slot' => 'content'],
                    ['kind' => 'slot', 'slot' => 'content'],
                ],
            ],
            [
                'kind' => 'element',
                'tag' => 'div',
                'attributes' => ['onclick' => 'callback()'],
            ],
        ] as $tree) {
            try {
                (new ViewTreeInspector)->inspect($tree);
                self::fail('Unsafe View Tree unexpectedly passed.');
            } catch (PortableConfigurationException $exception) {
                self::assertContains($exception->errorCode, [
                    'DECLARATIVE_VIEW_UTILITY_NOT_ALLOWED',
                    'DECLARATIVE_VIEW_TAG_NOT_ALLOWED',
                    'DECLARATIVE_VIEW_TARGET_DUPLICATED',
                    'DECLARATIVE_VIEW_ATTRIBUTE_NOT_ALLOWED',
                ]);
            }
        }
    }

    public function test_branding_mode_selects_only_registered_smart_views(): void
    {
        foreach ([
            'full' => 'default',
            'compact' => 'compact',
            'logo' => 'logo',
            'text' => 'text',
        ] as $mode => $expectedView) {
            $context = PageCompositionContext::fromBuilder(
                [
                    'title' => 'Docara',
                    'mode' => $mode,
                    'size' => 'medium',
                    'logo' => $mode === 'text' ? null : '/brand.svg',
                ],
                '/',
                [],
                [],
            );
            $plan = DeclarativePageCompiler::bundled($this->frameworkLock())->compile(
                (new DocumentParser)->parse("# Brand\n", 'content/brand.md'),
                'brand',
                'Brand',
                3,
                $context,
            );

            self::assertSame(
                $expectedView,
                $plan->toArray()['regions']['header'][0]['blocks'][0]['smart']['view'],
                "Unexpected Smart view for branding mode [$mode].",
            );
        }
    }

    public function test_header_navigation_is_projected_into_the_registered_header_view(): void
    {
        $context = PageCompositionContext::fromBuilder(
            ['title' => 'Docara'],
            '/ru/',
            [],
            [],
            ['navigation.primary' => 'Главное'],
            [
                'enabled' => true,
                'items' => [
                    ['id' => 'home', 'label' => 'Главная', 'href' => '/ru/'],
                    ['id' => 'start', 'label' => 'Быстрый старт', 'href' => '/ru/start/'],
                    ['id' => 'github', 'label' => 'GitHub', 'href' => 'https://github.com/simai/docara'],
                ],
            ],
            '/ru/',
        );

        $resolved = DeclarativePageCompiler::bundled($this->frameworkLock())->compile(
            (new DocumentParser)->parse("# Главная\n", 'content/ru/index.md'),
            'ru',
            'Главная',
            3,
            $context,
        )->toArray();

        $headerBlocks = $resolved['regions']['header'][0]['blocks'];
        self::assertCount(2, $headerBlocks);
        self::assertSame('docara.navigation', $headerBlocks[1]['smart']['smart']);
        self::assertSame('header', $headerBlocks[1]['smart']['view']);
        self::assertSame('Главное', $headerBlocks[1]['smart']['props']['label']);
        self::assertCount(3, $headerBlocks[1]['smart']['props']['items']);
        self::assertTrue($headerBlocks[1]['smart']['props']['items'][0]['active']);
        self::assertFalse($headerBlocks[1]['smart']['props']['items'][1]['active']);
    }

    public function test_disabled_header_navigation_projects_no_empty_navigation(): void
    {
        $context = PageCompositionContext::fromBuilder(
            ['title' => 'Docara'],
            '/',
            [],
            [],
            [],
            ['enabled' => false],
            '/',
        );

        $resolved = DeclarativePageCompiler::bundled($this->frameworkLock())->compile(
            (new DocumentParser)->parse("# Home\n", 'content/index.md'),
            'home',
            'Home',
            3,
            $context,
        )->toArray();

        self::assertCount(2, $resolved['regions']['header'][0]['blocks']);
        self::assertSame('docara.brand', $resolved['regions']['header'][0]['blocks'][0]['smart']['smart']);
        self::assertSame([], $resolved['regions']['header'][0]['blocks'][1]['smart']['props']['items']);
    }

    public function test_header_navigation_rejects_duplicate_ids(): void
    {
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('DECLARATIVE_HEADER_NAVIGATION_INVALID');

        PageCompositionContext::fromBuilder(
            ['title' => 'Docara'],
            '/',
            [],
            [],
            [],
            [
                'enabled' => true,
                'items' => [
                    ['id' => 'home', 'label' => 'Home', 'href' => '/'],
                    ['id' => 'home', 'label' => 'Start', 'href' => '/start/'],
                ],
            ],
            '/',
        );
    }

    public function test_logo_only_brand_requires_an_image(): void
    {
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('DECLARATIVE_SHELL_BRANDING_INVALID');

        PageCompositionContext::fromBuilder(
            ['title' => 'Docara', 'mode' => 'logo'],
            '/',
            [],
            [],
        );
    }

    public function test_registered_definition_schemas_reject_executable_and_template_surfaces(): void
    {
        $schemas = new SchemaRepository;
        foreach ([
            [[
                'schema' => 'docara.layout.v1',
                'key' => 'unsafe',
                'view' => 'layout.docara.docs',
                'regions' => [],
                'assets' => [],
                'template' => '../../unsafe.php',
            ], 'declarative-layout.schema.json'],
            [[
                'schema' => 'docara.section.v1',
                'key' => 'unsafe',
                'type' => 'content',
                'view' => 'section.docara.article',
                'allowed_regions' => ['main'],
                'slots' => ['content'],
                'allowed_blocks' => ['content.markdown'],
                'blocks' => [],
                'blade' => '@php system("id"); @endphp',
            ], 'declarative-section.schema.json'],
            [[
                'schema' => 'docara.block.v1',
                'key' => 'unsafe',
                'kind' => 'content',
                'renderer' => 'block.markdown',
                'allowed_smart' => [],
                'html' => '<script>alert(1)</script>',
            ], 'declarative-block.schema.json'],
        ] as [$definition, $schema]) {
            try {
                $schemas->assertValid($definition, $schema);
                self::fail("Executable surface unexpectedly passed [$schema].");
            } catch (PortableConfigurationException $exception) {
                self::assertSame('SCHEMA_VALIDATION_FAILED', $exception->errorCode);
            }
        }
    }

    public function test_framework_utility_projection_is_exactly_bound_to_runtime_registry(): void
    {
        $provenance = (new FrameworkUtilityRegistry)->provenance();

        self::assertSame('sf-v5.3.2-9d1cc46d-84c363da', $provenance['compatibility_id']);
        self::assertSame(
            '2c5963276d31af09770fe41cad04826c04b634f7b2d798d9b0e32864517346b7',
            $provenance['registry_sha256'],
        );
    }

    public function test_section_instance_ids_are_unique_across_the_page(): void
    {
        $layout = RegionCompositionResolver::defaults();
        $layout['regions']['outline']['sections'][0]['id'] = 'docs-navigation';

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('DECLARATIVE_SECTION_INSTANCE_ID_DUPLICATED');

        DeclarativePageCompiler::bundled($this->frameworkLock())->compile(
            (new DocumentParser)->parse('# Duplicate', 'content/duplicate.md'),
            'duplicate',
            'Duplicate',
            3,
            $this->context(),
            $layout,
        );
    }

    public function test_compiler_rejects_executable_author_layout_even_without_loader_schema(): void
    {
        $layout = RegionCompositionResolver::defaults();
        $layout['template_path'] = '../../unsafe.blade.php';

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('DECLARATIVE_AUTHOR_EXECUTABLE_SURFACE_FORBIDDEN');

        DeclarativePageCompiler::bundled($this->frameworkLock())->compile(
            (new DocumentParser)->parse('# Unsafe', 'content/unsafe.md'),
            'unsafe',
            'Unsafe',
            3,
            $this->context(),
            $layout,
        );
    }

    private function context(): PageCompositionContext
    {
        return PageCompositionContext::fromBuilder(
            ['title' => 'Docara'],
            '/',
            [[
                'key' => 'composition',
                'title' => 'Composition',
                'url' => '/composition/',
                'active' => true,
                'active_ancestor' => false,
                'current_section' => false,
                'open' => true,
                'children' => [],
            ]],
            [],
        );
    }

    /** @return array<string, mixed> */
    private function frameworkLock(): array
    {
        return json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
}
