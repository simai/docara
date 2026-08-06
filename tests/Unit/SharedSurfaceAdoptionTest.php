<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final class SharedSurfaceAdoptionTest extends TestCase
{
    #[Test]
    public function semantic_blocks_keep_the_frozen_default_bytes_on_one_surface_presentation(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $cases = [
            'hero' => [
                ":::hero\n# Hero\n\nDescription.\n\n![Meaningful image](/assets/docara-screen.png)\n:::\n",
                '886d1e4b0b2066004431427c1f69e2b0d34b2ce71b4960db16ebf1e667cb9684',
            ],
            'showcase' => [
                ":::showcase\n## Проверяемый результат\n\nСобранная документация видна до публикации.\n\n[Открыть пример](/landing/)\n\n![Интерфейс Docara](/assets/screen.png)\n:::\n",
                '402551dc2dd75537370a195b67099d7e12a9e9394cee0ab2c2460bff6d0a6405',
            ],
            'promo' => [
                ":::promo\n## Соберите первый сайт\n\nСоздайте проект и получите статический результат.\n\n[Начать](/start/)\n\n![](/assets/promo.png)\n:::\n",
                '50a55ea25e32533c84a0d2e3f1fd21fdf5082cccfe702243eacdfa2bc8b73648',
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
}
