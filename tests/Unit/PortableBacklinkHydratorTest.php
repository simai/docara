<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\PortableSite\PortableBacklinkHydrator;

final class PortableBacklinkHydratorTest extends TestCase
{
    public function test_relative_directory_links_build_a_reusable_localized_projection(): void
    {
        $copy = [
            'navigation.backlinks_heading' => 'Ссылаются на эту страницу',
            'navigation.backlinks_empty' => 'Обратных ссылок пока нет.',
        ];
        $pages = [
            [
                'url' => '/ru/components/details/',
                'title' => 'Раскрывающийся блок',
                'content_html' => '<p><a href="../backlinks/">Обратные ссылки</a></p>',
                'ui_copy' => $copy,
            ],
            [
                'url' => '/ru/components/backlinks/',
                'title' => 'Обратные ссылки',
                'content_html' => '<nav data-docara-backlinks data-docara-backlinks-limit="5"></nav>',
                'ui_copy' => $copy,
            ],
        ];
        $hydrator = new PortableBacklinkHydrator;
        $index = $hydrator->index($pages);

        self::assertSame([[
            'url' => '/ru/components/details/',
            'title' => 'Раскрывающийся блок',
        ]], $index['/ru/components/backlinks/']);
        $isolated = $hydrator->hydrate([$pages[1]], $index);
        self::assertStringContainsString('Ссылаются на эту страницу', $isolated[0]['content_html']);
        self::assertStringContainsString(
            '<a href="/ru/components/details/">Раскрывающийся блок</a>',
            $isolated[0]['content_html'],
        );
    }
}
