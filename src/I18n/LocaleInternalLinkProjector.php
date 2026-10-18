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

    /**
     * The URLs of a `srcset` value, each with its byte offset, read the way
     * the HTML image candidate parser reads them: a URL is a run of
     * non-whitespace, trailing commas end it, and descriptors run to the next
     * comma outside parentheses.
     *
     * @return list<array{0: string, 1: int}>
     */
    public static function srcsetUrls(string $srcset): array
    {
        $urls = [];
        $length = strlen($srcset);
        $position = 0;
        while ($position < $length) {
            $position += strspn($srcset, " \t\n\r\f,", $position);
            if ($position >= $length) {
                break;
            }
            $start = $position;
            $position += strcspn($srcset, " \t\n\r\f", $position);
            $url = rtrim(substr($srcset, $start, $position - $start), ',');
            $endedByComma = strlen($url) < $position - $start;
            if ($url !== '') {
                $urls[] = [$url, $start];
            }
            if ($endedByComma) {
                continue;
            }
            $depth = 0;
            while ($position < $length) {
                $character = $srcset[$position];
                if ($character === '(') {
                    $depth++;
                } elseif ($character === ')' && $depth > 0) {
                    $depth--;
                } elseif ($character === ',' && $depth === 0) {
                    break;
                }
                $position++;
            }
        }

        return $urls;
    }

    public function project(string $html): string
    {
        if ($this->routes === [] && trim($this->deploymentBase, '/') === '') {
            return $html;
        }
        if (trim($this->deploymentBase, '/') !== '') {
            $html = $this->rebaseMedia($html);
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

    /**
     * Media that authors address from the site root (`![](/ru/x.png)`, a raw
     * `<video poster>`, a `<picture>` source) take the deployment base the
     * way links do. Media carry no locale route, so only the base changes.
     */
    private function rebaseMedia(string $html): string
    {
        return preg_replace_callback(
            '/<(?<element>img|source|video|audio|track|link)\b(?:[^>"\']++|"[^"]*+"|\'[^\']*+\')*+>/i',
            function (array $tag): string {
                $element = strtolower($tag['element']);
                $attributes = match ($element) {
                    'img', 'source' => 'src|srcset',
                    'video' => 'src|poster',
                    'link' => 'href|imagesrcset',
                    default => 'src',
                };
                if ($element === 'link'
                    && preg_match('/\srel=(["\'])[^"\']*\b(?:icon|manifest|apple-touch-icon|apple-touch-icon-precomposed|mask-icon)\b[^"\']*\1/i', $tag[0]) !== 1
                ) {
                    return $tag[0];
                }

                return preg_replace_callback(
                    '/(?<lead>\s(?<name>' . $attributes . ')=(?<quote>["\']))(?<value>.*?)\k<quote>/i',
                    function (array $attribute): string {
                        $original = html_entity_decode($attribute['value'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $rebased = str_ends_with(strtolower($attribute['name']), 'srcset')
                            ? $this->rebaseSrcset($original)
                            : self::rebase($original, $this->deploymentBase);
                        if ($rebased === $original) {
                            return $attribute[0];
                        }

                        return $attribute['lead']
                            . htmlspecialchars($rebased, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                            . $attribute['quote'];
                    },
                    $tag[0],
                ) ?? $tag[0];
            },
            $html,
        ) ?? $html;
    }

    private function rebaseSrcset(string $srcset): string
    {
        $rebased = $srcset;
        foreach (array_reverse(self::srcsetUrls($srcset)) as [$url, $offset]) {
            $rebased = substr_replace($rebased, self::rebase($url, $this->deploymentBase), $offset, strlen($url));
        }

        return $rebased;
    }
}
