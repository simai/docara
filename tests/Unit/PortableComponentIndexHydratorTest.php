<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\PortableSite\PortableComponentIndexHydrator;
use Tests\TestCase;

final class PortableComponentIndexHydratorTest extends TestCase
{
    #[Test]
    public function it_derives_and_hydrates_only_direct_authored_component_pages(): void
    {
        $pages = [
            [
                'url' => '/ru/components/',
                'page_source_kind' => 'authored_markdown',
                'title' => 'Компоненты',
                'description' => 'Индекс',
                'content_html' => '<nav data-docara-component-index></nav>',
            ],
            [
                'url' => '/ru/components/zeta/',
                'page_source_kind' => 'authored_markdown',
                'title' => 'Ячейка',
                'description' => 'Вторая',
                'content_html' => '',
            ],
            [
                'url' => '/ru/components/alpha/',
                'page_source_kind' => 'authored_markdown',
                'title' => 'Альфа',
                'description' => 'Первая',
                'content_html' => '',
            ],
            [
                'url' => '/ru/components/generated/',
                'page_source_kind' => 'generated_projection',
                'title' => 'Generated',
                'description' => 'Excluded',
                'content_html' => '',
            ],
            [
                'url' => '/ru/components/alpha/nested/',
                'page_source_kind' => 'authored_markdown',
                'title' => 'Nested',
                'description' => 'Excluded',
                'content_html' => '',
            ],
        ];
        $hydrator = new PortableComponentIndexHydrator;
        $entries = $hydrator->index($pages, '/ru/components/');

        self::assertSame(['/ru/components/alpha/', '/ru/components/zeta/'], array_column($entries, 'url'));
        $hydrated = $hydrator->hydrate($pages, ['/ru/components/' => $entries]);
        $html = $hydrated[0]['content_html'];
        self::assertStringContainsString('data-docara-component-index-view', $html);
        self::assertStringContainsString('href="/ru/components/alpha/">Альфа</a>', $html);
        self::assertStringContainsString('>Первая</p>', $html);
        self::assertStringNotContainsString('Generated', $html);
        self::assertStringNotContainsString('Nested', $html);
    }
}
