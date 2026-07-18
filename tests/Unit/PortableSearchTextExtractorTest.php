<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableSearchTextExtractor;

final class PortableSearchTextExtractorTest extends TestCase
{
    #[Test]
    public function it_extracts_visible_markdown_and_explicit_smart_component_text(): void
    {
        $result = (new PortableSearchTextExtractor)->extract(
            '<h1>Начало</h1><h2>Наследование</h2><p>Видимый текст.</p>'
            . '<div hidden>Скрытое содержимое</div><p aria-hidden="true">Скрыто от чтения</p>'
            . '<script>danger()</script><style>.secret{}</style><template>Черновик</template>'
            . '<pre><code>docara build production</code></pre>',
            [
                [
                    'id' => 'ui.alert',
                    'props' => [
                        'title' => 'Обратите внимание',
                        'supporting-text' => 'Индекс строится локально.',
                        'aria-label' => 'Не индексировать повторно',
                        'scheme' => 'info',
                    ],
                ],
                [
                    'id' => 'ui.button',
                    'props' => [
                        'text' => 'Открыть руководство',
                        'action' => 'unsafeInternalAction',
                    ],
                ],
            ],
        );

        self::assertSame([
            ['level' => 1, 'text' => 'Начало'],
            ['level' => 2, 'text' => 'Наследование'],
        ], $result['headings']);
        self::assertStringContainsString('Видимый текст.', $result['text']);
        self::assertStringContainsString('docara build production', $result['text']);
        self::assertStringContainsString('Обратите внимание', $result['text']);
        self::assertStringContainsString('Индекс строится локально.', $result['text']);
        self::assertStringContainsString('Открыть руководство', $result['text']);
        self::assertStringNotContainsString('Скрытое содержимое', $result['text']);
        self::assertStringNotContainsString('Скрыто от чтения', $result['text']);
        self::assertStringNotContainsString('danger', $result['text']);
        self::assertStringNotContainsString('Черновик', $result['text']);
        self::assertStringNotContainsString('Не индексировать повторно', $result['text']);
        self::assertStringNotContainsString('unsafeInternalAction', $result['text']);
    }

    #[Test]
    public function it_rejects_invalid_utf8_and_unprojected_components(): void
    {
        foreach ([
            ["<p>\xB1\x31</p>", [], 'SEARCH_TEXT_INVALID_UTF8'],
            ['<p>Text</p>', [['id' => 'ui.tabs', 'props' => []]], 'SEARCH_COMPONENT_TEXT_PROJECTION_MISSING'],
        ] as [$html, $calls, $expectedCode]) {
            try {
                (new PortableSearchTextExtractor)->extract($html, $calls);
                self::fail("Invalid search input unexpectedly passed [$expectedCode].");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($expectedCode, $exception->errorCode);
            }
        }
    }
}
