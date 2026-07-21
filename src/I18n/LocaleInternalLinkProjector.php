<?php

declare(strict_types=1);

namespace Simai\Docara\I18n;

final readonly class LocaleInternalLinkProjector
{
    /** @param array<string, string> $routes */
    public function __construct(private array $routes) {}

    public function project(string $html): string
    {
        if ($this->routes === []) {
            return $html;
        }

        return preg_replace_callback(
            '/<a\b[^>]*\bhref=(?<quote>["\'])(?<href>.*?)\k<quote>/i',
            function (array $match): string {
                $href = html_entity_decode($match['href'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $suffixPosition = strcspn($href, '?#');
                $path = substr($href, 0, $suffixPosition);
                $suffix = substr($href, $suffixPosition);
                $target = $this->routes[$path] ?? null;
                if (! is_string($target)) {
                    return $match[0];
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
