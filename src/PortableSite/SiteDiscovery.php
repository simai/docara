<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Portable\PortableConfigurationException;

/**
 * The discovery files of a site with a public address (docara.json
 * `site_url`): sitemap.xml, robots.txt and llms.txt at the deployment base.
 *
 * A portal build (docara.json `portal`) writes llms.txt only: the portal
 * owns sitemap.xml and robots.txt.
 *
 * A site without `site_url` gets none of them, so its output stays exactly
 * what it was. The files list only pages a search engine may index: a page
 * marked `noindex: true`, a page that shows the source text while its
 * translation is held back (translation_tracking mode publish-current), an
 * error page (a route whose last segment is a 4xx or 5xx status code),
 * redirects and drafts are left out.
 */
final readonly class SiteDiscovery
{
    public const SITEMAP = 'sitemap.xml';

    public const ROBOTS = 'robots.txt';

    public const LLMS = 'llms.txt';

    public const FILES = [self::SITEMAP, self::ROBOTS, self::LLMS];

    /**
     * @param  list<array{title:string,path:string,description:?string}>  $llmsLinks
     */
    private function __construct(
        public string $siteUrl,
        public string $origin,
        public string $basePath,
        public ?string $description,
        public array $llmsLinks,
        public bool $portal = false,
    ) {}

    /**
     * The discovery settings of a site, or null when it has no site_url.
     *
     * @param  array<string, mixed>  $site  docara.json, or a resolved page configuration
     */
    public static function fromSite(array $site): ?self
    {
        $siteUrl = $site['site_url'] ?? null;
        if ($siteUrl === null) {
            return null;
        }
        if (! is_string($siteUrl)
            || strlen($siteUrl) > 2048
            || preg_match('#\A(https?://[A-Za-z0-9](?:[A-Za-z0-9.-]*[A-Za-z0-9])?(?::[0-9]{1,5})?)(/(?:[A-Za-z0-9._~-]+/?)*)\z#D', $siteUrl, $match) !== 1
        ) {
            throw new PortableConfigurationException(
                'SITE_URL_INVALID',
                'site_url must be an absolute http(s) address without query, fragment or credentials, such as https://example.com/docs/.',
            );
        }
        $origin = strtolower($match[1]);
        $path = self::directory($match[2]);
        foreach (explode('/', trim($path, '/')) as $segment) {
            if ($segment === '.' || $segment === '..') {
                throw new PortableConfigurationException('SITE_URL_INVALID', 'site_url must not contain dot segments.');
            }
        }
        $base = self::directory(is_string($site['base_url'] ?? null) ? $site['base_url'] : '/');
        if ($path !== $base) {
            throw new PortableConfigurationException(
                'SITE_URL_BASE_MISMATCH',
                "site_url path [$path] must equal base_url [$base]; set both from the same deployment address.",
            );
        }
        $discovery = is_array($site['discovery'] ?? null) ? $site['discovery'] : [];
        $description = is_string($discovery['description'] ?? null) ? self::line($discovery['description']) : null;
        $links = [];
        foreach (is_array($discovery['llms_links'] ?? null) ? $discovery['llms_links'] : [] as $link) {
            $linkPath = (string) ($link['path'] ?? '');
            foreach (explode('/', trim($linkPath, '/')) as $segment) {
                if ($segment === '' || $segment === '.' || $segment === '..') {
                    throw new PortableConfigurationException('DISCOVERY_LINK_INVALID', "discovery.llms_links path [$linkPath] is unsafe.");
                }
            }
            $links[] = [
                'title' => self::line((string) ($link['title'] ?? '')),
                'path' => $linkPath,
                'description' => is_string($link['description'] ?? null) ? self::line($link['description']) : null,
            ];
        }

        // A portal build (docara.json `portal`) leaves sitemap.xml and
        // robots.txt to the portal and keeps llms.txt.
        return new self(
            $origin . $path,
            $origin,
            $path,
            $description === '' ? null : $description,
            $links,
            ($site['portal'] ?? null) !== null,
        );
    }

    /**
     * The discovery files this site writes: all three, or llms.txt alone for
     * a portal build.
     *
     * @return list<string>
     */
    public function fileNames(): array
    {
        return $this->portal ? [self::LLMS] : self::FILES;
    }

    /**
     * Whether a page belongs in sitemap.xml and llms.txt.
     *
     * @param  array<string, mixed>  $page  url, front matter noindex flag (`noindex`) and `translation_fallback`
     */
    public static function indexable(array $page): bool
    {
        if (($page['noindex'] ?? false) === true || ($page['translation_fallback'] ?? null) !== null) {
            return false;
        }
        $segments = array_values(array_filter(explode('/', (string) ($page['url'] ?? '')), static fn (string $part): bool => $part !== ''));
        $last = $segments === [] ? '' : $segments[count($segments) - 1];

        return preg_match('/\A[45][0-9]{2}(?:\.html)?\z/D', $last) !== 1;
    }

    /** The absolute address of a root-relative URL under the deployment base. */
    public function absolute(string $url): string
    {
        if (! str_starts_with($url, $this->basePath) && $url . '/' !== $this->basePath) {
            throw new PortableConfigurationException('DISCOVERY_URL_OUTSIDE_BASE', "URL [$url] is outside base [$this->basePath].");
        }

        return $this->origin . $url;
    }

    /**
     * The three files, keyed by their path under the deployment root.
     *
     * @param  list<array<string, mixed>>  $pages  url, locale, title, description, translation_key, noindex, translation_fallback
     * @param  list<array{title:string,url:string}>  $sections  the default locale's top-level navigation
     * @param  list<array{label:string,url:string,locale:string}>  $languages  each locale's home, default first
     * @return array<string, string>
     */
    public function files(array $pages, array $sections, string $siteTitle, ?string $homeDescription, array $languages): array
    {
        if ($this->portal) {
            return [self::LLMS => $this->llms($pages, $sections, $siteTitle, $homeDescription, $languages)];
        }

        return [
            self::SITEMAP => $this->sitemap($pages),
            self::ROBOTS => $this->robots(),
            self::LLMS => $this->llms($pages, $sections, $siteTitle, $homeDescription, $languages),
        ];
    }

    public function robots(): string
    {
        return "User-agent: *\nAllow: /\n\nSitemap: " . $this->siteUrl . self::SITEMAP . "\n";
    }

    /** @param list<array<string, mixed>> $pages */
    public function sitemap(array $pages): string
    {
        $indexable = [];
        foreach ($pages as $page) {
            if (self::indexable($page)) {
                $indexable[(string) $page['url']] = $page;
            }
        }
        ksort($indexable, SORT_STRING);
        $groups = [];
        foreach ($indexable as $url => $page) {
            $key = (string) ($page['translation_key'] ?? $url);
            $groups[$key][(string) $page['locale']] = $url;
        }
        foreach ($groups as &$group) {
            ksort($group, SORT_STRING);
        }
        unset($group);
        $alternatesUsed = false;
        $body = '';
        foreach ($indexable as $url => $page) {
            $body .= "  <url>\n    <loc>" . $this->xml($this->absolute($url)) . "</loc>\n";
            $group = $groups[(string) ($page['translation_key'] ?? $url)] ?? [];
            if (count($group) > 1) {
                $alternatesUsed = true;
                foreach ($group as $locale => $alternateUrl) {
                    $body .= '    <xhtml:link rel="alternate" hreflang="' . $this->xml((string) $locale)
                        . '" href="' . $this->xml($this->absolute($alternateUrl)) . "\"/>\n";
                }
            }
            $body .= "  </url>\n";
        }

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
            . ($alternatesUsed ? ' xmlns:xhtml="http://www.w3.org/1999/xhtml"' : '') . ">\n"
            . $body . "</urlset>\n";
    }

    /**
     * llms.txt after https://llmstxt.org: the site title, its description,
     * the main sections, the other languages and the machine-readable files.
     *
     * @param  list<array<string, mixed>>  $pages
     * @param  list<array{title:string,url:string}>  $sections
     * @param  list<array{label:string,url:string,locale:string}>  $languages
     */
    public function llms(array $pages, array $sections, string $siteTitle, ?string $homeDescription, array $languages): string
    {
        $descriptions = [];
        foreach ($pages as $page) {
            if (self::indexable($page)) {
                $descriptions[(string) $page['url']] = self::line((string) ($page['description'] ?? ''));
            }
        }
        $text = '# ' . self::line($siteTitle) . "\n";
        $summary = $this->description ?? ($homeDescription === null ? null : self::line($homeDescription));
        if ($summary !== null && $summary !== '') {
            $text .= "\n> " . $summary . "\n";
        }
        $sectionLines = '';
        foreach ($sections as $section) {
            if (! isset($descriptions[$section['url']])) {
                continue;
            }
            $sectionLines .= $this->item($section['title'], $this->absolute($section['url']), $descriptions[$section['url']]);
        }
        if ($sectionLines !== '') {
            $text .= "\n## Sections\n\n" . $sectionLines;
        }
        if (count($languages) > 1) {
            $text .= "\n## Languages\n\n";
            foreach ($languages as $language) {
                $text .= $this->item($language['label'], $this->absolute($language['url']), null);
            }
        }
        $text .= "\n## Machine-readable\n\n";
        foreach ($this->llmsLinks as $link) {
            $text .= $this->item($link['title'], $this->siteUrl . $link['path'], $link['description']);
        }
        if (! $this->portal) {
            $text .= $this->item('Sitemap', $this->siteUrl . self::SITEMAP, null);
        }
        $text .= $this->item('Page metadata', $this->siteUrl . '_docara/page-metadata.json', 'URL, title, locale and source of every page');

        return $text;
    }

    private function item(string $title, string $url, ?string $description): string
    {
        $title = str_replace(['\\', '[', ']'], ['\\\\', '\\[', '\\]'], self::line($title));

        return '- [' . $title . '](' . $url . ')' . ($description === null || $description === '' ? '' : ': ' . $description) . "\n";
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function line(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private static function directory(string $path): string
    {
        $trimmed = trim($path, '/');

        return $trimmed === '' ? '/' : '/' . $trimmed . '/';
    }
}
