<?php

declare(strict_types=1);

namespace Simai\Docara\I18n;

final readonly class LocaleInternalLinkProjector
{
    /**
     * @param  array<string, string>  $routes
     * @param  string  $deploymentBase  the site's `base_url` as a path, `/` or `/docs/`
     */
    public function __construct(
        private array $routes,
        private string $deploymentBase = '/',
    ) {}

    /**
     * Authors write site routes from the site root (`/start/`), whatever
     * `base_url` the site is published under. A root-relative route that is
     * not already inside the deployment base gets the base in front, so the
     * same content builds for `/` and for a subdirectory such as a GitHub
     * Pages project site.
     */
    public static function rebase(string $href, string $deploymentBase): string
    {
        $base = '/' . trim($deploymentBase, '/');
        if ($base === '/'
            || ! str_starts_with($href, '/')
            || str_starts_with($href, '//')
            || $href === $base
            || str_starts_with($href, $base . '/')
            || str_starts_with($href, $base . '?')
            || str_starts_with($href, $base . '#')
        ) {
            return $href;
        }

        return $base . $href;
    }

    public function project(string $html): string
    {
        if ($this->routes === [] && trim($this->deploymentBase, '/') === '') {
            return $html;
        }

        return preg_replace_callback(
            // Links, and the frames of internal page previews.
            '/<(?:a\b[^>]*\bhref|iframe\b[^>]*\ssrc)=(?<quote>["\'])(?<href>.*?)\k<quote>/i',
            function (array $match): string {
                $original = html_entity_decode($match['href'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $href = self::rebase($original, $this->deploymentBase);
                $suffixPosition = strcspn($href, '?#');
                $path = substr($href, 0, $suffixPosition);
                $suffix = substr($href, $suffixPosition);
                $target = $this->routes[$path] ?? null;
                if (! is_string($target)) {
                    if ($href === $original) {
                        return $match[0];
                    }
                    $target = $path;
                }

                $escaped = htmlspecialchars($target . $suffix, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                return substr($match[0], 0, -strlen($match['href']) - 1)
                    . $escaped
                    . $match['quote'];
            },
            $html,
        ) ?? $html;
    }
}
