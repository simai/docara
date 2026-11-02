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
}
