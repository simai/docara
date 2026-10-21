<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\I18n\LocaleRegistry;
use Simai\Docara\I18n\LocaleRoutingPolicy;
use Simai\Docara\I18n\LocaleUrlProjector;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\ResolvedPagePlan;
use Simai\Docara\PortableSite\NotFoundPage;
use Simai\Docara\PortableSite\PortalProfile;
use Simai\Docara\PortableSite\SiteDiscovery;

final class NotFoundPageTest extends TestCase
{
    #[Test]
    public function the_page_sits_at_the_deployment_base_in_the_default_locale(): void
    {
        $page = $this->page($this->prefixedSite());

        self::assertNotNull($page);
        self::assertSame('ru', $page->locale);
        self::assertSame('content/ru/404.md', $page->sourcePath);
        self::assertSame(['url' => '/docs/404.html', 'output' => '404.html'], $page->route());
        self::assertSame('/docs/ru/404/', $page->routeUrl);
        self::assertSame('/docs/ru/', $page->homeUrl);
    }

    #[Test]
    public function the_page_can_be_turned_off_and_a_portal_build_has_none(): void
    {
        self::assertNull($this->page(['not_found' => ['enabled' => false]] + $this->prefixedSite()));
        self::assertNotNull($this->page(['not_found' => ['enabled' => true]] + $this->prefixedSite()));

        $portal = $this->portalSite();
        self::assertNull($this->page($portal, true));

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('the portal owns it');
        $this->page(['not_found' => ['enabled' => true]] + $portal, true);
    }

    #[Test]
    public function relative_urls_resolve_from_the_ordinary_route_and_others_stay(): void
    {
        $page = $this->page($this->prefixedSite());
        self::assertNotNull($page);

        self::assertSame('/docs/ru/assets/x.png', $page->resolve('../assets/x.png'));
        self::assertSame('/docs/ru/start/?q=1#a', $page->resolve('../start/?q=1#a'));
        self::assertSame('/docs/ru/404/', $page->resolve('./'));
        self::assertSame('/docs/ru/404/child', $page->resolve('child'));
        self::assertSame('/docs/', $page->resolve('../../'));
        self::assertSame('/', $page->resolve('../../../../'));
        foreach (['/docs/ru/', '#top', 'https://example.org/', 'mailto:a@b.c', '//cdn.example/x.js', ''] as $kept) {
            self::assertSame($kept, $page->resolve($kept));
        }

        $html = '<p><a href="../start/">s</a> <a href="#x">x</a> <img src="../assets/a.png" srcset="../assets/a.png 1x, ../assets/b.png 2x" alt="">'
            . '<video poster="cover.jpg" src="/docs/v.mp4"></video><span data-href="../no/">t</span></p>';
        self::assertSame(
            '<p><a href="/docs/ru/start/">s</a> <a href="#x">x</a> <img src="/docs/ru/assets/a.png" srcset="/docs/ru/assets/a.png 1x, /docs/ru/assets/b.png 2x" alt="">'
            . '<video poster="/docs/ru/404/cover.jpg" src="/docs/v.mp4"></video><span data-href="../no/">t</span></p>',
            $page->absolutize($html),
        );
    }

    #[Test]
    public function docara_content_escapes_the_strings_and_links_from_the_root(): void
    {
        $page = $this->page($this->prefixedSite());
        self::assertNotNull($page);
        $copy = [
            'error.not_found.title' => 'Нет <такой> страницы',
            'error.not_found.message' => "Проверьте [адрес] *или*\n# поиск & ссылки.",
            'error.not_found.home' => 'Домой & назад',
            'error.not_found.search' => 'Искать',
        ];

        self::assertSame(
            "# Нет \\<такой\\> страницы\n\nПроверьте \\[адрес\\] \\*или\\* \\# поиск \\& ссылки\\.\n",
            $page->markdown($copy),
        );
        self::assertSame(
            ['title' => 'Нет <такой> страницы', 'description' => $copy['error.not_found.message'], 'noindex' => true],
            $page->frontMatter($copy),
        );
        self::assertSame(
            '<ul class="docara-not-found-links" data-docara-not-found-links><li><a href="/docs/ru/">Домой &amp; назад</a></li>'
            . '<li><a href="/docs/ru/" data-docara-search-open>Искать</a></li></ul>',
            $page->linksHtml($copy, true),
        );
        self::assertStringNotContainsString('data-docara-search-open', $page->linksHtml($copy, false));
    }

    #[Test]
    public function the_plan_is_noindexed_out_of_navigation_search_and_reading_links(): void
    {
        $page = $this->page($this->prefixedSite());
        self::assertNotNull($page);
        $plan = $page->plan(new ResolvedPagePlan(
            'content/ru/404.md',
            '# Нет',
            [
                'search' => ['enabled' => true, 'indexed' => true],
                'navigation' => ['order' => 3],
                'reading' => ['toc' => true, 'toc_depth' => 3, 'breadcrumbs' => true, 'previous_next' => true],
            ],
            [],
            [],
            ['/search/indexed' => 'docara.json'],
            ['title' => 'Нет'],
        ));

        self::assertSame(['enabled' => true, 'indexed' => false], $plan->configuration['search']);
        self::assertSame(['order' => 3, 'hidden' => true], $plan->configuration['navigation']);
        self::assertSame(
            ['toc' => false, 'toc_depth' => 3, 'breadcrumbs' => false, 'previous_next' => false],
            $plan->configuration['reading'],
        );
        self::assertSame(['title' => 'Нет', 'noindex' => true], $plan->frontMatter);
        self::assertSame('@docara/not-found', $plan->provenance['/search/indexed']);
    }

    /** @param array<string, mixed> $site */
    private function page(array $site, bool $portal = false): ?NotFoundPage
    {
        $locales = LocaleRegistry::fromSite($site);
        $routing = LocaleRoutingPolicy::fromSite($site, $locales);

        return NotFoundPage::fromSite(
            $site,
            $portal ? PortalProfile::fromSite($site, $locales, $routing, SiteDiscovery::fromSite($site)) : null,
            $locales->default(),
            new LocaleUrlProjector((string) $site['base_url'], $locales, $routing),
        );
    }

    /** @return array<string, mixed> */
    private function prefixedSite(): array
    {
        return [
            'base_url' => '/docs/',
            'default_locale' => 'ru',
            'locales' => [
                'ru' => ['label' => 'Русский', 'direction' => 'ltr', 'content_root' => 'content/ru', 'public_prefix' => 'ru', 'fallbacks' => []],
                'en' => ['label' => 'English', 'direction' => 'ltr', 'content_root' => 'content/en', 'public_prefix' => 'en', 'fallbacks' => []],
            ],
            'locale_routing' => ['strategy' => 'prefixed', 'root' => 'redirect', 'detect_browser_language' => false],
        ];
    }

    /** @return array<string, mixed> */
    private function portalSite(): array
    {
        return [
            'base_url' => '/ru/product/docs/',
            'site_url' => 'https://portal.example/ru/product/docs/',
            'portal' => ['product' => 'product-docs', 'home_url' => 'https://portal.example/ru/'],
            'default_locale' => 'ru',
            'locales' => [
                'ru' => ['label' => 'Русский', 'direction' => 'ltr', 'content_root' => 'content/ru', 'public_prefix' => '', 'fallbacks' => []],
            ],
            'locale_routing' => ['strategy' => 'default_unprefixed', 'root' => 'default_locale', 'detect_browser_language' => false],
        ];
    }
}
