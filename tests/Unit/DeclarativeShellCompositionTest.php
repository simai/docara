<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Declarative\Adapter\LarenaContractAdapter;
use Simai\Docara\Declarative\Composition\PageCompositionContext;
use Simai\Docara\Declarative\DeclarativePageCompiler;
use Simai\Docara\Declarative\Definition\DefinitionRepository;
use Simai\Docara\Declarative\Document\DocumentParser;
use Simai\Docara\Declarative\Rendering\DeclarativePageRenderer;
use Simai\Docara\Declarative\Rendering\SmartRenderer;
use Simai\Docara\Declarative\Semantic\ShellStructuralParityChecker;
use Simai\Docara\Declarative\Smart\CompositeSmartPlanResolver;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final class DeclarativeShellCompositionTest extends TestCase
{
    public function test_it_builds_and_renders_a_four_level_shell_through_composite_smart_plans(): void
    {
        $context = $this->context();
        $plan = DeclarativePageCompiler::bundled($this->frameworkLock())->compile(
            (new DocumentParser)->parse("# Guide\n\n## Install\n\nText.", 'content/guide/install.md'),
            'guide/install',
            'Install',
            3,
            $context,
        );

        self::assertSame(
            ['header' => 1, 'sidebar' => 1, 'main' => 1, 'outline' => 1, 'footer' => 0],
            array_map('count', $plan->regions),
        );
        self::assertSame(
            ['docara.brand', 'docara.navigation', 'docara.toc'],
            array_column($plan->semanticProjection()['smart'], 'smart'),
        );
        $navigation = $plan->regions['sidebar'][0]->blocks[0]->smart;
        self::assertNotNull($navigation);
        self::assertSame('docara.navigation', $navigation->smart);
        self::assertSame('docara.smart.template', $navigation->provenance['renderer']);
        self::assertSame('larena.ui.smart_manifest.v1', $navigation->provenance['manifest_schema']);
        self::assertTrue(
            $navigation->props['items'][0]['children'][0]['children'][0]['children'][0]['active'],
        );

        $parity = (new ShellStructuralParityChecker)->assertEquivalent($context, $plan);
        self::assertTrue($parity->passed);

        $larena = (new LarenaContractAdapter)->adapt($plan);
        self::assertSame('docara.navigation', $larena->payload['regions']['sidebar'][0]['blocks'][0]['smart']['key']);
        self::assertSame($plan->semanticProjection(), $larena->semantics);

        $rendered = (new DeclarativePageRenderer(new PortableMarkdownRenderer))->render($plan);
        $html = $rendered->html;
        self::assertStringContainsString('data-docara-smart="docara.brand"', $html);
        self::assertStringContainsString('data-docara-smart="docara.navigation"', $html);
        self::assertStringContainsString('data-docara-navigation-depth="4"', $html);
        self::assertStringContainsString('aria-current="page"', $html);
        self::assertStringContainsString('data-docara-smart="docara.toc"', $html);
        self::assertStringContainsString('href="#install"', $html);
        self::assertSame('docara.navigation', $rendered->hydration['components'][1]['asset_owner']);
        self::assertSame('docara.navigation', $rendered->hydration['components'][1]['hydration_owner']);
    }

    public function test_shell_structural_parity_fails_when_builder_data_and_plan_differ(): void
    {
        $context = $this->context();
        $plan = DeclarativePageCompiler::bundled($this->frameworkLock())->compile(
            (new DocumentParser)->parse("# Guide\n\nText.", 'content/guide.md'),
            'guide',
            'Guide',
            3,
            $context,
        );
        $different = PageCompositionContext::fromBuilder(
            ['title' => 'Different', 'label' => null, 'logo' => null, 'logo_dark' => null],
            '/',
            $context->navigation,
            $context->outline,
        );

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('DECLARATIVE_SHELL_STRUCTURAL_PARITY_FAILED');

        (new ShellStructuralParityChecker)->assertEquivalent($different, $plan);
    }

    public function test_navigation_deeper_than_four_levels_fails_closed(): void
    {
        $tree = [$this->node('one', [$this->node('two', [
            $this->node('three', [$this->node('four', [$this->node('five')])]),
        ])])];

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('DECLARATIVE_NAVIGATION_DEPTH_EXCEEDED');

        PageCompositionContext::fromBuilder(
            ['title' => 'Docara'],
            '/',
            $tree,
            [],
        );
    }

    public function test_product_manifests_use_the_larena_schema_without_claiming_framework_runtime(): void
    {
        $repository = new DefinitionRepository;
        foreach (['docara.brand', 'docara.navigation', 'docara.toc'] as $key) {
            $manifest = $repository->smartManifest($key);
            self::assertSame('larena.ui.smart_manifest.v1', $manifest['schema']);
            self::assertSame('composite', $manifest['kind']);
            self::assertSame('simai/docara', $manifest['owner_package']);
            self::assertSame('docara.smart.template', $manifest['render']['renderer']);
            self::assertNull($manifest['frontend']['runtime']);
            self::assertNull($manifest['frontend']['tag']);
        }
    }

    public function test_legacy_product_smart_names_resolve_to_one_canonical_implementation(): void
    {
        $repository = new DefinitionRepository;

        $brand = $repository->smartManifest('docara.header');
        self::assertSame('docara.brand', $brand['key']);
        self::assertSame([
            'requested' => 'docara.header',
            'canonical' => 'docara.brand',
            'deprecated' => true,
            'reason' => 'Renamed because header is a layout region; use docara.brand.',
        ], $brand['_resolution']);

        $toc = $repository->smartManifest('docara.outline');
        self::assertSame('docara.toc', $toc['key']);
        self::assertTrue($toc['_resolution']['deprecated']);
    }

    public function test_product_views_render_from_the_canonical_registry(): void
    {
        $plan = (new CompositeSmartPlanResolver)->resolve(
            'docara.header',
            'legacy-brand',
            ['branding' => $this->context()->branding],
            'compact',
        );

        self::assertSame('docara.brand', $plan->smart);
        self::assertSame('compact', $plan->view);
        self::assertSame('smart.docara.brand.compact', $plan->template);
        self::assertTrue($plan->provenance['deprecated_alias']);
        self::assertContains('docara.smart.brand.css', $plan->assets);
        $html = (new SmartRenderer)->render($plan)->html;
        self::assertStringContainsString('data-docara-smart="docara.brand"', $html);
        self::assertStringContainsString('data-docara-view="compact"', $html);
        self::assertStringNotContainsString('docara.header', $html);
    }

    private function context(): PageCompositionContext
    {
        return PageCompositionContext::fromBuilder(
            [
                'title' => 'Docara',
                'label' => 'Documentation',
                'logo' => '/_docara/brand/logo.svg',
                'logo_dark' => '/_docara/brand/logo-dark.svg',
            ],
            '/',
            [$this->node('guide', [
                $this->node('start', [
                    $this->node('setup', [
                        $this->node('install', [], true),
                    ], false, true),
                ], false, true),
            ], false, true)],
            [
                ['id' => 'install', 'level' => 2, 'text' => 'Install'],
                ['id' => 'next', 'level' => 3, 'text' => 'Next'],
            ],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $children
     * @return array<string, mixed>
     */
    private function node(
        string $key,
        array $children = [],
        bool $active = false,
        bool $activeAncestor = false,
    ): array {
        return [
            'key' => $key,
            'title' => ucfirst($key),
            'url' => '/' . $key . '/',
            'active' => $active,
            'active_ancestor' => $activeAncestor,
            'current_section' => $activeAncestor && count($children) === 1 && ($children[0]['active'] ?? false),
            'open' => $active || $activeAncestor,
            'children' => $children,
        ];
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
