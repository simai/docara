<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

/**
 * A framed example has <base href> pointing at its published folder, so
 * "assets/photo.svg" in its markup loads the example's own file. An inline
 * example is part of the page and has no base of its own: the same path
 * would resolve against the page address and miss. Before the markup joins
 * the page, every reference to one of the example's own assets in an
 * attribute value is rewritten to the published folder.
 *
 * Only the example's known asset paths are rewritten, and only inside tag
 * attribute values: a plain URL attribute (image-url="assets/a.svg"), a
 * srcset list, url() in a style, or a path inside a JSON attribute
 * (value='{"href":"assets/a.svg"}'). Text content, other relative URLs and
 * paths that merely start with an asset's name stay as written.
 */
final class InlineExampleAssetRebaser
{
    private const TAG = '/<[A-Za-z][A-Za-z0-9-]*(?:\s+[^\s"\'>\/=]+(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'=<>`]+))?)*\s*\/?>/';

    private const ATTRIBUTE = '/(?<name>\s[^\s"\'>\/=]+\s*=\s*)(?<value>"[^"]*"|\'[^\']*\'|[^\s"\'=<>`]+)/';

    /**
     * @param  list<string>  $assets  Asset paths relative to the example
     *                                folder, such as "assets/photo.svg".
     * @param  string  $baseHref  The example's published folder, ending in "/".
     */
    public function rebase(string $html, array $assets, string $baseHref): string
    {
        $pattern = $this->assetPattern($assets);
        if ($pattern === null || $baseHref === '') {
            return $html;
        }
        $prefix = rtrim($baseHref, '/') . '/';

        return preg_replace_callback(
            self::TAG,
            fn (array $tag): string => preg_replace_callback(
                self::ATTRIBUTE,
                fn (array $attribute): string => $attribute['name'] . (preg_replace_callback(
                    $pattern,
                    // The value is raw markup, so the prefix is HTML-escaped
                    // and the matched path is kept as written.
                    static fn (array $asset): string => htmlspecialchars($prefix, ENT_QUOTES | ENT_HTML5, 'UTF-8') . $asset['asset'],
                    $attribute['value'],
                ) ?? $attribute['value']),
                $tag[0],
            ) ?? $tag[0],
            $html,
        ) ?? $html;
    }

    /** @param list<string> $assets */
    private function assetPattern(array $assets): ?string
    {
        $alternatives = [];
        foreach ($assets as $asset) {
            $asset = ltrim(str_replace('\\', '/', $asset), '/');
            if ($asset === '') {
                continue;
            }
            // Markup holds the path as written or HTML-escaped, and a JSON
            // attribute may escape its slashes.
            foreach ([$asset, htmlspecialchars($asset, ENT_QUOTES | ENT_HTML5, 'UTF-8')] as $form) {
                $alternatives[preg_quote($form, '/')] = true;
                $alternatives[preg_quote(str_replace('/', '\\/', $form), '/')] = true;
            }
        }
        if ($alternatives === []) {
            return null;
        }
        $keys = array_keys($alternatives);
        // The longest path first, so assets/a.svg never wins over assets/a.svg.gz.
        usort($keys, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));

        // A reference starts the value or follows a delimiter (quote, space,
        // comma, parenthesis, equals sign); "./" in front is the same path.
        // It ends where the path ends: query, fragment, quote, space, comma
        // or parenthesis, never inside a longer file name.
        return '/(?<![A-Za-z0-9_.~%\/\\\\-])(?:\.\/|\.\\\\\/)?(?<asset>' . implode('|', $keys) . ')(?![A-Za-z0-9_.~%\/-])/';
    }
}
