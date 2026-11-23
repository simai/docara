<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Framework\FrameworkComponentRuntime;
use Simai\Docara\Portable\PortableConfigurationLoader;
use Simai\Docara\PortableSite\PageBuilder;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final class SharedSurfaceAdoptionTest extends TestCase
{
    #[Test]
    public function semantic_blocks_keep_the_frozen_default_bytes_on_one_surface_presentation(): void
    {
        $renderer = new PortableMarkdownRenderer;
        // 3.14.1 writes the renamed bg-surface-page for bg-surface-0.
        // 3.3.0 changed only the frame's padding inside the container from
        // p-N to p-top-N p-bottom-N, so the band's text keeps the page's edge.
        $cases = [
            'hero' => [
                ":::hero\n# Hero\n\nDescription.\n\n![Meaningful image](/assets/hero-backdrop.webp)\n:::\n",
                'b261cefc90812a17e0f3b24dc014f52ab4e0fdca736873d728fa3295ad4ef943',
            ],
            'showcase' => [
                ":::showcase\n## Проверяемый результат\n\nСобранная документация видна до публикации.\n\n[Открыть пример](/landing/)\n\n![Интерфейс Docara](/assets/screen.png)\n:::\n",
                '53ab79b33cadde54cabab639768a54343fb27235c1da22f516cce3586238f285',
            ],
            'promo' => [
                ":::promo\n## Соберите первый сайт\n\nСоздайте проект и получите статический результат.\n\n[Начать](/start/)\n\n![](/assets/promo.png)\n:::\n",
                '46f2d64416ed5c7725eafbb13e6c0e2f9f9afe9ee7ea2a4e1e2f6800396ebeb9',
            ],
        ];

        foreach ($cases as $block => [$markdown, $expected]) {
            $html = $renderer->render($markdown);
            self::assertSame($expected, hash('sha256', $html), $block);
            self::assertSame(1, substr_count($html, 'data-docara-block="' . $block . '"'));
        }
    }

    #[Test]
    public function portable_renderer_contains_no_duplicate_semantic_outer_section_builder(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 2) . '/src/PortableSite/PortableMarkdownRenderer.php',
        );

        self::assertSame(2, substr_count($source, '$this->surfaces->renderSemanticFrame('));
        self::assertStringNotContainsString("return '<section data-docara-block=\"' . \$block", $source);
        self::assertStringNotContainsString("return '<section data-docara-block=\"hero\"", $source);
    }

    #[Test]
    public function production_page_builder_keeps_the_landing_typed_ir_and_exact_semantic_output(): void
    {
        $root = dirname(__DIR__, 2) . '/docs/site';
        $plan = (new PortableConfigurationLoader($root))->resolve('content/ru/index.md');
        $result = (new PageBuilder(new PortableMarkdownRenderer))->build(
            $plan,
            $root,
            FrameworkComponentRuntime::fromLock($plan->frameworkLock),
            3,
        );
        $typed = array_values(array_map(
            static fn (array $node): string => (string) ($node['data']['component'] ?? ''),
            array_filter(
                $result->document->toArray()['nodes'],
                static fn (array $node): bool => ($node['type'] ?? null) === 'typed_directive',
            ),
        ));

        // The round-4 home page: a hero, card grids (six link cards among
        // them), a tabs showcase of examples, a run of details and a closing
        // full-width surface; no retired landing blocks.
        self::assertContains('docara.hero', $typed);
        foreach (['docara.features', 'docara.showcase', 'docara.promo', 'docara.columns', 'docara.cta'] as $retired) {
            self::assertNotContains($retired, $typed);
        }
        self::assertSame(
            '8a046cb399806fee2f794f2b7b9fbaffa264d7474f1ffe780355aa9385e5a173',
            hash('sha256', $result->contentHtml),
        );
        foreach (['hero' => 1, 'grid' => 5, 'tabs' => 2, 'details' => 6, 'surface' => 1, 'features' => 0, 'columns' => 0, 'cta' => 0] as $block => $count) {
            self::assertSame($count, substr_count($result->contentHtml, 'data-docara-block="' . $block . '"'), $block);
        }
        self::assertSame(5, substr_count($result->contentHtml, 'data-docara-space="7"'));
        self::assertSame(1, substr_count($result->contentHtml, 'data-docara-space="6"'));
        self::assertSame(6, substr_count($result->contentHtml, 'data-docara-card-link'));
        self::assertSame([], $result->documentArtifact->assets);
        self::assertSame([], $result->componentArtifacts);
    }
}
