<?php

namespace Simai\Docara;

use FilesystemIterator;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Helper\ProgressBar;
use Simai\Docara\Console\ConsoleOutput;
use Simai\Docara\Multiple\MultipleHandler;

class Configurator
{
    public array $locales;

    public array $paths = [];

    public array $settings;

    public array $fingerPrint = [];

    public bool $useCategory = false;

    public array $translations = [];

    public string $distPath = '';

    public array $headings;

    public array $menu;

    public array $flattenMenu;

    public bool $hasIndexPage = false;

    public string $indexPage = '';

    private MultipleHandler $multipleHandler;

    public array $topMenu = [];

    public ConsoleOutput $console;

    public array $realFlatten = [];

    public string $locale = 'en';

    public string $docsDir = 'source/docs/';

    private Container $container;

    private Docara $docara;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->console = $this->container['consoleOutput'];
        $docsDirEnv = $_ENV['DOCS_DIR'] ?? getenv('DOCS_DIR') ?? null;
        if (! $docsDirEnv) {
            $this->console->writeln('<comment>DOCS_DIR is not set; defaulting to "docs".</comment>');
            $docsDirEnv = 'docs';
        }
        $this->docsDir = 'source/' . trim($docsDirEnv, '/\\') . '/';
        $this->extractImages();
    }

    public function prepare($locales, $docara): void
    {
        $this->console->writeln(PHP_EOL . '<comment>=== Configurator prepare ===</comment>');
        $this->docara = $docara;
        $this->distPath = $docara->getDestinationPath();
        $this->useCategory = $docara->getConfig('category');
        $this->locale = $docara->getConfig('defaultLocale');
        $this->console->writeln(PHP_EOL . "<comment>=== Default locale set is ({$this->locale}) ===</comment>");
        $this->locales = array_keys($locales);
        $this->makeSettings();
        $value = filter_var($this->useCategory, FILTER_VALIDATE_BOOLEAN);
        $this->console->writeln(PHP_EOL . sprintf(
            '<comment>=== UseCategory: %s ===</comment>',
            $value ? 'true' : 'false'
        ));
        if ($this->useCategory) {
            $this->multipleHandler = new MultipleHandler;
            $this->makeMultipleStructure();
        } else {
            $this->makeSingleStructure();
        }
        $this->makeLocales();
    }

    private function array_set_deep(&$array, $path, $value, $locale): void
    {
        $segments = explode('/', $path);
        if ($segments[0] === '') {
            $segments[0] = $locale;
        } else {
            $segments = explode('/', $locale . '/' . $path);
        }
        $current = &$array;
        foreach ($segments as $segment) {
            if (! isset($current['pages'][$segment])) {
                $current['pages'][$segment] = [];
            }
            $current = &$current['pages'][$segment];
        }
        $dir = $this->docsDir . '/' . $locale;
        if (is_dir($dir . '/' . $path)) {
            if (is_file($dir . '/' . $path . '/index.md') || is_file($dir . '/' . $path . '/' . $path . '.md')) {
                $value['has_index'] = true;
            } else {
                $value['has_index'] = false;
            }
        }
        if (! isset($value['showInMenu'])) {
            $value['showInMenu'] = true;
        }
        $current['current'] = $value;
    }

    public function generateBreadCrumbs($locale, $segments): array
    {

        $items = [];
        if (empty($this->realFlatten) || ! isset($this->realFlatten[$locale])) {
            return $items;
        }

        $path = '';
        foreach ($segments as $segment) {
            $path .= '/' . $segment;
            foreach ($this->realFlatten[$locale] as $value) {
                if ($value['path']) {
                    $link = $value['path'];
                } else {
                    $link = $value['navPath'];
                }
                if ($link === $path) {
                    $items[] = $value;
                }
            }
        }

        return $items;
    }

    public function makeLocales(): void
    {
        $this->console->writeln(PHP_EOL . '<comment>=== Making locales from .lang.php... ===</comment>');
        foreach ($this->locales as $locale) {
            $locales = [];
            $file = $this->docsDir . '/' . $locale . '/.lang.php';
            if (is_file($file)) {
                $content = include $file;
                $this->translations[$locale] = $content;
            }
        }
        $this->console->writeln(PHP_EOL . '<comment>=== Making success... ===</comment>');
    }

    public function getJsTranslations(string $locale): ?array
    {
        if (! empty($this->translations[$locale])) {
            return $this->translations[$locale];
        }

        return null;
    }

    private function sortPagesRecursively(array &$pages, array $menu): void
    {
        $sortedPages = [];

        foreach ($menu as $key => $_) {
            if (isset($pages[$key])) {

                if (isset($pages[$key]['pages']) && isset($pages[$key]['current']['menu'])) {

                    $this->sortPagesRecursively($pages[$key]['pages'], $pages[$key]['current']['menu']);
                }
                $sortedPages[$key] = $pages[$key];
            }
        }
        foreach ($pages as $key => $value) {
            if (! isset($sortedPages[$key])) {
                $sortedPages[$key] = $value;
            }
        }

        $pages = $sortedPages;
    }

    private function sortPages($items): array
    {
        foreach ($items['pages'] as &$item) {
            $current = $item;
            if (! isset($current['pages']) || ! isset($current['current']) || ! isset($item['current']['menu'])) {
                continue;
            }
            $this->sortPagesRecursively($item['pages'], $item['current']['menu']);
        }

        return $items;

    }

    public function makeSingleStructure(): void
    {
        foreach ($this->locales as $locale) {
            $pages = $this->makeFlatten($this->settings[$locale], $locale);
            $filteredPages = array_filter($pages['flat'], function ($item) {
                return $item['path'] !== null;
            });
            $this->flattenMenu[$locale] = array_values($filteredPages);
            $this->realFlatten[$locale] = $pages['flat'];

            $this->menu[$locale] = $this->buildMenuTree($this->settings[$locale] ?? [], '', $locale);
        }
    }

    private function getFirstPageWithIndex($key, $item): ?string
    {
        if (! empty($item['current']['has_index'])) {
            return $key;
        }

        if (isset($item['pages'])) {
            foreach ($item['pages'] as $skey => $page) {
                $getKey = $this->getFirstPageWithIndex($key . '/' . $skey, $page);
                if ($getKey) {
                    return $getKey;
                }
            }
        } else {
            return $key . '/' . array_key_first($item['current']['menu']);
        }

        return null;
    }

    public function makeMultipleStructure(): void
    {
        foreach ($this->locales as $locale) {
            foreach ($this->settings[$locale] as $item) {
                $this->hasIndexPage = ! empty($item['current']['has_index']);
                if ($this->hasIndexPage) {
                    $this->indexPage = '';
                }

                if (! isset($item['current']['menu']) || ! is_array($item['current']['menu'])) {
                    continue;
                }

                foreach ($item['current']['menu'] as $menuKey => $title) {
                    $isLink = $this->isLink($menuKey);
                    $path = '/' . $locale . '/' . $menuKey;

                    if (! $this->hasIndexPage && ! $isLink) {
                        $this->hasIndexPage = true;
                        $this->indexPage = $menuKey;
                    }

                    $path = $isLink ? $menuKey : $path;

                    if (empty($item['current']['has_index']) && isset($item['pages'][$menuKey]) && is_array($item['pages'][$menuKey])) {

                        $needKey = $this->getFirstPageWithIndex($menuKey, $item['pages'][$menuKey]);
                        if ($needKey !== null) {
                            $path = '/' . $locale . '/' . $needKey;
                        }
                    }

                    $this->topMenu[$locale][$menuKey] = [
                        'path' => $path,
                        'isLink' => $isLink,
                        'title' => $title,
                    ];

                    if (! $isLink && isset($item['pages'][$menuKey])) {
                        $menu = $this->buildMenuTree([$menuKey => $item['pages'][$menuKey]] ?? [], '', $locale);
                        $this->topMenu[$locale][$menuKey]['children'] = $item['pages'][$menuKey];
                        $pages = $this->makeFlatten([$menuKey => $item['pages'][$menuKey]], $locale);

                        $this->multipleHandler->setFlatten($locale, $menuKey, $pages);
                        $this->multipleHandler->setMenu($locale, $menuKey, $menu);
                    }
                }
            }
        }
    }

    public function makeSettings(): void
    {

        $total = 0;
        foreach ($this->locales as $locale) {
            $dir = $this->docsDir . $locale;
            if (is_dir($dir)) {
                foreach (new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir)
                ) as $file) {
                    if ($file->isFile() && $file->getFilename() === '.settings.php') {
                        $total++;
                    }
                }
            }
        }
        if ($total > 0) {
            $this->console->writeln(PHP_EOL . '<comment>=== Searching .settings.php... ===</comment>');
            $progress = new ProgressBar($this->console, $total);
            $progress->start();
            foreach ($this->locales as $locale) {
                $settings = [];
                $dir = $this->docsDir . $locale;
                if (is_dir($dir)) {
                    foreach (new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($dir)
                    ) as $file) {
                        if ($file->isFile() && $file->getFilename() === '.settings.php') {
                            $progress->advance();
                            $relativePath = str_replace($dir, '', dirname($file->getPathname()));
                            $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
                            $this->array_set_deep($settings, $relativePath, include $file->getPathname(), $locale);
                        }
                    }
                    if (! file_exists($this->distPath)) {
                        mkdir($this->distPath, 0755, true);
                    }

                    if (empty($settings)) {
                        return;
                    }

                    $settings = $this->sortPages($settings);
                    $this->settings[$locale] = $settings['pages'] ?? [];

                }
            }
            $progress->finish();

            $this->console->writeln(PHP_EOL . "<comment>=== Success read all ({$total}) .settings.php ===</comment>");
        }
    }

    /**
     * @return array[]
     */
    public function makeFlatten(array $items, string $locale): array
    {
        $pages = [
            'flat' => [],
        ];
        $this->makeMenu($items, $pages, '', $locale);

        return $pages;
    }

    public function getMenu(string $locale, array $path = []): array
    {
        if ($this->useCategory) {
            if (count($path) < 2) {
                return [];
            }
            [$locale, $key] = $path;

            return $this->multipleHandler->getMenuByCategory($locale, $key);
        } else {
            return $this->menu[$locale] ?? [];
        }
    }

    public function getTopMenu(string $locale): array
    {
        return $this->topMenu[$locale] ?? [];
    }

    public function buildMenuTree(array $items, string $prefix = '', string $locale = 'en'): array
    {
        $tree = [];

        foreach ($items as $slug => $item) {
            if ($this->useCategory && $prefix === '') {
                $slug = '/' . $locale . '/' . $slug;
            }
            $itemsSet = false;
            $title = $item['current']['title'] ?? null;
            $hasSub = ! empty($item['pages']);
            $menu = $item['current']['menu'] ?? [];
            $isLink = $this->isLink($slug);
            $currentPath = $prefix ? $prefix . '/' . $slug : $slug;

            if ($prefix === '' && $slug === $locale) {
                $fullPath = '/' . $locale;
            } else {
                $fullPath = '/' . trim($currentPath, '/');
            }
            $tree[$fullPath] = [
                'title' => $title,
                'path' => $isLink ? $slug : ($item['current']['has_index'] ? $fullPath : null),
                'isLink' => $isLink,
                'showInMenu' => $item['current']['showInMenu'],
                'children' => [],
            ];

            if (is_array($menu)) {
                foreach ($menu as $menuKey => $menuLabel) {
                    $isLink = $this->isLink($menuKey);
                    if (isset($item['pages'][$menuKey])) {
                        if ($hasSub) {
                            $itemsSet = true;
                            $tree[$fullPath]['children'] += $this->buildMenuTree($item['pages'], $currentPath, $locale);
                        }

                        continue;
                    }

                    $menuPath = $fullPath . '/' . $menuKey;

                    $tree[$fullPath]['children'][$menuPath] = [
                        'title' => $menuLabel,
                        'path' => $isLink ? $menuKey : $menuPath,
                        'isLink' => $isLink,
                        'children' => [],
                    ];
                }
            }
            if (! $itemsSet && $hasSub) {
                $tree[$fullPath]['children'] += $this->buildMenuTree($item['pages'], $currentPath, $locale);
            }
        }

        return $tree;
    }

    public function isLink(string $string): bool
    {
        return Str::startsWith($string, ['http', 'https']);
    }

    public function makeMenu(array $items, array &$pages, string $prefix = '', string $locale = 'ru'): void
    {
        foreach ($items as $key => $value) {
            $hasChildren = isset($value['pages']) && is_array($value['pages']);
            $path = trim($prefix . '/' . $key, '/');
            $setItem = false;
            $fullPath = trim($path, '/');
            $isLink = $this->isLink($key);
            if (isset($value['current']) && $value['current']['has_index']) {
                $finalPath = str_ends_with($fullPath, $path) ? $fullPath : trim($fullPath . '/' . $path, '/');
                $menuPath = '/' . $finalPath;
                $pages['flat'][] = [
                    'key' => $path,
                    'path' => $isLink ? $key : $menuPath,
                    'label' => $value['current']['title'],
                ];
                $setItem = true;
            }
            if (! $setItem && isset($value['current']['title'])) {
                $finalPath = str_ends_with($fullPath, $path) ? $fullPath : trim($fullPath . '/' . $path, '/');
                $menuPath = '/' . $finalPath;
                $pages['flat'][] = [
                    'key' => $path,
                    'path' => null,
                    'navPath' => $isLink ? $key : $menuPath,
                    'label' => $value['current']['title'],
                ];
            }
            if (isset($value['current']['menu']) && is_array($value['current']['menu'])) {
                foreach ($value['current']['menu'] as $menuKey => $menuLabel) {
                    if (isset($value['pages'][$menuKey]) || $this->isLink($menuKey)) {
                        continue;
                    }
                    $finalPath = str_ends_with($fullPath, $menuKey) ? $fullPath : trim($fullPath . '/' . $menuKey, '/');
                    $menuPath = '/' . $finalPath;
                    $pages['flat'][] = [
                        'key' => $menuKey,
                        'path' => $menuPath,
                        'label' => $menuLabel,
                    ];
                }
            }

            if ($hasChildren) {
                $this->makeMenu($value['pages'], $pages, $path, $locale);
            }
        }
    }

    public function mkFingerprint(string $html): string
    {
        $t = trim(html_entity_decode(strip_tags($html)));
        $t = preg_replace('/\s+/u', ' ', mb_strtolower($t));

        return md5($t);
    }

    public function setFingerprint($id, string $fingerprint): void
    {
        $this->fingerPrint[$fingerprint] = $id;
    }

    public function makeUniqueHeadingId($relativePath, $level, $index): string
    {
        $base = $relativePath . '-' . $level . '-' . $index;

        return 'h-' . substr(md5($base), 0, 12);
    }

    public function setHeading($path, $headings): void
    {
        $this->headings[$path] = $headings;
    }

    public function flattenNav(array $items, array &$flat): array
    {

        foreach ($items as $key => $value) {
            if (is_array($value) && $key === 'menu') {
                $flat = array_merge($flat, $value);
            } elseif (is_array($value)) {
                $this->flattenNav($value, $flat);
            }
        }

        return $flat;
    }

    public function getPrevAndNext(string $path, string $locale): array
    {
        if (! isset($this->flattenMenu[$locale])) {
            return [];
        }
        $flattenNav = $this->flattenMenu[$locale];
        $returnArr = [];
        $needly = 0;
        foreach ($flattenNav as $key => $value) {
            if (! $value['path']) {
                continue;
            }
            if ($value['path'] === $path) {
                $needly = $key;
                break;
            }

        }

        if ($needly === 0) {
            $returnArr['next'] = $flattenNav[1];
        } else {
            $returnArr['prev'] = $flattenNav[$needly - 1];
            if (isset($flattenNav[$needly + 1])) {
                $returnArr['next'] = $flattenNav[$needly + 1];
            }
        }

        return $returnArr;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function setPaths(array $paths): void
    {
        $this->paths = array_merge($paths, $this->paths);
    }

    public function getTranslate($text, $locale): string
    {
        return $this->translations[$locale][$text] ?? '';

    }

    public function getItems($locale): array
    {
        return $this->settings[$locale] ?? [];
    }

    public function extFromMime(string $mimeExt): string
    {
        $map = ['jpeg' => 'jpg', 'png' => 'png', 'svg+xml' => 'svg'];

        return $map[$mimeExt] ?? $mimeExt;
    }

    public function saveB64AndReturnRel(string $source, string $b64, string $ext): string
    {
        $b64 = preg_replace('/\s+/', '', $b64);
        $bytes = base64_decode($b64, true);
        if ($bytes === false) {
            return 'assets/build/img/b64/invalid.' . $ext;
        }
        $hash = substr(sha1($bytes), 0, 16);
        $rel = "assets/build/img/b64/{$hash}.{$ext}";
        $dst = $source . '/' . $rel;
        @mkdir(dirname($dst), 0775, true);
        if (! file_exists($dst)) {
            file_put_contents($dst, $bytes);
        }

        return $rel;
    }

    public function extractImages(bool $dryRun = false): void
    {

        @ini_set('pcre.backtrack_limit', '10000000');
        @ini_set('pcre.recursion_limit', '100000');

        $source = __DIR__ . '/source/' . $_ENV['DOCS_DIR'];
        $scanDirs = glob("{$source}/*", GLOB_ONLYDIR) ?: [];

        $reInlineShortcut = '~!\[([^\]]*)\]\(\s*b64:([A-Za-z0-9+/=\s]+)(?:,?\s*ext=([a-z0-9]+))?\s*\)~i';
        $reInlineDataUri = '~!\[([^\]]*)\]\(\s*data:image/([a-z0-9.+-]+);base64,([A-Za-z0-9+/=\s]+)\s*\)~i';
        $reRefDataUri = '~^\[([^\]]+)\]:\s*<?\s*data:image/([a-z0-9.+-]+);base64,([A-Za-z0-9+/=\s]+)>?\s*$~im';

        $changedFiles = 0;
        $inlineHits = 0;
        $refHits = 0;
        $files = [];
        foreach ($scanDirs as $dir) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                $dir,
                FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO
            ));
            foreach ($it as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'md') {
                    $files[] = $file;
                }
            }
        }
        $total = count($files);
        if ($total > 0) {
            $this->console->writeln(PHP_EOL . '<comment>=== Extracting images ===</comment>');
            $progress = new ProgressBar($this->console, $total);
            $progress->start();
            foreach ($scanDirs as $dir) {
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                    $dir,
                    FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO
                ));

                foreach ($it as $file) {
                    if (! $file->isFile() || strtolower($file->getExtension()) !== 'md') {
                        continue;
                    }
                    $progress->advance();
                    $path = $file->getPathname();
                    $md = file_get_contents($path);
                    if ($md === false || $md === '') {
                        continue;
                    }
                    $orig = $md;

                    $md = preg_replace_callback($reInlineShortcut, function ($m) use ($source, &$inlineHits) {
                        $alt = $m[1];
                        $b64 = $m[2];
                        $ext = $m[3] ?? 'png';
                        $rel = $this->saveB64AndReturnRel($source, $b64, $ext);
                        $inlineHits++;

                        return '![' . $alt . '](/' . $rel . ')';
                    }, $md);

                    $md = preg_replace_callback($reInlineDataUri, function ($m) use ($source, &$inlineHits) {
                        $alt = $m[1];
                        $ext = $this->extFromMime($m[2]);
                        $b64 = $m[3];
                        $rel = $this->saveB64AndReturnRel($source, $b64, $ext);
                        $inlineHits++;

                        return '![' . $alt . '](/' . $rel . ')';
                    }, $md);

                    $md = preg_replace_callback($reRefDataUri, function ($m) use ($source, &$refHits) {
                        $label = $m[1];
                        $ext = $this->extFromMime($m[2]);
                        $b64 = $m[3];
                        $rel = $this->saveB64AndReturnRel($source, $b64, $ext);
                        $refHits++;

                        return '[' . $label . ']: /' . $rel;
                    }, $md);

                    if ($md !== $orig) {
                        $changedFiles++;
                        if ($dryRun) {
                            echo "[dry-run] would update: {$path}\n";
                        } else {
                            file_put_contents($path, $md);
                            echo "Updated: {$path}\n";
                        }
                    }
                }
            }

            $progress->finish();
            $this->console?->writeln(PHP_EOL . sprintf(
                '<info>Done</info>: files=%d, inline=%d, refs=%d, changed=%d',
                $total,
                $inlineHits,
                $refHits,
                $changedFiles
            ));
        } else {
            $this->console->writeln(PHP_EOL . '<comment>=== Skip Extracting images 0 files ===</comment>');
        }

    }

}
