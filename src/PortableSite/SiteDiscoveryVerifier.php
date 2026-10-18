<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use DOMDocument;
use DOMElement;

/**
 * Checks the discovery files of a built site against its resolved page plans:
 * sitemap.xml, robots.txt and llms.txt are well-formed, every address is
 * under site_url, every sitemap URL is a built indexable page, and every
 * indexable page is in the sitemap. A site without site_url is not checked.
 */
final readonly class SiteDiscoveryVerifier
{
    private const SITEMAP_NS = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    private const XHTML_NS = 'http://www.w3.org/1999/xhtml';

    /**
     * @param  list<array<string, mixed>>  $records  resolved-page-plans.json pages
     * @return list<string> problems; empty when the files are right
     */
    public function verify(string $root, array $records): array
    {
        $configuration = $records[0]['resolved_page_plan']['configuration'] ?? null;
        if (! is_array($configuration)) {
            return [];
        }
        try {
            $discovery = SiteDiscovery::fromSite($configuration);
        } catch (\Throwable $exception) {
            return ['site_url is invalid: ' . $exception->getMessage()];
        }
        if ($discovery === null) {
            return [];
        }
        $root = rtrim($root, '/\\');
        $indexable = [];
        foreach ($records as $record) {
            $page = [
                'url' => (string) ($record['url'] ?? ''),
                'locale' => (string) ($record['locale'] ?? ''),
                'output' => (string) ($record['output'] ?? ''),
                'noindex' => ($record['resolved_page_plan']['front_matter']['noindex'] ?? false) === true,
                'translation_fallback' => $record['translation_fallback'] ?? null,
            ];
            if (SiteDiscovery::indexable($page)) {
                $indexable[$page['url']] = $page;
            }
        }
        $problems = [];
        foreach (SiteDiscovery::FILES as $file) {
            if (! $this->safeFile($root . '/' . $file)) {
                $problems[] = "[$file] is missing or unsafe although site_url is set.";
            }
        }
        if ($problems !== []) {
            return $problems;
        }
        if ((string) file_get_contents($root . '/' . SiteDiscovery::ROBOTS) !== $discovery->robots()) {
            $problems[] = '[robots.txt] must allow all crawlers and name ' . $discovery->siteUrl . SiteDiscovery::SITEMAP . '.';
        }
        array_push($problems, ...$this->sitemap($root, $discovery, $indexable));
        array_push($problems, ...$this->llms($root, $discovery));

        return $problems;
    }

    /**
     * @param  array<string, array{url:string,locale:string,output:string}>  $indexable
     * @return list<string>
     */
    private function sitemap(string $root, SiteDiscovery $discovery, array $indexable): array
    {
        $xml = (string) file_get_contents($root . '/' . SiteDiscovery::SITEMAP);
        if (stripos($xml, '<!DOCTYPE') !== false || stripos($xml, '<!ENTITY') !== false) {
            return ['[sitemap.xml] must not declare a DOCTYPE or entities.'];
        }
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $urlset = $document->documentElement;
        if (! $loaded || ! $urlset instanceof DOMElement
            || $urlset->localName !== 'urlset'
            || $urlset->namespaceURI !== self::SITEMAP_NS
        ) {
            return ['[sitemap.xml] is not a well-formed sitemaps.org urlset.'];
        }
        $problems = [];
        $listed = [];
        foreach ($urlset->childNodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            if ($node->localName !== 'url' || $node->namespaceURI !== self::SITEMAP_NS) {
                $problems[] = "[sitemap.xml] has an unexpected element [{$node->localName}].";

                continue;
            }
            $locations = $node->getElementsByTagNameNS(self::SITEMAP_NS, 'loc');
            if ($locations->length !== 1) {
                $problems[] = '[sitemap.xml] has a url entry without exactly one loc.';

                continue;
            }
            $loc = trim((string) $locations->item(0)?->textContent);
            $url = $this->pageUrl($discovery, $loc);
            if ($url === null) {
                $problems[] = "[sitemap.xml] lists [$loc] outside site_url " . $discovery->siteUrl . '.';

                continue;
            }
            if (isset($listed[$url])) {
                $problems[] = "[sitemap.xml] lists [$loc] twice.";

                continue;
            }
            $listed[$url] = true;
            if (! isset($indexable[$url]) || ! $this->safeFile($root . '/' . $indexable[$url]['output'])) {
                $problems[] = "[sitemap.xml] lists [$loc], which is not a built indexable page.";
            }
            foreach ($node->getElementsByTagNameNS(self::XHTML_NS, 'link') as $link) {
                $href = $link->getAttribute('href');
                $alternate = $this->pageUrl($discovery, $href);
                if ($link->getAttribute('rel') !== 'alternate'
                    || $alternate === null
                    || ! isset($indexable[$alternate])
                    || $indexable[$alternate]['locale'] !== $link->getAttribute('hreflang')
                ) {
                    $problems[] = "[sitemap.xml] alternate [$href] for [$loc] is not a built indexable page in its hreflang.";
                }
            }
        }
        foreach (array_keys($indexable) as $url) {
            if (! isset($listed[$url])) {
                $problems[] = '[sitemap.xml] misses the indexable page [' . $discovery->absolute($url) . '].';
            }
        }

        return $problems;
    }

    /** @return list<string> */
    private function llms(string $root, SiteDiscovery $discovery): array
    {
        $text = (string) file_get_contents($root . '/' . SiteDiscovery::LLMS);
        if (preg_match('//u', $text) !== 1 || preg_match('/\A# \S[^\n]*\n/u', $text) !== 1) {
            return ['[llms.txt] must be UTF-8 and start with a "# <site title>" line.'];
        }
        $problems = [];
        preg_match_all('/\]\(([^)\s]+)\)/u', $text, $links);
        if (($links[1] ?? []) === []) {
            $problems[] = '[llms.txt] links to nothing.';
        }
        foreach ($links[1] ?? [] as $href) {
            if (! str_starts_with($href, $discovery->siteUrl)) {
                $problems[] = "[llms.txt] links to [$href] outside site_url " . $discovery->siteUrl . '.';

                continue;
            }
            $relative = substr($href, strlen($discovery->siteUrl));
            if ($relative === '' || str_ends_with($relative, '/')) {
                $relative .= 'index.html';
            }
            if (str_contains('/' . $relative . '/', '/../') || ! $this->safeFile($root . '/' . rawurldecode($relative))) {
                $problems[] = "[llms.txt] links to [$href], which is not a file of the build.";
            }
        }

        return $problems;
    }

    private function pageUrl(SiteDiscovery $discovery, string $absolute): ?string
    {
        if (! str_starts_with($absolute, $discovery->siteUrl)) {
            return null;
        }
        $url = substr($absolute, strlen($discovery->origin));

        return str_contains('/' . $url . '/', '/../') ? null : $url;
    }

    private function safeFile(string $path): bool
    {
        $stat = @lstat($path);

        return is_array($stat) && ! is_link($path) && (($stat['mode'] ?? 0) & 0170000) === 0100000 && ($stat['nlink'] ?? 1) === 1;
    }
}
