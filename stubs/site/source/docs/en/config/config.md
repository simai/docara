---
extends: _core._layouts.documentation
section: content
title: config.php
description: Configuration keys reference
---

# config.php reference

Docara loads `config.php` from your project root (and merges `config.<env>.php` if present). Use it to tune URLs, locales, caching, navigation helpers, and landing page behaviour.

## Core flags

-   `baseUrl` — canonical site URL; used for link generation.
-   `production` — boolean; toggles minification/asset behaviour.
-   `category` — switch between single-tree and category mode for docs navigation.
-   `indexPage` — optional landing slug without locale (e.g. `collections/intro`). If omitted, Docara auto-selects a page (locale root or first available page/category).
-   `defaultLocale` — locale code used when no locale segment is detected.

## Paths & caching

-   `cache` — enable Docara cache.
-   `cachePath` — where cache files are stored (default `.cache` in project root).
-   `lang_path` — path to language files (defaults to `source/lang`).
-   `buildPath` (set in container, exposed via config) — controls `source` and `destination` roots for builds.

## Metadata & frontend

-   `siteName`, `siteDescription` — shown in templates.
-   `github` — base repo URL for "Edit on GitHub" links.
-   `locales` — map of locale code to human-readable name; drives language switcher.
-   `tags` — array of custom tag class short names registered by Docara.
-   `turbo` — enable Turbo Drive navigation (default: false). When true, Docara JS tears down listeners/observers on `turbo:before-render` and re-inits on `turbo:load`; keep your own scripts Turbo-safe too.

## Helpers (closures)

These are callable values consumed by templates; you can override them to change behaviour:

-   `getNavItems($page)` — return prev/next navigation items.
-   `getMenu($page)` — return sidebar items; respects `category` mode.
-   `generateBreadcrumbs($page)` — build breadcrumbs for current path.
-   `getJsTranslations($page)` — expose translation strings to JS.
-   `isHome($page)` — decide if current page is the locale landing page.
-   `layout` — layout configuration tree (header/aside/main/footer/floating), see `stubs/site/config.php` for defaults.

## Layout skeleton

Docara ships a layout tree you can override in `config.php` via the `layout` key. A shortened excerpt from `stubs/site/config.php`:

```php
$layoutConfiguration = [
    'base' => [
        'header' => [
            'enabled' => true,
            'blocks' => [
                'logo' => ['enabled' => true],
                'topMenu' => ['enabled' => true],
                'search' => ['enabled' => true],
                'toolbar' => [
                    'enabled' => true,
                    'items' => [
                        ['type' => 'button', 'label' => 'Feedback', 'action' => '/feedback'],
                        ['type' => 'menu', 'label' => 'Share', 'items' => [...]],
                    ],
                ],
            ],
        ],
        'asideLeft' => ['enabled' => true, 'blocks' => ['menu' => ['enabled' => true]]],
        'main' => ['innerContent' => ['enabled' => true], 'outerContent' => ['enabled' => false]],
        'asideRight' => ['enabled' => true, 'blocks' => ['navigation' => ['enabled' => true]]],
        'footer' => ['enabled' => false],
        'floating' => ['enabled' => true, 'fabBackToTop' => ['enabled' => true]],
    ],
];

return [
    // ...
    'layout' => $layoutConfiguration,
];
```

Disable or extend individual blocks (e.g., add toolbar items, hide `asideRight`, enable `outerContent` iframe) without touching Blade templates—Docara reads this tree at runtime.

## Example minimal config

```php
return [
    'baseUrl' => '',
    'category' => false,
    'indexPage' => 'config', // optional landing slug
    'locales' => ['en' => 'English'],
    'defaultLocale' => 'en',
    'siteName' => 'Simai Documentation',
    'siteDescription' => 'Simai framework documentation',
];
```
