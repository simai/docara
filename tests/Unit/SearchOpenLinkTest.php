<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\SearchOpenLink;

/**
 * `[text](#docara-search)`: a Markdown link that opens the search dialog and,
 * without JavaScript, leads to the home page.
 */
final class SearchOpenLinkTest extends TestCase
{
    #[Test]
    public function a_markdown_link_to_the_reserved_fragment_opens_search(): void
    {
        $html = (new PortableMarkdownRenderer)->render("Nothing here. [Open search](#docara-search) or go on.\n");
        self::assertStringContainsString('<a href="#docara-search">Open search</a>', $html);

        self::assertSame(
            str_replace('<a href="#docara-search">', '<a href="/ru/" data-docara-search-open>', $html),
            SearchOpenLink::apply($html, '/ru/', true),
        );
    }

    #[Test]
    public function hero_actions_and_cta_links_keep_their_classes(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $hero = $renderer->render(":::hero {variant=centered}\n# Lost\n\nText.\n\n[Home](/)\n\n[Search](#docara-search)\n:::\n");
        $applied = SearchOpenLink::apply($hero, '/docs/', true);
        self::assertMatchesRegularExpression(
            '#<a data-docara-hero-action class="sf-button [^"]*" href="/docs/" data-docara-search-open><span class="sf-button-text-container">Search</span></a>#',
            $applied,
        );
        self::assertStringContainsString('href="/"><span class="sf-button-text-container">Home</span>', $applied);

        $cta = $renderer->render(":::cta\n[Search](#docara-search)\n:::\n");
        self::assertMatchesRegularExpression(
            '#<a data-docara-block="cta" class="[^"]*" href="/docs/" data-docara-search-open>#',
            SearchOpenLink::apply($cta, '/docs/', true),
        );
    }

    #[Test]
    public function without_search_the_link_is_an_ordinary_home_link(): void
    {
        self::assertSame(
            '<p><a href="/">Open search</a></p>',
            SearchOpenLink::apply('<p><a href="#docara-search">Open search</a></p>', '/', false),
        );
    }

    #[Test]
    public function other_fragments_and_lookalikes_are_left_alone(): void
    {
        $html = '<p><a href="#docara-search-tips">Tips</a> <a href="/search/#docara-search">Page</a>'
            . ' <a href="#docara-searches">More</a> <a title="#docara-search" href="#top">Top</a></p>';
        self::assertSame($html, SearchOpenLink::apply($html, '/', true));
    }

    #[Test]
    public function the_home_url_is_escaped_and_the_marker_is_not_doubled(): void
    {
        self::assertSame(
            '<a href="/a&amp;b/" data-docara-search-open>x</a>',
            SearchOpenLink::apply('<a href="#docara-search">x</a>', '/a&b/', true),
        );
        self::assertSame(
            '<a href="/" data-docara-search-open>x</a>',
            SearchOpenLink::apply('<a href=\'#docara-search\' data-docara-search-open>x</a>', '/', true),
        );
    }
}
