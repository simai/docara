<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Declarative\Composition\PageCompositionContext;
use Simai\Docara\Declarative\Composition\RegionCompositionResolver;
use Simai\Docara\Declarative\DeclarativePageCompiler;
use Simai\Docara\Declarative\Document\DocumentParser;
use Simai\Docara\Declarative\Rendering\DeclarativePageRenderer;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final class DeclarativeRegionCompositionTest extends TestCase
{
    public function test_optional_regions_can_be_disabled_and_disappear_from_markup(): void
    {
        $layout = RegionCompositionResolver::defaults();
        $layout['regions']['sidebar']['enabled'] = false;
        $layout['regions']['outline']['enabled'] = false;

        $plan = DeclarativePageCompiler::bundled($this->frameworkLock())->compile(
            (new DocumentParser)->parse("# Regions\n\n## Content\n\nText.", 'content/regions.md'),
            'regions',
            'Regions',
            3,
            $this->context(),
            $layout,
            [
                '/layout/regions/sidebar/enabled' => 'content/regions.page.json',
                '/layout/regions/outline/enabled' => 'content/regions.page.json',
            ],
        );

        self::assertTrue($plan->layout->regions['header']->enabled);
        self::assertFalse($plan->layout->regions['sidebar']->enabled);
        self::assertTrue($plan->layout->regions['main']->enabled);
        self::assertFalse($plan->layout->regions['outline']->enabled);
        self::assertFalse($plan->layout->regions['footer']->enabled);
        self::assertSame([], $plan->regions['sidebar']);
        self::assertSame([], $plan->regions['outline']);
        self::assertSame(
            'content/regions.page.json',
            $plan->layout->provenance['configuration']['/layout/regions/sidebar/enabled'],
        );

        $html = (new DeclarativePageRenderer(new PortableMarkdownRenderer))->render($plan)->html;
        self::assertStringContainsString('data-docara-region="header"', $html);
        self::assertStringContainsString('data-docara-region="main"', $html);
        self::assertStringNotContainsString('data-docara-region="sidebar"', $html);
        self::assertStringNotContainsString('data-docara-region="outline"', $html);
        self::assertStringNotContainsString('data-docara-region="footer"', $html);
    }

    public function test_required_main_region_cannot_be_disabled(): void
    {
        $layout = RegionCompositionResolver::defaults();
        $layout['regions']['main']['enabled'] = false;

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('DECLARATIVE_REQUIRED_REGION_DISABLED');

        DeclarativePageCompiler::bundled($this->frameworkLock())->compile(
            (new DocumentParser)->parse('# Required', 'content/required.md'),
            'required',
            'Required',
            3,
            $this->context(),
            $layout,
        );
    }

    public function test_smart_data_binding_is_fail_closed_by_region(): void
    {
        $layout = RegionCompositionResolver::defaults();
        $layout['regions']['header']['sections'][0]['section'] = 'docara.outline';

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('DECLARATIVE_REGION_SECTION_INVALID');

        DeclarativePageCompiler::bundled($this->frameworkLock())->compile(
            (new DocumentParser)->parse('# Binding', 'content/binding.md'),
            'binding',
            'Binding',
            3,
            $this->context(),
            $layout,
        );
    }

    private function context(): PageCompositionContext
    {
        return PageCompositionContext::fromBuilder(
            ['title' => 'Docara', 'label' => 'Documentation'],
            '/',
            [[
                'key' => 'regions',
                'title' => 'Regions',
                'url' => '/regions/',
                'active' => true,
                'active_ancestor' => false,
                'current_section' => false,
                'open' => true,
                'children' => [],
            ]],
            [['id' => 'content', 'level' => 2, 'text' => 'Content']],
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
