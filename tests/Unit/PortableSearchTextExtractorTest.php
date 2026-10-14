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
            . '<pre><code>docara build production</code></pre>'
            . '<sf-alert title="Обратите внимание" supporting-text="Индекс строится локально." aria-label="Не индексировать повторно"></sf-alert>'
            . '<sf-button text="Открыть руководство" data-action="unsafeInternalAction"></sf-button>',
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
    public function it_rejects_invalid_utf8_and_invalid_component_metadata(): void
    {
        foreach ([
            ["<p>\xB1\x31</p>", [], 'SEARCH_TEXT_INVALID_UTF8'],
            ['<p>Text</p>', [['id' => null, 'props' => []]], 'SEARCH_COMPONENT_CALL_INVALID'],
        ] as [$html, $calls, $expectedCode]) {
            try {
                (new PortableSearchTextExtractor)->extract($html, $calls);
                self::fail("Invalid search input unexpectedly passed [$expectedCode].");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($expectedCode, $exception->errorCode);
            }
        }
    }

    #[Test]
    public function it_indexes_unknown_custom_elements_without_a_component_id_policy(): void
    {
        $result = (new PortableSearchTextExtractor)->extract(
            '<acme-notice title="Local title" description="Local description"></acme-notice>',
            [['id' => 'acme.notice', 'props' => ['internal' => 'not projected']]],
        );

        self::assertSame('Local title Local description', $result['text']);
        self::assertStringNotContainsString('not projected', $result['text']);
    }
}
