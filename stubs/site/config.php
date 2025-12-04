<?php

use Dotenv\Dotenv;
use Illuminate\Support\Str;

$projectRoot = getcwd();

$dotenv = Dotenv::createImmutable(getcwd());
$dotenv->safeLoad();

foreach ((array) getenv() as $k => $v) {
    $_ENV[$k] = $v;
}

return [
    'baseUrl' => '',
    'production' => false,
    'env' => getenv(),
    'category' => false,
    'cache' => true,
    'cachePath' => $projectRoot . '/.cache',
    'siteName' => 'Simai Documentation',
    'siteDescription' => 'Simai framework documentation',
    'github' => 'https://github.com/simai/ui-doc-template/',
    'locales' => [
        'en' => 'English',
    ],
    'pretty' => true,
    'defaultLocale' => 'en',
    'lang_path' => 'source/lang',
    'tags' => ['ExampleTag', 'ListWrap'],
    'getNavItems' => function ($page) {
        return $page->configurator->getPrevAndNext($page->getPath(), $page->locale());
    },
    'getMenu' => function ($page) {
        $locale = $page->locale();
        if ($page->category) {
            $path = collect(explode('/', trim(str_replace('\\', '/', $page->getPath()), '/')))
                ->take(2)->toArray();

            return $page->configurator->getMenu($locale, $path);
        } else {
            return $page->configurator->getMenu($locale);
        }
    },
    'generateBreadcrumbs' => function ($page) {
        $currentPath = trim($page->getPath(), '/');
        $locale = $page->locale();
        $segments = $currentPath === '' ? [] : explode('/', $currentPath);

        return $page->configurator->generateBreadCrumbs($locale, $segments);
    },
    'getJsTranslations' => function ($page) {
        $locale = $page->locale();

        return $page->configurator->getJsTranslations($locale);
    },
    'locale' => function ($page) {
        $path = str_replace('\\', '/', $page->getPath());
        $locale = explode('/', $path);
        $current = $page->defaultLocale;
        $locales = array_keys($page->locales->toArray());
        foreach ($locale as $segment) {
            if (in_array($segment, $locales)) {
                $current = $segment;
                break;
            }
        }

        return $current;
    },
    'gitHubUrl' => function ($page) {
        return $page->configurator->getGitHubUrl($page);
    },
    'isHome' => function ($page) {
        $current = trim($page->getPath(), '/');

        return $current === $page->locale();
    },
    'collections' => require_once ('source/_core/collections.php'),
    'isActive' => function ($page, $path) {

        return Str::endsWith(trimPath($page->getPath()), trimPath($path));
    },
    'translate' => function ($page, $text) {
        return $page->configurator->getTranslate(trim($text), $page->locale());
    },
    'isActiveParent' => function ($page, $node): bool {
        $currentPath = $page->getPath();
        if ($node['path'] === $currentPath) {
            return true;
        }
        foreach ($node['children'] as $child) {
            if ($page->isActiveParent($child, $currentPath)) {
                return true;
            }
        }

        return false;
    },
];
