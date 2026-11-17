<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\PortableSite\PortableSiteHeader;

final class PortableSiteHeaderTest extends TestCase
{
    private const COPY = [
        'navigation.open' => 'Open menu',
        'navigation.close' => 'Close menu',
        'navigation.mobile_title' => 'Menu',
    ];

    #[Test]
    public function sections_a_site_adds_go_to_the_actions_slot_before_the_tools(): void
    {
        $region = '<section data-docara-section="docara.header"><a data-docara-smart="docara.brand" href="/">Brand</a>'
            . '<nav class="docara-header-navigation">Links</nav></section>'
            . '<section data-docara-section="docara.shell"><button type="button">Get a key</button></section>';

        $html = (new PortableSiteHeader)->render($region, null, '<div class="tools">Search</div>', '', self::COPY, 'xl');

        // The first-paint picture places the added section after the
        // navigation and before the tools, as the drawn bar does.
        $picture = substr($html, 0, (int) strpos($html, '</header>'));
        self::assertLessThan(strpos($picture, 'class="sf-site-header-actions"'), strpos($picture, 'class="sf-site-header-nav"'));
        self::assertLessThan(strpos($picture, 'class="sf-site-header-tools"'), strpos($picture, 'class="sf-site-header-actions"'));
        self::assertStringNotContainsString('Get a key', substr($picture, (int) strpos($picture, 'sf-site-header-nav'), (int) strpos($picture, 'sf-site-header-actions') - (int) strpos($picture, 'sf-site-header-nav')));

        self::assertStringContainsString('<div slot="actions" data-sf-held class="docara-header-extra flex items-cross-center gap-1"><section data-docara-section="docara.shell"><button type="button">Get a key</button></section></div>', $html);
        self::assertStringContainsString('<nav class="docara-header-navigation">Links</nav>', $html);
    }

    #[Test]
    public function a_header_without_added_sections_has_no_actions_slot(): void
    {
        $region = '<section data-docara-section="docara.header"><a data-docara-smart="docara.brand" href="/">Brand</a>'
            . '<nav class="docara-header-navigation">Links</nav></section>';

        $html = (new PortableSiteHeader)->render($region, null, '<div class="tools">Search</div>', '', self::COPY, 'xl');

        self::assertStringNotContainsString('sf-site-header-actions', $html);
        self::assertStringNotContainsString('slot="actions"', $html);
    }

    #[Test]
    public function the_language_picker_is_a_tools_element_of_its_own(): void
    {
        // Collapsed, sf-header-site 1.11 moves a slotted tools element that
        // holds a setting (the language picker) into its panel. Held in one
        // element with the search and the reader settings, it took them along
        // and left the narrow bar with no tools.
        $region = '<section data-docara-section="docara.header"><a data-docara-smart="docara.brand" href="/">Brand</a></section>';
        $tools = '<div class="docara-header-tools flex items-cross-center gap-1/3">'
            . "\n    <sf-button id=\"docara-search-trigger\">Search</sf-button>"
            . "\n    <sf-language data-docara-language mode=\"menu\"><script type=\"application/json\" data-sf-settings>{}</script></sf-language>"
            . "\n    <button id=\"docara-reader-settings-trigger\">Settings</button>\n</div>";

        $html = (new PortableSiteHeader)->render($region, null, $tools, '', self::COPY, 'xl');
        $held = substr($html, (int) strpos($html, '</header>'));

        self::assertSame(3, substr_count($held, 'slot="tools" data-sf-held'));
        self::assertStringContainsString('<div slot="tools" data-sf-held class="docara-header-tools flex items-cross-center gap-1/3">' . "\n    <sf-button id=\"docara-search-trigger\">Search</sf-button>\n    </div>", $held);
        self::assertStringContainsString('<sf-language slot="tools" data-sf-held data-docara-language mode="menu">', $held);
        self::assertStringContainsString('<div slot="tools" data-sf-held class="docara-header-tools flex items-cross-center gap-1/3">' . "\n    <button id=\"docara-reader-settings-trigger\">Settings</button>\n</div>", $held);
        // The order stays search, language, settings, in the picture as well.
        $picture = substr($html, 0, (int) strpos($html, '</header>'));
        self::assertLessThan(strpos($picture, '<sf-language'), strpos($picture, 'Search'));
        self::assertLessThan(strpos($picture, 'Settings'), strpos($picture, '<sf-language'));
        self::assertStringNotContainsString('id="docara-search-trigger"', $picture);
    }

    #[Test]
    public function tools_without_a_language_picker_stay_one_element(): void
    {
        $region = '<section data-docara-section="docara.header"><a data-docara-smart="docara.brand" href="/">Brand</a></section>';
        $tools = '<div class="docara-header-tools flex items-cross-center gap-1/3"><sf-button>Search</sf-button><button>Settings</button></div>';

        $html = (new PortableSiteHeader)->render($region, null, $tools, '', self::COPY, 'xl');

        self::assertSame(1, substr_count($html, 'slot="tools"'));
        self::assertStringContainsString('<div slot="tools" data-sf-held class="docara-header-tools flex items-cross-center gap-1/3"><sf-button>Search</sf-button><button>Settings</button></div>', $html);
    }
}
