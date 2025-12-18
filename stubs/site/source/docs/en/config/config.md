---
extends: _core._layouts.documentation
section: content
title: config.php
description: Configuration keys reference
---

# config.php reference

Docara loads `config.php` from your project root (and merges `config.<env>.php` if present). Use it to tune URLs, locales, caching, navigation helpers, and landing page behaviour.

## Core flags
- `baseUrl` — canonical site URL; used for link generation.
- `production` — boolean; toggles minification/asset behaviour.
- `category` — switch between single-tree and category mode for docs navigation.
- `indexPage` — optional landing slug without locale (e.g. `collections/intro`). If omitted, Docara auto-selects a page (locale root or first available page/category).
- `defaultLocale` — locale code used when no locale segment is detected.

## Paths & caching
- `cache` — enable Docara cache.
- `cachePath` — where cache files are stored (default `.cache` in project root).
- `lang_path` — path to language files (defaults to `source/lang`).
- `buildPath` (set in container, exposed via config) — controls `source` and `destination` roots for builds.

## Metadata
- `siteName`, `siteDescription` — shown in templates.
- `github` — base repo URL for “Edit on GitHub” links.
- `locales` — map of locale code to human-readable name; drives language switcher.
- `tags` — array of custom tag class short names registered by Docara.

## Helpers (closures)
These are callable values consumed by templates; you can override them to change behaviour:
- `getNavItems($page)` — return prev/next navigation items.
- `getMenu($page)` — return sidebar items; respects `category` mode.
- `generateBreadcrumbs($page)` — build breadcrumbs for current path.
- `getJsTranslations($page)` — expose translation strings to JS.
- `isHome($page)` — decide if current page is the locale landing page.
- `layout` — layout configuration tree (header/aside/main/footer/floating), see `stubs/site/config.php` for defaults.

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
