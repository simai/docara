---
extends: _core._layouts.documentation
section: content
title: Configurator
description: Configurator
---

# Configurator

The `Configurator` class is a central helper used to collect, process, and organize localized documentation structure
for Jigsaw builds. It loads translations, builds hierarchical and flat menus, stores heading data, and enables
navigation features like breadcrumbs and next/previous links.

## Core Purpose

!links

- Read directory structures per locale (e.g., `source/_docs-${lang}`/)

- Parse `.lang.php` for localization

- Build a tree of documentation pages

- Generate a flat structure for navigation and search

- Manage headings and menu trees

- Provide tools for breadcrumbs and next/previous navigation

!endlinks

## Instantiation

```php 
$configurator = new \App\Helpers\Configurator($locales);
$jigsaw->setConfig('configurator', $configurator);
```

This is done during the `beforeBuild` phase in `bootstrap.php`.

## Usage in config.php

!links

- Prev/Next navigation    `$page->configurator->getPrevAndNext(...)`
- Breadcrumbs    `$page->configurator->generateBreadCrumbs(...)`
- Translation    `$page->configurator->getTranslate(...)`

!endlinks

## Properties

### Initialization-related

!links

- public array `$locales` List of locale codes, e.g., `['en', 'ru']`

- public string `$locale` Currently active locale (default: 'ru')

!endlinks

### Data Structure Outputs

!links

- public array `$paths` List of all documentation paths

- public array `$settings` Per-locale tree of documentation pages

- public array `$menu` Hierarchical nested menu structure

- public array `$flattenMenu` Flat array of pages with valid path

- public array `$realFlatten` Flat array of all menu/page items (including ones without path)

- public array `$headings` Map of file path to extracted heading array

- public array `$translations` Per-locale loaded key-value pairs from .lang.php

!endlinks

## Constructor

```php 
public function __construct($locales)
```

!links

- Receives a `Collection` or array of locales.

- Extracts locale keys using `$locales->toArray()`.

- Triggers:

  `makeSettings()` — scans documentation structure

  `makeLocales()` — loads translations

!endlinks

## Internal File/Folder Conventions

!links

- Pages live under `source/_docs-<locale>/`

- Each folder may contain:

    - `index.md` (main page)

    - `<slug>.md` (e.g., `install.md`)

    - `.settings.php` (per-folder metadata)

    - `.lang.php` (translations)

!endlinks

## Methods Breakdown

> makeLocales(): void

Scans each locale's `.lang.php` file and loads key-value translation data into `$translations`.

```php 
$file = 'source/_docs-' . $locale . '/.lang.php';
$this->translations[$locale] = include $file;
```

> makeSettings(): void

Core method. Does all of the following:
!links

- Scans the filesystem for `.settings.php`

- Builds a recursive `$settings[locale]` tree

- Flattens the tree into `realFlatten` and `flattenMenu`

- Builds the nested menu via `buildMenuTree()`

- Also calls:

  `makeFlatten()`

  `buildMenuTree()`

!endlinks

> array_set_deep(&$array, $path, $value, $locale): void

Internal helper to deeply inject `$value` into a multi-level array, based on a slash-separated `$path`.

Used to structure page hierarchy based on folder names.

> makeFlatten(array $items, string $locale): array

Creates a flattened list of documentation pages from the nested tree, calling `makeMenu()`.

```php 
return [
'flat' => [
['key' => 'getting-started', 'path' => '/ru/getting-started', 'label' => 'Getting Started'],
...
]
];
```

> makeMenu(array $items, array &$pages, string $prefix, string $locale)

Recursive method used by `makeFlatten()` to populate the flat structure, respecting `has_index` and custom menus.

Each flat page is recorded with:
!links

- `key` (slug)

- `path` (URL)

- `label` (title)

!endlinks

> buildMenuTree(array $items, string $prefix = '', string $locale = 'ru'): array

Builds a nested associative array of pages and child items.

Used for sidebar navigation and tree-structured menus.

```php
[
    '/ru' => [
        'title' => 'Home',
        'path' => '/ru',
        'children' => [
            '/ru/install' => [...],
        ],
    ],
]
```

> generateBreadCrumbs(string $locale, array $segments): array
***Used in***: `config.php > generateBreadcrumbs`

Takes URL segments and returns a list of items from the flattened structure that match that path.

Useful for breadcrumb navigation on a page.

> getPrevAndNext(string $path, string $locale): array

Uses `$flattenMenu[locale]` to find the previous and next navigable pages relative to a given `$path`.

```php 
[
'prev' => [...],
'next' => [...],
]
```

> makeUniqueHeadingId(string $relativePath, string $level, int $index): string

Generates a hash-based heading ID used to uniquely identify `h1`–`h4` headers within documentation pages.

Output example: `h-df13a9c218f3`

> setHeading(string $path, array $headings): void

Stores heading data per documentation page.

```php 
$this->headings['ru/getting-started'] = [
  ['id' => 'intro', 'text' => 'Introduction', 'level' => 'h2']
];
```

> flattenNav(array $items, array &$flat): array

Extracts all menu items from a deeply nested structure and adds them into a flat array.

Called when generating indexed or navigable views of menu content.

> getTranslate(string $text, string $locale): string
***Used in***: `config.php > translate`

Loads translation for the given $text from `.lang.php` loaded during `makeLocales()`.

If not found, return empty string (``).

> setLocale(string $locale): void

Sets the working locale for internal logic.

> setPaths(array $paths): void

Registers documentation paths for reference or indexing.

## Usage in Bootstrap Build Lifecycle

The `Configurator` class is not only exposed in `config.php`, but is also tightly integrated into the Jigsaw build
lifecycle via `bootstrap.php`.

Below is a breakdown of how and where its methods are invoked in each lifecycle event:

### beforeBuild

```php 
$locales = $jigsaw->getConfig('locales');
$configurator = new \App\Helpers\Configurator($locales);
$jigsaw->setConfig('configurator', $configurator);
```

At this stage:

- The `Configurator` is instantiated

- `.lang.php` and `.settings.php` files are loaded

- It builds:

    - `$configurator->settings`

    - `$menu`, `$flattenMenu`, `$realFlatten`

    - `$translations`

This ensures the rest of the build process and templates can use navigation, menu, and translation data.

### afterCollections

This is where page-level headings and indexing are prepared using `Configurator`.

1. ***setHeading***($path, $headings)
   Each page's `<h2>`–`<h4>` tags are parsed and stored as heading metadata:

    ```php 
    $configurator->setHeading($page->getPath(), $rightMenuHeadings);
    ```

   This powers sidebar navigation or page TOCs.

2. ***makeUniqueHeadingId***()

   Custom heading IDs are generated per heading to ensure uniqueness and consistent anchor links:

    ```php 
    $id = $configurator->makeUniqueHeadingId($page->getPath(), $match[1], $key);
    ```
   Also used when a heading lacks an explicit `id` attribute.

3. ***setPaths***(array $paths)

   All processed page paths are collected and passed to Configurator, which stores them for potential use in search or
   sitemap generation:
    ```php 
    $configurator->setPaths($paths);
    ```

### afterBuild

At this stage, the rendered HTML is post-processed. `Configurator` helps:

1. Assign Heading IDs
   Once more, `makeUniqueHeadingId()` ensures all headings in final HTML output have valid, consistent anchors:

    ```php 
    $id = $configurator->makeUniqueHeadingId($relativePath, $tag, $count);
    ```
2. Generate Anchor Links
   The HTML is updated to include anchor links (like `#` icons) with the IDs:

    ```html
    <h2 id="h-abc123"><a href="#h-abc123" class="header-anchor">#</a><span>Heading</span></h2>
    ```

## Summary of Bootstrap Usage

| Phase              | Method(s) Used                                  | Purpose                                           |
|--------------------|-------------------------------------------------|---------------------------------------------------|
| `beforeBuild`      | `__construct`, `makeSettings`, `makeLocales`    | Initialize settings and translations              |
| `afterCollections` | `setHeading`, `makeUniqueHeadingId`, `setPaths` | Store per-page heading and path data              |
| `afterBuild`       | `makeUniqueHeadingId`                           | 	Ensure final output headings are properly linked |

## Index Generation Support

Additionally, during afterCollections, the following structure is generated per page:

```php 
[
  'title' => $title,
  'url' => $page->getUrl(),
  'lang' => $page->language ?? '',
  'content' => trim($cleanedContent),
  'headings' => $headings,
];
```

This is stored in `$jigsaw->setConfig('INDEXES', $index);` and later saved as `search-index_<lang>.json`.

This system relies on:
!links

- `$configurator->getHeading()`

- `$configurator->getPaths()`

- `$configurator->getItems($locale)`

!endlinks
Even though these methods aren't directly called in bootstrap, their data powers key features in templates and indexing.

