<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\I18n\LocaleInternalLinkProjector;
use Tests\TestCase;

final class LocaleMediaRebaseTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function mediaAttributes(): array
    {
        return [
            'img src' => ['<img src="/ru/guide/x.png" alt="a > b">', '<img src="/docara/ru/guide/x.png" alt="a > b">'],
            'img srcset' => [
                '<img srcset="/a.png 1x, /b.png 2x" src="/a.png">',
                '<img srcset="/docara/a.png 1x, /docara/b.png 2x" src="/docara/a.png">',
            ],
            'source src' => ['<source src="/v.webm" type="video/webm">', '<source src="/docara/v.webm" type="video/webm">'],
            'source srcset' => [
                "<source srcset='/w-480.webp 480w,/w-960.webp 960w' sizes=\"50vw\">",
                "<source srcset='/docara/w-480.webp 480w,/docara/w-960.webp 960w' sizes=\"50vw\">",
            ],
            'video src and poster' => [
                '<video src="/v.mp4" poster="/p.jpg" controls></video>',
                '<video src="/docara/v.mp4" poster="/docara/p.jpg" controls></video>',
            ],
            'audio src' => ['<audio src="/a.mp3"></audio>', '<audio src="/docara/a.mp3"></audio>'],
            'track src' => ['<track src="/c.vtt" kind="captions">', '<track src="/docara/c.vtt" kind="captions">'],
            'picture' => [
                '<picture><source srcset="/x.avif" type="image/avif"><img src="/x.png" alt=""></picture>',
                '<picture><source srcset="/docara/x.avif" type="image/avif"><img src="/docara/x.png" alt=""></picture>',
            ],
            'link icon' => ['<link rel="icon" href="/favicon.svg">', '<link rel="icon" href="/docara/favicon.svg">'],
            'link manifest' => ['<link href="/site.webmanifest" rel="manifest">', '<link href="/docara/site.webmanifest" rel="manifest">'],
        ];
    }

    #[Test]
    #[DataProvider('mediaAttributes')]
    public function media_are_rebased_under_a_prefix_and_untouched_at_the_root(string $html, string $expected): void
    {
        self::assertSame($expected, (new LocaleInternalLinkProjector([], '/docara/'))->project($html));
        self::assertSame($html, (new LocaleInternalLinkProjector([], '/'))->project($html));
        self::assertSame($html, (new LocaleInternalLinkProjector(['/a.png' => '/ru/a.png'], '/'))->project($html));
    }

    #[Test]
    public function srcset_rebases_each_candidate_and_leaves_the_others(): void
    {
        $projector = new LocaleInternalLinkProjector([], '/x/');

        self::assertSame(
            '<img srcset="/x/a.png 1x, data:image/png;base64,AA,BB 2x, rel.png 3x, https://e.test/c.png 4x, //cdn.test/d.png 5x, /x/e.png 6x,/x/f.png">',
            $projector->project('<img srcset="/a.png 1x, data:image/png;base64,AA,BB 2x, rel.png 3x, https://e.test/c.png 4x, //cdn.test/d.png 5x, /x/e.png 6x,/f.png">'),
        );
        self::assertSame(
            [['a.png', 0], ['b.png', 9], ['c.png', 16], ['d', 34]],
            LocaleInternalLinkProjector::srcsetUrls('a.png 1x,b.png, c.png (1,2) 2x , ,d'),
        );
    }

    #[Test]
    public function external_data_relative_and_unrelated_urls_are_left_alone(): void
    {
        $html = '<img src="https://example.com/x.png">'
            . '<img src="//cdn.example.com/x.png">'
            . '<img src="data:image/gif;base64,R0lGOD">'
            . '<img src="images/x.png">'
            . '<img src="../assets/x.png">'
            . '<img src="/x/already.png">'
            . '<img data-src="/lazy.png" alt="">'
            . '<video poster="https://example.com/p.jpg"></video>'
            . '<link rel="stylesheet" href="/style.css">'
            . '<link rel="canonical" href="/page/">'
            . '<pre><code>&lt;img src="/code.png"&gt;</code></pre>'
            . '<iframe title="e" srcdoc="&lt;img src=&quot;/inner.png&quot;&gt;"></iframe>';

        self::assertSame($html, (new LocaleInternalLinkProjector([], '/x/'))->project($html));
    }

    #[Test]
    public function an_encoded_attribute_value_is_rebased_and_reencoded(): void
    {
        self::assertSame(
            '<img src="/x/a.png?w=1&amp;h=2">',
            (new LocaleInternalLinkProjector([], '/x/'))->project('<img src="/a.png?w=1&amp;h=2">'),
        );
    }
}
