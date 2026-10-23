<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\PortableSite\HtmlAttributeQuoting;

final class HtmlAttributeQuotingTest extends TestCase
{
    #[Test]
    public function single_quoted_attribute_values_become_double_quoted(): void
    {
        self::assertSame(
            '<p title="Say &quot;hi&quot;" class="a">Say "hi" and \'bye\'</p>',
            HtmlAttributeQuoting::normalize('<p title=\'Say "hi"\' class="a">Say "hi" and \'bye\'</p>'),
        );
        self::assertSame(
            '<section data-x="{&quot;speed&quot;:80}" data-y="a>b"></section>',
            HtmlAttributeQuoting::normalize('<section data-x=\'{"speed":80}\' data-y="a>b"></section>'),
        );
    }

    #[Test]
    public function text_comments_and_raw_text_elements_are_left_alone(): void
    {
        $html = "<!-- x='1' --><script>var a='b'; if (c<d) {}</script><style>a[x='y']{}</style><p>a='b' 1 < 2</p>";

        self::assertSame($html, HtmlAttributeQuoting::normalize($html));
    }

    #[Test]
    public function the_libxml_serialization_is_the_same_on_every_libxml_version(): void
    {
        $document = new \DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8"?><div title=\'He said "go"\'>x</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $node = $document->getElementsByTagName('div')->item(0);

        self::assertSame(
            '<div title="He said &quot;go&quot;">x</div>',
            HtmlAttributeQuoting::normalize((string) $document->saveHTML($node)),
        );
    }
}
