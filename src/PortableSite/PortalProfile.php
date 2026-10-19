<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\I18n\LocaleRegistry;
use Simai\Docara\I18n\LocaleRoutingPolicy;
use Simai\Docara\Portable\PortableConfigurationException;

/**
 * The portal profile of a site (docara.json `portal`): a build made to be
 * unpacked under one path of a larger portal, such as /ru/docara/docs/ on
 * https://simai.io/.
 *
 * The portal owns the hosting files and the locale prefix, so a portal build
 * has one locale, routed without a prefix, and an absolute site_url for its
 * canonical addresses. It writes llms.txt but no sitemap.xml or robots.txt,
 * refuses to publish any of the files the portal owns, and links back to the
 * portal home from its header. `docara portal-artifact` packs the build.
 *
 * A site without `portal` builds exactly as before.
 */
final readonly class PortalProfile
{
    /** Files at the top of the build that belong to the portal, not the site. */
    public const PORTAL_FILES = ['CNAME', '.nojekyll', 'robots.txt', 'sitemap.xml', '404.html'];

    public const PRODUCT_PATTERN = '/\A[a-z][a-z0-9-]{1,63}\z/D';

    private function __construct(
        public string $product,
        public string $homeUrl,
        public string $homeLabel,
        public string $siteUrl,
        public string $basePath,
        public string $locale,
    ) {}

    /**
     * The portal profile of a site, or null when docara.json has no `portal`.
     *
     * @param  array<string, mixed>  $site
     */
    public static function fromSite(
        array $site,
        LocaleRegistry $locales,
        LocaleRoutingPolicy $routing,
        ?SiteDiscovery $discovery,
    ): ?self {
        $portal = $site['portal'] ?? null;
        if ($portal === null) {
            return null;
        }
        if (! is_array($portal) || array_is_list($portal)) {
            throw new PortableConfigurationException('PORTAL_INVALID', 'portal must be an object.');
        }
        $product = $portal['product'] ?? null;
        if (! is_string($product) || preg_match(self::PRODUCT_PATTERN, $product) !== 1) {
            throw new PortableConfigurationException(
                'PORTAL_PRODUCT_INVALID',
                'portal.product must be a portal product id: lowercase letters, digits and hyphens, starting with a letter.',
            );
        }
        $homeUrl = $portal['home_url'] ?? null;
        if (! is_string($homeUrl)
            || strlen($homeUrl) > 2048
            || preg_match('#\Ahttps://[A-Za-z0-9](?:[A-Za-z0-9.-]*[A-Za-z0-9])?(?::[0-9]{1,5})?(?:/[A-Za-z0-9._~%-]*)*\z#D', $homeUrl) !== 1
        ) {
            throw new PortableConfigurationException(
                'PORTAL_HOME_URL_INVALID',
                'portal.home_url must be an absolute https address without query or fragment, such as https://simai.io/ru/.',
            );
        }
        $label = $portal['home_label'] ?? null;
        if ($label !== null && (! is_string($label) || trim($label) === '' || mb_strlen($label) > 80)) {
            throw new PortableConfigurationException(
                'PORTAL_HOME_LABEL_INVALID',
                'portal.home_label must be a non-empty string of at most 80 characters.',
            );
        }
        $label = $label === null
            ? (string) parse_url($homeUrl, PHP_URL_HOST)
            : trim((string) preg_replace('/\s+/u', ' ', $label));
        if ($discovery === null) {
            throw new PortableConfigurationException(
                'PORTAL_SITE_URL_REQUIRED',
                'A portal build needs site_url: its full address on the portal, such as https://simai.io/ru/docara/docs/.',
            );
        }
        $all = $locales->all();
        if (count($all) !== 1) {
            throw new PortableConfigurationException(
                'PORTAL_SINGLE_LOCALE_REQUIRED',
                'A portal build holds one locale; the locale prefix belongs to the portal path. Build each locale separately.',
            );
        }
        if ($routing->strategy !== LocaleRoutingPolicy::STRATEGY_DEFAULT_UNPREFIXED) {
            throw new PortableConfigurationException(
                'PORTAL_UNPREFIXED_ROUTING_REQUIRED',
                'A portal build uses locale_routing.strategy default_unprefixed; the portal path carries the locale.',
            );
        }

        return new self(
            $product,
            $homeUrl,
            $label,
            $discovery->siteUrl,
            $discovery->basePath,
            (string) array_key_first($all),
        );
    }

    /**
     * Fails when the build would publish a file the portal owns.
     *
     * @param  string  $destination  the build candidate
     */
    public function assertNoPortalFiles(string $destination): void
    {
        foreach (self::PORTAL_FILES as $file) {
            $path = rtrim($destination, '/\\') . '/' . $file;
            if (file_exists($path) || is_link($path)) {
                throw new PortableConfigurationException(
                    'PORTAL_FILE_COLLISION',
                    "A portal build must not publish [$file]: the portal owns it. Remove it from the content.",
                );
            }
        }
    }

    /** @return array{url:string,label:string} */
    public function home(): array
    {
        return ['url' => $this->homeUrl, 'label' => $this->homeLabel];
    }
}
