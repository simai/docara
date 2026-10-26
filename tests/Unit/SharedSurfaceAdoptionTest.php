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
        // 3.3.0 changed only the frame's padding inside the container from
        // p-N to p-top-N p-bottom-N, so the band's text keeps the page's edge.
        $cases = [
            'hero' => [
                ":::hero\n# Hero\n\nDescription.\n\n![Meaningful image](/assets/hero-backdrop.webp)\n:::\n",
                'b261cefc90812a17e0f3b24dc014f52ab4e0fdca736873d728fa3295ad4ef943',
            ],
            'showcase' => [
                ":::showcase\n## Проверяемый результат\n\nСобранная документация видна до публикации.\n\n[Открыть пример](/landing/)\n\n![Интерфейс Docara](/assets/screen.png)\n:::\n",
                '0b0d0b1ab3c259cc6bf1deabf497fc03438888e17a9ff066416e5e3fa750e744',
            ],
            'promo' => [
                ":::promo\n## Соберите первый сайт\n\nСоздайте проект и получите статический результат.\n\n[Начать](/start/)\n\n![](/assets/promo.png)\n:::\n",
                'b117dee1817d9a43d50f28e04659df6b4c4553ac401401a66d8ddfda988fcaba',
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

        self::assertContains('docara.hero', $typed);
        self::assertContains('docara.features', $typed);
        self::assertNotContains('docara.showcase', $typed);
        self::assertContains('docara.promo', $typed);
        self::assertSame(
            'abd58cb4f523f4d673450d235c665905cd39dea909844a9dcd9083cd91af184d',
            hash('sha256', $result->contentHtml),
        );
        foreach (['hero' => 1, 'features' => 3, 'showcase' => 0, 'promo' => 1] as $block => $count) {
            self::assertSame($count, substr_count($result->contentHtml, 'data-docara-block="' . $block . '"'));
        }
        self::assertSame([], $result->documentArtifact->assets);
        self::assertSame([], $result->componentArtifacts);
    }
}
