<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Portable\PortableConfigurationException;

/**
 * Which optional vendor files of the portable tree a built site needs.
 *
 * Most of `resources/portable/vendor` is needed by every site: the Framework
 * runtime loads its components and utilities on demand from what the reader's
 * page holds at run time, and the icon subset with its full Outlined fallback
 * serves icons whose names Smart components choose at run time. A few groups
 * are reached only from the page itself, so the finished HTML decides them:
 *
 * - `diagram`: mermaid, referenced only by a page that renders a diagram;
 * - `math`: KaTeX with its fonts, referenced only by a page that renders math;
 * - `icons_rounded` / `icons_sharp`: the Rounded and Sharp Material Symbols
 *   faces, variable fonts and catalogue instances alike, used only by an icon
 *   that asks for that family in markup, an example's own script or the
 *   site's own stylesheets and scripts. Framework
 *   Smart components choose a family only from an `sf-icon` `type` attribute,
 *   which is markup;
 * - `utility_full`: the Framework's all-in-one utility stylesheet, which the
 *   exact production plan never links; only a page that links it gets it.
 *
 * Each group carries the licence file that travels with it. The selection is
 * a pure function of the built files, so the static verifier recomputes it.
 */
final readonly class PortableVendorAssetSelection
{
    public const FEATURES = ['diagram', 'math', 'icons_rounded', 'icons_sharp', 'utility_full'];

    private const ROUNDED_MARKERS = [
        'sf-icon-rounded',
        'sf-icon--rounded',
        'Material Symbols Rounded',
        'SF_ICON_TYPE',
    ];

    private const SHARP_MARKERS = [
        'sf-icon-shape',
        'sf-icon--sharp',
        'Material Symbols Sharp',
        'SF_ICON_TYPE',
    ];

    /**
     * @param  array<string, bool>  $features
     * @param  list<string>|null  $utilityRevisions  runtime revisions whose full utility sheet a page links; null means every revision
     */
    private function __construct(
        private array $features,
        private ?array $utilityRevisions,
    ) {}

    /** Everything, as before: for callers that publish without a built site. */
    public static function everything(): self
    {
        return new self(array_fill_keys(self::FEATURES, true), null);
    }

    /**
     * @param  iterable<string>  $htmlDocuments  final page HTML
     * @param  iterable<string>  $siteAssets  the site's own stylesheets and scripts
     */
    public static function fromDocuments(iterable $htmlDocuments, iterable $siteAssets = []): self
    {
        $features = array_fill_keys(self::FEATURES, false);
        $utilityRevisions = [];
        foreach ($htmlDocuments as $html) {
            if (! is_string($html)) {
                throw new PortableConfigurationException(
                    'PORTABLE_VENDOR_SELECTION_INPUT_INVALID',
                    'A built page could not be read for vendor asset selection.',
                );
            }
            if (str_contains($html, 'vendor/mermaid/mermaid.min.js')) {
                $features['diagram'] = true;
            }
            if (str_contains($html, 'vendor/katex/katex.min.')) {
                $features['math'] = true;
            }
            if (preg_match_all(
                '#simai-framework/runtime/([a-f0-9]{40})/distr/core/css/utility\.full\.css#',
                $html,
                $utilityMatches,
            ) > 0) {
                $features['utility_full'] = true;
                array_push($utilityRevisions, ...$utilityMatches[1]);
            }
            foreach (self::authoredParts($html) as $part) {
                self::markIconFamilies($part, $features);
            }
        }
        foreach ($siteAssets as $asset) {
            if (is_string($asset)) {
                self::markIconFamilies($asset, $features);
            }
        }
        $utilityRevisions = array_values(array_unique($utilityRevisions));
        sort($utilityRevisions, SORT_STRING);

        return new self($features, $utilityRevisions);
    }

    /**
     * Read every built page, and the site's own stylesheets and scripts, under
     * a build directory. Docara's `_docara/` assets and dot directories are not
     * the site's own.
     */
    public static function fromBuild(string $root): self
    {
        $root = rtrim($root, '/\\');
        $html = [];
        $assets = [];
        if (is_dir($root) && ! is_link($root)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveCallbackFilterIterator(
                    new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                    static function (\SplFileInfo $file) use ($root): bool {
                        if ($file->isLink()) {
                            return false;
                        }
                        if (! $file->isDir()) {
                            return true;
                        }
                        $name = $file->getFilename();

                        return ! str_starts_with($name, '.')
                            && ! ($name === '_docara' && dirname($file->getPathname()) === $root);
                    },
                ),
            );
            foreach ($iterator as $file) {
                if (! $file instanceof \SplFileInfo || ! $file->isFile()) {
                    continue;
                }
                $extension = strtolower($file->getExtension());
                if ($extension === 'html') {
                    $html[$file->getPathname()] = $file->getPathname();
                } elseif (in_array($extension, ['css', 'js', 'mjs'], true)) {
                    $assets[$file->getPathname()] = $file->getPathname();
                }
            }
        }
        ksort($html, SORT_STRING);
        ksort($assets, SORT_STRING);

        return self::fromDocuments(
            (static function () use ($html): \Generator {
                foreach ($html as $path) {
                    yield file_get_contents($path);
                }
            })(),
            (static function () use ($assets): \Generator {
                foreach ($assets as $path) {
                    yield (string) file_get_contents($path);
                }
            })(),
        );
    }

    public function has(string $feature): bool
    {
        return $this->features[$feature] ?? false;
    }

    /** Which optional group a path under the portable tree belongs to, if any. */
    public static function featureOf(string $relative): ?string
    {
        return match (true) {
            str_starts_with($relative, 'vendor/mermaid/'),
            $relative === 'vendor/licenses/mermaid-LICENSE' => 'diagram',
            str_starts_with($relative, 'vendor/katex/'),
            $relative === 'vendor/licenses/katex-LICENSE' => 'math',
            preg_match('#^vendor/google/material-symbols/[a-f0-9]{40}/MaterialSymbolsRounded\.woff2$#D', $relative) === 1,
            preg_match('#^vendor/docara/icon-catalog/[a-f0-9]{40}/material-symbols-rounded\.[a-f0-9]{20}\.(?:woff2|css|manifest\.json)$#D', $relative) === 1 => 'icons_rounded',
            preg_match('#^vendor/google/material-symbols/[a-f0-9]{40}/MaterialSymbolsSharp\.woff2$#D', $relative) === 1,
            preg_match('#^vendor/docara/icon-catalog/[a-f0-9]{40}/material-symbols-sharp\.[a-f0-9]{20}\.(?:woff2|css|manifest\.json)$#D', $relative) === 1 => 'icons_sharp',
            preg_match('#^vendor/simai-framework/runtime/[a-f0-9]{40}/distr/core/css/utility\.full\.css$#D', $relative) === 1 => 'utility_full',
            default => null,
        };
    }

    /** Whether a path under the portable tree is published for this site. */
    public function publishes(string $relative): bool
    {
        $feature = self::featureOf($relative);
        if ($feature === null) {
            return true;
        }
        if (! $this->has($feature)) {
            return false;
        }
        if ($feature === 'utility_full' && $this->utilityRevisions !== null) {
            return preg_match('#^vendor/simai-framework/runtime/([a-f0-9]{40})/#', $relative, $match) === 1
                && in_array($match[1], $this->utilityRevisions, true);
        }

        return true;
    }

    /** @return array{features: array<string, bool>, utility_revisions: list<string>|null} */
    public function toArray(): array
    {
        return ['features' => $this->features, 'utility_revisions' => $this->utilityRevisions];
    }

    /**
     * The parts of a page an author wrote: the page with its example frames'
     * documents unpacked, without the styles and scripts Docara itself
     * inlines (they name every icon family whether a page uses it or not).
     *
     * @return list<string>
     */
    private static function authoredParts(string $html): array
    {
        $parts = [];
        $frames = [];
        if (preg_match_all(
            '~\b(?:data-docara-)?srcdoc\s*=\s*(?:"([^"]*)"|\'([^\']*)\')~is',
            $html,
            $matches,
            PREG_SET_ORDER,
        ) === false) {
            throw new PortableConfigurationException(
                'PORTABLE_VENDOR_SELECTION_INPUT_INVALID',
                'A built page could not be scanned for vendor asset selection.',
            );
        }
        foreach ($matches as $match) {
            $frames[] = html_entity_decode(
                ($match[1] ?? '') !== '' ? $match[1] : ($match[2] ?? ''),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8',
            );
        }
        $page = preg_replace('~\b(?:data-docara-)?srcdoc\s*=\s*(?:"[^"]*"|\'[^\']*\')~is', '', $html);
        foreach ([$page, ...$frames] as $document) {
            if (! is_string($document)) {
                throw new PortableConfigurationException(
                    'PORTABLE_VENDOR_SELECTION_INPUT_INVALID',
                    'A built page could not be scanned for vendor asset selection.',
                );
            }
            $stripped = preg_replace(
                '~<(style|script)\b(?=[^>]*\bdata-docara-)[^>]*>.*?</\1\s*>~is',
                '',
                $document,
            );
            if (! is_string($stripped)) {
                throw new PortableConfigurationException(
                    'PORTABLE_VENDOR_SELECTION_INPUT_INVALID',
                    'A built page could not be scanned for vendor asset selection.',
                );
            }
            $parts[] = $stripped;
        }

        return $parts;
    }

    /** @param array<string, bool> $features */
    private static function markIconFamilies(string $text, array &$features): void
    {
        foreach (self::ROUNDED_MARKERS as $marker) {
            if (str_contains($text, $marker)) {
                $features['icons_rounded'] = true;
                break;
            }
        }
        foreach (self::SHARP_MARKERS as $marker) {
            if (str_contains($text, $marker)) {
                $features['icons_sharp'] = true;
                break;
            }
        }
        // A Smart icon picks its family from its `type` attribute.
        if (preg_match_all(
            '~<sf-icon\b[^>]*\btype\s*=\s*(?:"|\'|&quot;|&#039;|&apos;)?\s*(rounded|sharp|shape)\b~i',
            $text,
            $matches,
        ) > 0) {
            foreach ($matches[1] as $family) {
                $features[strtolower($family) === 'rounded' ? 'icons_rounded' : 'icons_sharp'] = true;
            }
        }
    }
}
