<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

/**
 * A Markdown link that opens the search dialog: `[Open search](#docara-search)`.
 *
 * The address is a reserved fragment, so the source stays plain Markdown and
 * works in hero actions, CTAs and lists like any other link. The build turns
 * it into a link to the page locale's home page marked
 * `data-docara-search-open`, the marker `_docara/search.js` opens the dialog
 * for (the built-in 404 page uses the same link). Without JavaScript the
 * search dialog cannot work, so the link leads to the home page. On a page
 * without search the link is an ordinary link to the home page.
 */
final class SearchOpenLink
{
    public const HREF = '#docara-search';

    public const ATTRIBUTE = 'data-docara-search-open';

    public static function apply(string $html, string $homeUrl, bool $searchEnabled): string
    {
        if (! str_contains($html, self::HREF)) {
            return $html;
        }
        $home = htmlspecialchars($homeUrl, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');

        return preg_replace_callback(
            '/<a\b(?<before>[^>]*?)\shref=(?<quote>["\'])' . preg_quote(self::HREF, '/') . '\k<quote>(?<after>[^>]*)>/i',
            static function (array $match) use ($home, $searchEnabled): string {
                $attributes = $match['before'] . $match['after'];
                $marker = $searchEnabled && preg_match('/\s' . self::ATTRIBUTE . '(?=[\s=>]|$)/', $attributes) !== 1
                    ? ' ' . self::ATTRIBUTE
                    : '';

                return '<a' . $match['before'] . ' href="' . $home . '"' . $match['after'] . $marker . '>';
            },
            $html,
        ) ?? $html;
    }
}
