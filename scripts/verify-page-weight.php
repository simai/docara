#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Page-weight guard for a built Docara site.
 *
 * Usage:
 *   php scripts/verify-page-weight.php <build-dir> [--config=<file.json>]
 *       [--base-url=/] [--max-page-bytes=N] [--max-image-bytes=N]
 *       [--image-oversize-ratio=R] [--top=N] [--json]
 *
 * For every HTML page of the build it adds up, by static analysis and
 * de-duplicated, the bytes the page makes a browser download: the HTML, its
 * stylesheets (with @import), scripts, preloads, icons, images (the largest
 * srcset candidate), and the fonts and images its CSS points at. Example
 * frames (srcdoc) and same-site iframes count with their own resources.
 *
 * CSS is read the way a browser fetches it, approximately: an @font-face
 * font counts only when a rule whose selector classes all occur on the page
 * names its family (and, with unicode-range, when the page has a character
 * in that range); a url() in a rule counts only when its selector classes
 * occur on the page. Element, id and attribute parts of a selector, :not(),
 * :is() and :where() are ignored, so the estimate leans towards counting.
 * Resources a script loads at run time are not seen.
 *
 * The check fails when
 * - a page weighs more than max_page_bytes (default 3 MB, per page override
 *   in "pages");
 * - an image file a page uses is larger than max_image_bytes (default 1 MB);
 * - a page that has no icon outside the shell icon subset and no variant
 *   icon (rounded, sharp, filled, light, medium, full-font) makes the browser
 *   fetch a full Material Symbols font;
 * - an <img> with width or height attributes has a bitmap more than
 *   image_oversize_ratio (default 2.5) times that size in either dimension.
 *
 * Budgets come from defaults, then the --config JSON file, then the command
 * line. Config keys: max_page_bytes, max_image_bytes, image_oversize_ratio,
 * base_url, full_icon_font_pattern (a PCRE matched against the resource
 * path), variant_icon_classes (list), and pages: {"<page path or URL>":
 * {"max_page_bytes": N, "reason": "..."}}.
 */
$defaults = [
    'max_page_bytes' => 3_000_000,
    'max_image_bytes' => 1_000_000,
    'image_oversize_ratio' => 2.5,
    'base_url' => '/',
    'full_icon_font_pattern' => '~/MaterialSymbols(?:Outlined|Rounded|Sharp)\.(?:woff2|woff|ttf|otf)$~',
    'variant_icon_classes' => [
        'sf-icon-rounded', 'sf-icon-shape', 'sf-icon-sharp', 'sf-icon-filled',
        'sf-icon-light', 'sf-icon-medium', 'sf-icon-full-font',
    ],
    'pages' => [],
];

$arguments = array_slice($argv, 1);
$root = null;
$options = [];
foreach ($arguments as $argument) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $argument, $match) === 1) {
        $options[$match[1]] = $match[2] ?? true;
    } elseif ($root === null) {
        $root = $argument;
    } else {
        fwrite(STDERR, "Unexpected argument [$argument].\n");
        exit(2);
    }
}
if (! is_string($root) || ! is_dir($root)) {
    fwrite(STDERR, "Usage: php scripts/verify-page-weight.php <build-dir> [--config=<file.json>] [--base-url=/] [--max-page-bytes=N] [--max-image-bytes=N] [--image-oversize-ratio=R] [--top=N] [--json]\n");
    exit(2);
}
$root = (string) realpath($root);

$config = $defaults;
if (isset($options['config'])) {
    $file = (string) $options['config'];
    $decoded = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
    if (! is_array($decoded)) {
        fwrite(STDERR, "The config file [$file] is missing or is not a JSON object.\n");
        exit(2);
    }
    foreach ($decoded as $key => $value) {
        if ($key === '$comment') {
            continue;
        }
        if (! array_key_exists($key, $defaults)) {
            fwrite(STDERR, "Unknown config key [$key] in [$file].\n");
            exit(2);
        }
        $config[$key] = $value;
    }
}
foreach (['max-page-bytes' => 'max_page_bytes', 'max-image-bytes' => 'max_image_bytes'] as $flag => $key) {
    if (isset($options[$flag])) {
        $config[$key] = (int) $options[$flag];
    }
}
if (isset($options['image-oversize-ratio'])) {
    $config['image_oversize_ratio'] = (float) $options['image-oversize-ratio'];
}
if (isset($options['base-url'])) {
    $config['base_url'] = (string) $options['base-url'];
}
$top = isset($options['top']) ? max(0, (int) $options['top']) : 10;

$weigher = new DocaraPageWeigher(
    $root,
    (string) $config['base_url'],
    (string) $config['full_icon_font_pattern'],
    array_values((array) $config['variant_icon_classes']),
    (float) $config['image_oversize_ratio'],
);
$pageBudgets = [];
foreach ((array) $config['pages'] as $page => $budget) {
    $pageBudgets[$weigher->pageKey((string) $page)] = (int) ($budget['max_page_bytes'] ?? $config['max_page_bytes']);
}

$results = [];
$failures = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$pages = [];
foreach ($iterator as $file) {
    $relative = substr($file->getPathname(), strlen($root) + 1);
    if ($file->isFile() && str_ends_with($relative, '.html') && ! str_starts_with($relative, '_docara/')) {
        $pages[] = str_replace('\\', '/', $relative);
    }
}
sort($pages, SORT_STRING);

$redirects = 0;
foreach ($pages as $page) {
    $head = (string) file_get_contents($root . '/' . $page, length: 4096);
    if (preg_match('~<meta\s+http-equiv=["\']?refresh~i', $head) === 1 && ! str_contains($head, 'stylesheet')) {
        $redirects++;

        continue;
    }
    $result = $weigher->weigh($page);
    $budget = $pageBudgets[$page] ?? (int) $config['max_page_bytes'];
    $result['budget'] = $budget;
    $results[] = $result;
    if ($result['total_bytes'] > $budget) {
        $failures[] = sprintf('%s weighs %s, over its %s budget. Largest: %s', $page, docaraBytes($result['total_bytes']), docaraBytes($budget), implode(', ', array_map(
            static fn (array $resource): string => $resource['path'] . ' ' . docaraBytes($resource['bytes']),
            array_slice($result['resources'], 0, 3),
        )));
    }
    foreach ($result['resources'] as $resource) {
        if ($resource['kind'] === 'image' && $resource['bytes'] > (int) $config['max_image_bytes']) {
            $failures[] = sprintf('%s uses the image %s of %s, over the %s image budget.', $page, $resource['path'], docaraBytes($resource['bytes']), docaraBytes((int) $config['max_image_bytes']));
        }
    }
    if ($result['full_icon_fonts'] !== [] && ! $result['needs_full_icon_font']) {
        $failures[] = sprintf('%s has only shell-subset icons but makes the browser fetch the full Material Symbols font %s.', $page, implode(', ', $result['full_icon_fonts']));
    }
    foreach ($result['oversized_images'] as $image) {
        $failures[] = sprintf('%s shows %s (%dx%d) at %s; the bitmap is more than %sx that size.', $page, $image['path'], $image['natural'][0], $image['natural'][1], $image['declared'], (string) $config['image_oversize_ratio']);
    }
}
$weigher->assertNoMissing();

usort($results, static fn (array $left, array $right): int => $right['total_bytes'] <=> $left['total_bytes'] ?: strcmp($left['page'], $right['page']));
$totals = array_column($results, 'total_bytes');
sort($totals);
$median = $totals === [] ? 0 : $totals[intdiv(count($totals), 2)];

if (isset($options['json'])) {
    echo json_encode([
        'pages' => count($results),
        'redirect_pages_skipped' => $redirects,
        'median_bytes' => $median,
        'max_bytes' => $totals === [] ? 0 : max($totals),
        'budget' => $config,
        'heaviest' => array_map(static fn (array $result): array => [
            'page' => $result['page'],
            'total_bytes' => $result['total_bytes'],
            'budget' => $result['budget'],
            'largest' => array_slice($result['resources'], 0, 5),
        ], array_slice($results, 0, $top)),
        'failures' => $failures,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    printf("Page weight: %d page(s) (%d redirect page(s) skipped), median %s, heaviest %s (budget %s per page, %s per image).\n", count($results), $redirects, docaraBytes($median), docaraBytes($totals === [] ? 0 : max($totals)), docaraBytes((int) $config['max_page_bytes']), docaraBytes((int) $config['max_image_bytes']));
    foreach (array_slice($results, 0, $top) as $result) {
        printf("  %10s / %-10s %s\n", docaraBytes($result['total_bytes']), docaraBytes($result['budget']), $result['page']);
    }
    foreach ($failures as $failure) {
        fwrite(STDERR, 'FAIL ' . $failure . "\n");
    }
}

exit($failures === [] ? 0 : 1);

function docaraBytes(int $bytes): string
{
    return $bytes >= 1_000_000 ? sprintf('%.2f MB', $bytes / 1_000_000) : sprintf('%.1f KB', $bytes / 1000);
}

final class DocaraPageWeigher
{
    /** @var array<string, int> */
    private array $sizes = [];

    /** @var array<string, array<string, mixed>> */
    private array $stylesheets = [];

    /** @var array<string, string> */
    private array $missing = [];

    /** @var list<string> */
    private array $subsetIcons = [];

    private string $base;

    /** @param list<string> $variantClasses */
    public function __construct(
        private readonly string $root,
        string $baseUrl,
        private readonly string $fullIconFontPattern,
        private readonly array $variantClasses,
        private readonly float $oversizeRatio,
    ) {
        $base = trim($baseUrl, '/');
        $this->base = $base === '' ? '/' : '/' . $base . '/';
        foreach (glob($root . '/_docara/vendor/docara/icon-subset/*/*.manifest.json') ?: [] as $manifest) {
            $decoded = json_decode((string) file_get_contents($manifest), true);
            foreach ((array) ($decoded['icons'] ?? []) as $icon) {
                $this->subsetIcons[] = (string) $icon;
            }
        }
    }

    /** A page path relative to the build ("ru/start/index.html") from a path or a URL. */
    public function pageKey(string $page): string
    {
        if (str_ends_with($page, '.html')) {
            return ltrim($page, '/');
        }
        $path = (string) $this->localPath(rtrim($page, '/') . '/', '');

        return str_ends_with($path, '.html') ? $path : 'index.html';
    }

    /** @return array<string, mixed> */
    public function weigh(string $page): array
    {
        $resources = [];
        $state = ['icons' => [], 'needs_full' => false, 'oversized' => [], 'documents' => [$page => true]];
        $html = (string) file_get_contents($this->root . '/' . $page);
        $this->document($html, $page, $resources, $state);
        uasort($resources, static fn (array $left, array $right): int => $right['bytes'] <=> $left['bytes']);
        $fullFonts = [];
        foreach ($resources as $path => $resource) {
            if ($resource['kind'] === 'font' && preg_match($this->fullIconFontPattern, '/' . $path) === 1) {
                $fullFonts[] = $path;
            }
        }

        return [
            'page' => $page,
            'html_bytes' => strlen($html),
            'total_bytes' => strlen($html) + array_sum(array_column($resources, 'bytes')),
            'resources' => array_values($resources),
            'full_icon_fonts' => $fullFonts,
            'needs_full_icon_font' => $state['needs_full'],
            'oversized_images' => $state['oversized'],
        ];
    }

    public function assertNoMissing(): void
    {
        foreach (array_slice($this->missing, 0, 5, true) as $url => $from) {
            fwrite(STDERR, "note: [$url] referenced from [$from] is not in the build and is not counted.\n");
        }
    }

    /**
     * @param  array<string, array{path:string,bytes:int,kind:string}>  $resources
     * @param  array<string, mixed>  $state
     */
    private function document(string $html, string $page, array &$resources, array &$state): void
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($document);

        $classes = [];
        foreach ($xpath->query('//*[@class]') ?: [] as $element) {
            foreach (preg_split('/\s+/', trim($element->getAttribute('class'))) ?: [] as $class) {
                $classes[$class] = true;
            }
        }
        $text = (string) ($xpath->query('//body')->item(0)?->textContent ?? $document->textContent);
        $codepoints = [];
        foreach (array_unique(mb_str_split($text) ?: []) as $character) {
            $codepoints[] = mb_ord($character);
        }

        // Icons: a name outside the shell subset or a variant class needs a full font.
        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " sf-icon ")]') ?: [] as $icon) {
            $iconClasses = preg_split('/\s+/', trim($icon->getAttribute('class'))) ?: [];
            $name = trim($icon->textContent);
            if (array_intersect($iconClasses, $this->variantClasses) !== []
                || ($name !== '' && $this->subsetIcons !== [] && ! in_array($name, $this->subsetIcons, true))
            ) {
                $state['needs_full'] = true;
            }
        }

        $sheets = [];
        foreach ($xpath->query('//link[@href]') ?: [] as $link) {
            $rel = ' ' . strtolower(trim($link->getAttribute('rel'))) . ' ';
            $href = $link->getAttribute('href');
            if (str_contains($rel, ' stylesheet ')) {
                $sheets[] = $this->stylesheet($href, $page);
            } elseif (str_contains($rel, ' preload ') || str_contains($rel, ' modulepreload ')) {
                $as = strtolower($link->getAttribute('as'));
                $this->add($resources, $href, $page, $as === 'font' ? 'font' : ($as === 'image' ? 'image' : ($as === 'style' ? 'css' : 'script')));
            } elseif (str_contains($rel, ' icon ') || str_contains($rel, ' apple-touch-icon ')) {
                $this->add($resources, $href, $page, 'image');
            }
        }
        foreach ($xpath->query('//script[@src]') ?: [] as $script) {
            $this->add($resources, $script->getAttribute('src'), $page, 'script');
        }
        foreach ($xpath->query('//style') ?: [] as $style) {
            $sheets[] = $this->parsedCss((string) $style->textContent, $page);
        }
        foreach ($xpath->query('//*[@style]') ?: [] as $element) {
            foreach ($this->cssUrls($element->getAttribute('style')) as $url) {
                $this->add($resources, $url, $page, 'image');
            }
        }
        foreach ($xpath->query('//img | //picture/source | //video[@poster]') ?: [] as $image) {
            $candidates = [];
            foreach (['src', 'poster'] as $attribute) {
                if ($image->hasAttribute($attribute)) {
                    $candidates[] = $image->getAttribute($attribute);
                }
            }
            foreach (explode(',', $image->getAttribute('srcset')) as $candidate) {
                $candidate = trim(preg_split('/\s+/', trim($candidate))[0] ?? '');
                if ($candidate !== '') {
                    $candidates[] = $candidate;
                }
            }
            $best = null;
            foreach ($candidates as $candidate) {
                $path = $this->localPath($candidate, $page);
                if ($path !== null && $this->size($path, $candidate, $page) !== null
                    && ($best === null || $this->sizes[$path] > $this->sizes[$best])
                ) {
                    $best = $path;
                }
            }
            if ($best === null) {
                continue;
            }
            $this->addPath($resources, $best, 'image');
            $this->checkDisplaySize($image, $best, $state);
        }
        foreach ($xpath->query('//iframe') ?: [] as $frame) {
            foreach (['srcdoc', 'data-docara-srcdoc'] as $attribute) {
                if ($frame->hasAttribute($attribute)) {
                    $this->document($frame->getAttribute($attribute), $page, $resources, $state);
                }
            }
            if ($frame->hasAttribute('src')) {
                $path = $this->localPath($frame->getAttribute('src'), $page);
                if ($path !== null && ! str_ends_with($path, '.html')) {
                    $path = rtrim($path, '/') . '/index.html';
                }
                if ($path !== null && ! isset($state['documents'][$path]) && $this->size($path, $frame->getAttribute('src'), $page) !== null) {
                    $state['documents'][$path] = true;
                    $this->addPath($resources, $path, 'html');
                    $this->document((string) file_get_contents($this->root . '/' . $path), $path, $resources, $state);
                }
            }
        }

        // Stylesheets: the sheet itself, then fonts and images whose rules apply.
        $faces = [];
        $families = [];
        $rules = [];
        $pending = $sheets;
        $seen = [];
        while ($pending !== []) {
            $sheet = array_shift($pending);
            if ($sheet === null || isset($seen[$sheet['id']])) {
                continue;
            }
            $seen[$sheet['id']] = true;
            if ($sheet['path'] !== null) {
                $this->addPath($resources, $sheet['path'], 'css');
            }
            foreach ($sheet['imports'] as $import) {
                $pending[] = $this->stylesheet($import, $sheet['context']);
            }
            foreach ($sheet['faces'] as $face) {
                $faces[] = $face + ['context' => $sheet['context']];
            }
            foreach ($sheet['rules'] as $rule) {
                $rules[] = $rule + ['context' => $sheet['context']];
            }
        }
        // A custom property names a font only through font-family: var(--x),
        // and only its document-wide value (a rule without classes) is read:
        // a class-scoped value is usually overridden for that class.
        $variables = [];
        foreach ($rules as $rule) {
            if (in_array([], $rule['selectors'], true)) {
                foreach ($rule['variables'] as $name => $names) {
                    $variables[$name] = array_merge($variables[$name] ?? [], $names);
                }
            }
        }
        foreach ($rules as $rule) {
            if (! $this->applies($rule['selectors'], $classes)) {
                continue;
            }
            foreach ($rule['families'] as $family) {
                $families[$family] = true;
            }
            foreach ($rule['uses'] as $variable) {
                foreach ($variables[$variable] ?? [] as $family) {
                    $families[$family] = true;
                }
            }
            foreach ($rule['urls'] as $url) {
                $this->add($resources, $url, $rule['context'], 'image');
            }
        }
        // A family's faces differ by weight: the browser fetches the face of
        // the weight it draws. Weights picked at run time are not visible, so
        // only the face nearest to the regular weight (400) counts.
        $nearest = [];
        foreach ($faces as $face) {
            [$from, $to] = $face['weight'];
            $distance = $from > 400 ? $from - 400 : ($to < 400 ? 400 - $to : 0);
            $nearest[$face['family']] = min($nearest[$face['family']] ?? PHP_INT_MAX, $distance);
        }
        foreach ($faces as $face) {
            [$from, $to] = $face['weight'];
            $distance = $from > 400 ? $from - 400 : ($to < 400 ? 400 - $to : 0);
            if (! isset($families[$face['family']]) || $face['url'] === null || $distance !== $nearest[$face['family']]
                || ! $this->inRange($face['ranges'], $codepoints)
            ) {
                continue;
            }
            $this->add($resources, $face['url'], $face['context'], 'font');
        }
    }

    /** @param array<string, mixed> $state */
    private function checkDisplaySize(DOMElement $image, string $path, array &$state): void
    {
        $width = (int) $image->getAttribute('width');
        $height = (int) $image->getAttribute('height');
        if (($width <= 0 && $height <= 0) || str_ends_with(strtolower($path), '.svg')) {
            return;
        }
        $natural = @getimagesize($this->root . '/' . $path);
        if (! is_array($natural) || $natural[0] <= 0) {
            return;
        }
        $ratio = $this->oversizeRatio;
        if (($width > 0 && $natural[0] > $ratio * $width) || ($height > 0 && $natural[1] > $ratio * $height)) {
            $state['oversized'][] = ['path' => $path, 'natural' => [$natural[0], $natural[1]], 'declared' => $width . 'x' . $height];
        }
    }

    /**
     * @param  list<list<string>>  $selectors  each selector's required classes
     * @param  array<string, true>  $classes
     */
    private function applies(array $selectors, array $classes): bool
    {
        foreach ($selectors as $required) {
            $applies = true;
            foreach ($required as $class) {
                if (! isset($classes[$class])) {
                    $applies = false;

                    break;
                }
            }
            if ($applies) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array{0:int,1:int}>  $ranges
     * @param  list<int>  $codepoints
     */
    private function inRange(array $ranges, array $codepoints): bool
    {
        if ($ranges === []) {
            return true;
        }
        foreach ($codepoints as $codepoint) {
            foreach ($ranges as [$from, $to]) {
                if ($codepoint >= $from && $codepoint <= $to) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return array<string, mixed>|null */
    private function stylesheet(string $url, string $from): ?array
    {
        $path = $this->localPath($url, $from);
        if ($path === null || $this->size($path, $url, $from) === null) {
            return null;
        }
        if (! isset($this->stylesheets[$path])) {
            $this->stylesheets[$path] = ['path' => $path] + $this->parsedCss((string) file_get_contents($this->root . '/' . $path), $path);
            $this->stylesheets[$path]['id'] = $path;
        }

        return $this->stylesheets[$path];
    }

    /** @return array<string, mixed> */
    private function parsedCss(string $css, string $context): array
    {
        $css = (string) preg_replace('~/\*.*?\*/~s', '', $css);
        $sheet = ['id' => 'inline:' . md5($context . "\0" . $css), 'path' => null, 'context' => $context, 'imports' => [], 'faces' => [], 'rules' => []];
        $this->parseBlock($css, $sheet);

        return $sheet;
    }

    /** @param array<string, mixed> $sheet */
    private function parseBlock(string $css, array &$sheet): void
    {
        $length = strlen($css);
        $start = 0;
        $index = 0;
        while ($index < $length) {
            $character = $css[$index];
            if ($character === '"' || $character === "'") {
                $index = $this->skipString($css, $index);

                continue;
            }
            if ($character === ';') {
                $statement = trim(substr($css, $start, $index - $start));
                if (preg_match('~^@import\s+(?:url\(\s*)?["\']?([^"\')\s]+)~i', $statement, $match) === 1) {
                    $sheet['imports'][] = $match[1];
                }
                $start = ++$index;

                continue;
            }
            if ($character === '{') {
                $prelude = trim(substr($css, $start, $index - $start));
                $end = $this->matchingBrace($css, $index);
                $body = substr($css, $index + 1, $end - $index - 1);
                $this->block($prelude, $body, $sheet);
                $start = $index = $end + 1;

                continue;
            }
            $index++;
        }
    }

    /** @param array<string, mixed> $sheet */
    private function block(string $prelude, string $body, array &$sheet): void
    {
        $lower = strtolower($prelude);
        if (str_starts_with($lower, '@font-face')) {
            $declarations = $this->declarations($body);
            $family = $this->familyName((string) ($declarations['font-family'] ?? ''));
            $urls = $this->cssUrls((string) ($declarations['src'] ?? ''));
            $ranges = [];
            foreach (explode(',', (string) ($declarations['unicode-range'] ?? '')) as $range) {
                if (preg_match('/^\s*U\+([0-9a-f?]+)(?:-([0-9a-f]+))?\s*$/i', $range, $match) === 1) {
                    $from = hexdec(str_replace('?', '0', $match[1]));
                    $to = isset($match[2]) ? hexdec($match[2]) : hexdec(str_replace('?', 'f', $match[1]));
                    $ranges[] = [(int) $from, (int) $to];
                }
            }
            if ($family !== '') {
                $weights = preg_split('/\s+/', strtr(strtolower(trim((string) ($declarations['font-weight'] ?? '400'))), ['normal' => '400', 'bold' => '700'])) ?: ['400'];
                $sheet['faces'][] = [
                    'family' => $family,
                    'url' => $urls[0] ?? null,
                    'ranges' => $ranges,
                    'weight' => [(int) $weights[0], (int) ($weights[1] ?? $weights[0])],
                ];
            }

            return;
        }
        if (preg_match('~^@(media|supports|layer|container|document|scope|starting-style)\b~', $lower) === 1) {
            $this->parseBlock($body, $sheet);

            return;
        }
        if (str_starts_with($lower, '@')) {
            return;
        }
        if (str_contains($body, '{')) {
            // CSS nesting: the nested rules count with the parent's classes.
            $this->parseBlock($body, $sheet);
        }
        $families = [];
        $variables = [];
        $uses = [];
        foreach ($this->declarations($body) as $property => $value) {
            $custom = str_starts_with($property, '--');
            if ($property !== 'font-family' && $property !== 'font' && ! $custom) {
                continue;
            }
            preg_match_all('/var\(\s*(--[\w-]+)/', $value, $matches);
            if (! $custom) {
                array_push($uses, ...$matches[1]);
            }
            foreach (explode(',', (string) preg_replace('/var\([^()]*\)/', '', $value)) as $item) {
                $name = $this->familyName($property === 'font' ? (string) preg_replace('/^.*\d\S*\s+/', '', trim($item)) : $item);
                if ($name === '') {
                    continue;
                }
                if ($custom) {
                    $variables[$property][] = $name;
                } else {
                    $families[] = $name;
                }
            }
        }
        $urls = $this->cssUrls($body);
        if ($families === [] && $variables === [] && $uses === [] && $urls === []) {
            return;
        }
        $sheet['rules'][] = [
            'selectors' => $this->selectorClasses($prelude),
            'families' => array_values(array_unique($families)),
            'variables' => $variables,
            'uses' => array_values(array_unique($uses)),
            'urls' => $urls,
        ];
    }

    /** @return list<list<string>> */
    private function selectorClasses(string $prelude): array
    {
        $selectors = [];
        $depth = 0;
        $current = '';
        foreach (str_split($prelude) as $character) {
            if ($character === '(') {
                $depth++;
            } elseif ($character === ')') {
                $depth--;
            }
            if ($character === ',' && $depth === 0) {
                $selectors[] = $current;
                $current = '';

                continue;
            }
            $current .= $character;
        }
        $selectors[] = $current;
        $result = [];
        $expanded = [];
        foreach ($selectors as $selector) {
            array_push($expanded, ...$this->expandAlternatives($selector));
        }
        foreach ($expanded as $selector) {
            do {
                $selector = (string) preg_replace('/:(?:not|is|where|has|matches|-webkit-any|nth-[a-z-]+|host|host-context|dir|lang)\([^()]*\)/i', '', $selector, -1, $count);
            } while ($count > 0);
            $selector = (string) preg_replace('/\[[^\]]*\]/', '', $selector);
            preg_match_all('/\.((?:\\\\.|[\w-])+)/u', $selector, $matches);
            $result[] = array_map(static fn (string $class): string => stripslashes($class), $matches[1]);
        }

        return $result;
    }

    /**
     * Expand :is(), :where() and :matches() into one selector per alternative,
     * so ".a:is(.b,.c)" requires a and b, or a and c.
     *
     * @return list<string>
     */
    private function expandAlternatives(string $selector, int $budget = 32): array
    {
        if (preg_match('/:(?:is|where|matches|-webkit-any)\(/i', $selector, $match, PREG_OFFSET_CAPTURE) !== 1 || $budget <= 1) {
            return [$selector];
        }
        $open = $match[0][1] + strlen($match[0][0]);
        $depth = 1;
        $items = [];
        $current = '';
        for ($index = $open; $index < strlen($selector) && $depth > 0; $index++) {
            $character = $selector[$index];
            $depth += $character === '(' ? 1 : ($character === ')' ? -1 : 0);
            if ($depth === 0 || ($character === ',' && $depth === 1)) {
                $items[] = $current;
                $current = '';

                continue;
            }
            $current .= $character;
        }
        $prefix = substr($selector, 0, $match[0][1]);
        $suffix = substr($selector, $index);
        $result = [];
        foreach ($items as $item) {
            array_push($result, ...$this->expandAlternatives($prefix . trim($item) . $suffix, intdiv($budget, max(1, count($items)))));
        }

        return $result;
    }

    /** @return array<string, string> */
    private function declarations(string $body): array
    {
        $declarations = [];
        foreach (preg_split('/;(?=(?:[^"\']|"[^"]*"|\'[^\']*\')*$)/', $body) ?: [] as $declaration) {
            $colon = strpos($declaration, ':');
            if ($colon === false) {
                continue;
            }
            $declarations[strtolower(trim(substr($declaration, 0, $colon)))] = trim(substr($declaration, $colon + 1));
        }

        return $declarations;
    }

    private function familyName(string $value): string
    {
        $value = trim((string) preg_replace('/!important\s*$/i', '', trim($value)));
        if ($value === '' || str_contains($value, 'var(') || str_contains($value, 'url(')) {
            return '';
        }
        $value = trim($value, " \t\n\"'");

        return strtolower($value);
    }

    /** @return list<string> */
    private function cssUrls(string $css): array
    {
        preg_match_all('~url\(\s*(["\']?)([^"\')]+)\1\s*\)~i', $css, $matches);
        $urls = [];
        foreach ($matches[2] as $url) {
            $url = trim($url);
            if ($url !== '' && ! str_starts_with(strtolower($url), 'data:') && ! str_starts_with($url, '#')) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    private function skipString(string $css, int $index): int
    {
        $quote = $css[$index];
        $length = strlen($css);
        for ($index++; $index < $length; $index++) {
            if ($css[$index] === '\\') {
                $index++;
            } elseif ($css[$index] === $quote) {
                return $index + 1;
            }
        }

        return $length;
    }

    private function matchingBrace(string $css, int $index): int
    {
        $depth = 0;
        $length = strlen($css);
        while ($index < $length) {
            $character = $css[$index];
            if ($character === '"' || $character === "'") {
                $index = $this->skipString($css, $index);

                continue;
            }
            if ($character === '{') {
                $depth++;
            } elseif ($character === '}' && --$depth === 0) {
                return $index;
            }
            $index++;
        }

        return $length;
    }

    /** @param array<string, array{path:string,bytes:int,kind:string}> $resources */
    private function add(array &$resources, string $url, string $from, string $kind): void
    {
        $path = $this->localPath($url, $from);
        if ($path !== null && $this->size($path, $url, $from) !== null) {
            $this->addPath($resources, $path, $kind);
        }
    }

    /** @param array<string, array{path:string,bytes:int,kind:string}> $resources */
    private function addPath(array &$resources, string $path, string $kind): void
    {
        $resources[$path] ??= ['path' => $path, 'bytes' => $this->sizes[$path], 'kind' => $kind];
    }

    private function size(string $path, string $url, string $from): ?int
    {
        if (! array_key_exists($path, $this->sizes)) {
            $file = $this->root . '/' . $path;
            $this->sizes[$path] = is_file($file) ? (int) filesize($file) : null;
        }
        if ($this->sizes[$path] === null) {
            $this->missing[$url] ??= $from;
        }

        return $this->sizes[$path];
    }

    /** The build-relative path of a same-site URL, or null for another site or a data URL. */
    private function localPath(string $url, string $from): ?string
    {
        $url = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($url === '' || str_starts_with($url, '//') || preg_match('~^[a-z][a-z0-9+.-]*:~i', $url) === 1) {
            return null;
        }
        $path = rawurldecode((string) parse_url($url, PHP_URL_PATH));
        if ($path === '') {
            return null;
        }
        if (str_starts_with($path, '/')) {
            if ($this->base !== '/' && ! str_starts_with($path, $this->base)) {
                return null;
            }
            $candidate = substr($path, strlen($this->base));
        } else {
            $directory = dirname($from);
            $candidate = ($directory === '.' ? '' : $directory . '/') . $path;
        }
        $segments = [];
        foreach (explode('/', $candidate) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);

                continue;
            }
            $segments[] = $segment;
        }
        $resolved = implode('/', $segments);
        if (str_ends_with($path, '/') || $resolved === '') {
            $resolved = ltrim($resolved . '/index.html', '/');
        }

        return $resolved;
    }
}
