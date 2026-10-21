<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\I18n\LocaleDefinition;
use Simai\Docara\I18n\LocaleInternalLinkProjector;
use Simai\Docara\I18n\LocaleUrlProjector;
use Simai\Docara\I18n\Translator;
use Simai\Docara\I18n\UiCopy;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\ResolvedPagePlan;

/**
 * The page a static host serves for a missing address: `404.html` at the
 * deployment base, in the default locale, with the normal Docara shell.
 *
 * A site gets it unless docara.json sets `not_found.enabled: false`. The
 * content is Docara's own (the `error.not_found.*` strings, which the default
 * locale's lang.json may override) or the site's `404.md` at the root of the
 * default locale's content. A portal build has none: the portal owns
 * 404.html, and `not_found.enabled: true` there is an error.
 *
 * The host serves the page for any missing path, so everything it links to is
 * addressed from the site root: a relative URL in the content is resolved as
 * if the page were at its ordinary route (`<locale>/404/`). The page is
 * `noindex`, has no place in the navigation, and is left out of the search
 * index, sitemap.xml, llms.txt and page metadata.
 */
final readonly class NotFoundPage
{
    public const OUTPUT = '404.html';

    public const SOURCE = '404.md';

    /** Groups the page apart from any translation of an ordinary page. */
    public const TRANSLATION_KEY = '@docara/not-found';

    /** The trace source of Docara's own content. */
    public const GENERATED_SOURCE = '@docara/not-found';

    public const GENERATED_KIND = 'generated_not_found';

    public const MESSAGE_IDS = [
        'error.not_found.title',
        'error.not_found.message',
        'error.not_found.home',
        'error.not_found.search',
    ];

    private function __construct(
        public string $locale,
        public string $sourcePath,
        public string $url,
        public string $routeUrl,
        public string $homeUrl,
    ) {}

    /**
     * The not-found page of a site, or null when it has none.
     *
     * @param  array<string, mixed>  $site  docara.json
     */
    public static function fromSite(
        array $site,
        ?PortalProfile $portal,
        LocaleDefinition $defaultLocale,
        LocaleUrlProjector $urls,
    ): ?self {
        $setting = $site['not_found'] ?? null;
        $enabled = is_array($setting) ? ($setting['enabled'] ?? null) : null;
        if ($portal !== null) {
            if ($enabled === true) {
                throw new PortableConfigurationException(
                    'PORTAL_NOT_FOUND_PAGE_FORBIDDEN',
                    'A portal build has no 404.html: the portal owns it. Remove not_found.enabled from docara.json.',
                );
            }

            return null;
        }
        if ($enabled === false) {
            return null;
        }
        $locale = $defaultLocale->tag->value();

        return new self(
            $locale,
            rtrim($defaultLocale->contentRoot, '/') . '/' . self::SOURCE,
            $urls->deploymentBase() . self::OUTPUT,
            $urls->page($locale, '404')['url'],
            $urls->home($locale),
        );
    }

    /** @return array{url:string,output:string} */
    public function route(): array
    {
        return ['url' => $this->url, 'output' => self::OUTPUT];
    }

    /**
     * The page's strings: the default locale's lang.json, else Docara's own.
     *
     * @return array<string, string>
     */
    public function copy(Translator $translator): array
    {
        $copy = [];
        foreach (self::MESSAGE_IDS as $id) {
            $copy[$id] = $translator->messageOr($this->locale, $id, UiCopy::buildText($this->locale, $id));
        }

        return $copy;
    }

    /** @param array<string, string> $copy */
    public function markdown(array $copy): string
    {
        return '# ' . self::escapeMarkdown($copy['error.not_found.title']) . "\n\n"
            . self::escapeMarkdown($copy['error.not_found.message']) . "\n";
    }

    /**
     * @param  array<string, string>  $copy
     * @return array{title:string,description:string,noindex:true}
     */
    public function frontMatter(array $copy): array
    {
        return [
            'title' => $copy['error.not_found.title'],
            'description' => $copy['error.not_found.message'],
            'noindex' => true,
        ];
    }

    /**
     * The links under Docara's own text: the home page and, on a site with
     * search, a link that opens the search dialog (the home page without
     * JavaScript).
     *
     * @param  array<string, string>  $copy
     */
    public function linksHtml(array $copy, bool $searchEnabled): string
    {
        $home = self::escapeHtml($this->homeUrl);
        $html = '<ul class="docara-not-found-links" data-docara-not-found-links>'
            . '<li><a href="' . $home . '">' . self::escapeHtml($copy['error.not_found.home']) . '</a></li>';
        if ($searchEnabled) {
            $html .= '<li><a href="' . $home . '" data-docara-search-open>'
                . self::escapeHtml($copy['error.not_found.search']) . '</a></li>';
        }

        return $html . '</ul>';
    }

    /**
     * The page's plan: noindex, out of the navigation and the search index,
     * without breadcrumbs, page contents or previous/next links.
     */
    public function plan(ResolvedPagePlan $plan): ResolvedPagePlan
    {
        $configuration = $plan->configuration;
        $provenance = $plan->provenance;
        $configuration['search'] = [
            ...(is_array($configuration['search'] ?? null) ? $configuration['search'] : []),
            'indexed' => false,
        ];
        $configuration['navigation'] = [
            ...(is_array($configuration['navigation'] ?? null) ? $configuration['navigation'] : []),
            'hidden' => true,
        ];
        $configuration['reading'] = [
            ...(is_array($configuration['reading'] ?? null) ? $configuration['reading'] : []),
            'breadcrumbs' => false,
            'toc' => false,
            'previous_next' => false,
        ];
        foreach (['/search/indexed', '/navigation/hidden', '/reading/breadcrumbs', '/reading/toc', '/reading/previous_next'] as $pointer) {
            $provenance[$pointer] = self::GENERATED_SOURCE;
        }
        $provenance['/front_matter/noindex'] = self::GENERATED_SOURCE;
        ksort($provenance, SORT_STRING);

        return new ResolvedPagePlan(
            $plan->page,
            $plan->markdown,
            $configuration,
            $plan->frameworkLock,
            $plan->trace,
            $provenance,
            [...$plan->frontMatter, 'noindex' => true],
        );
    }

    /**
     * The content with every relative URL resolved from the page's ordinary
     * route, so the links and media work at whatever path the host serves
     * the page.
     */
    public function absolutize(string $html): string
    {
        return preg_replace_callback(
            '/<(?<element>a|area|iframe|img|source|video|audio|track|embed|object)\b(?:[^>"\']++|"[^"]*+"|\'[^\']*+\')*+>/i',
            function (array $tag): string {
                return preg_replace_callback(
                    '/(?<lead>\s(?<name>href|src|srcset|poster|data)=(?<quote>["\']))(?<value>.*?)\k<quote>/i',
                    function (array $attribute): string {
                        $original = html_entity_decode($attribute['value'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $resolved = strtolower($attribute['name']) === 'srcset'
                            ? $this->absolutizeSrcset($original)
                            : $this->resolve($original);
                        if ($resolved === $original) {
                            return $attribute[0];
                        }

                        return $attribute['lead']
                            . htmlspecialchars($resolved, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                            . $attribute['quote'];
                    },
                    $tag[0],
                ) ?? $tag[0];
            },
            $html,
        ) ?? $html;
    }

    /** A relative URL resolved from the page's ordinary route; others unchanged. */
    public function resolve(string $url): string
    {
        $trimmed = trim($url);
        if ($trimmed === ''
            || str_starts_with($trimmed, '/')
            || str_starts_with($trimmed, '#')
            || preg_match('/\A[A-Za-z][A-Za-z0-9+.-]*:/', $trimmed) === 1
        ) {
            return $url;
        }
        $suffixPosition = strcspn($trimmed, '?#');
        $path = substr($trimmed, 0, $suffixPosition);
        $suffix = substr($trimmed, $suffixPosition);
        $segments = array_values(array_filter(
            explode('/', trim($this->routeUrl, '/')),
            static fn (string $segment): bool => $segment !== '',
        ));
        $parts = explode('/', $path);
        $directory = false;
        foreach ($parts as $index => $part) {
            $last = $index === count($parts) - 1;
            if ($part === '..') {
                array_pop($segments);
                $directory = $last;
            } elseif ($part === '.' || $part === '') {
                $directory = $last;
            } else {
                $segments[] = $part;
                $directory = false;
            }
        }
        if ($path === '') {
            $directory = true;
        }
        $resolved = '/' . implode('/', $segments);

        return ($directory && $resolved !== '/' ? $resolved . '/' : $resolved) . $suffix;
    }

    private function absolutizeSrcset(string $srcset): string
    {
        $resolved = $srcset;
        foreach (array_reverse(LocaleInternalLinkProjector::srcsetUrls($srcset)) as [$url, $offset]) {
            $resolved = substr_replace($resolved, $this->resolve($url), $offset, strlen($url));
        }

        return $resolved;
    }

    private static function escapeMarkdown(string $text): string
    {
        $line = trim((string) preg_replace('/\s+/u', ' ', $text));

        return (string) preg_replace('/([!-\/:-@\[-`{-~])/', '\\\\$1', $line);
    }

    private static function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
