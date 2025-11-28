---
extends: _core._layouts.documentation
section: content
title: Categories
description: Categories
---

## Обзор

This template can build documentation either as a single tree or as a set of top-level categories. The behaviour is toggled by the `category` flag that lives in `config.php`:

```php
return [
    // ...
    'category' => false,
];
```

Turning the flag on switches the configurator into "category mode", which changes how collections are flattened, how menus are generated, and what sidebar a page receives.

## Когда `category` есть `false`

- `Configurator::prepare()` calls `makeSingleStructure()`, producing one flattened list for each locale.
- `source/_core/collections.php` still creates one collection per locale, and the sidebar is built from the entire tree returned by `.settings.php`Файлы.
- Every page uses the same menu (`$page->configurator->getMenu($locale)`), so the sidebar always reflects the full documentation tree.

This mode matches the default Jigsaw experience and is best for smaller documentation sets where a single sidebar is enough.

## Когда `category` есть `true`

- `Configurator::prepare()` switches to `makeMultipleStructure()` and instantiates `MultipleHandler`.
- Each top-level entry defined in the locale root `.settings.php` `menu` array becomes a category. For example, inside `source/docs/en/.settings.php`:

  ```php
  return [
      'menu' => [
          'collections' => 'Collections',
          'content' => 'Content',
          // ...
      ],
  ];
  ```

- For every category key (such as `collections`) the configurator:
  - Stores category-specific sidebar data through `MultipleHandler::setMenu()` и `setFlatten()`.
  - Keeps track of the first non-link menu item to use as the category's landing page.
  - Exposes the data via `Configurator::getMenu($locale, [$locale, $category])`.
- The global `getMenu` helper in `config.php` detects the flag and feeds the current page's first two path segments (`$locale/$category`) into the configurator, so only the relevant sidebar items appear.
- The top navigation (`$page->configurator->getTopMenu($locale)`) is populated with category entries, giving users an easy way to jump between sections.
- Keys that start with `http` или `https` are treated as plain links by `Configurator::isLink()`. They render in the top navigation (and any `menu` arrays) as direct anchors and skip sidebar/category generation. You can mix directory-backed categories and link-only items in the same array:

  ```php
  'menu' => [
      'collections' => 'Collections',
      'https://example.com/design-system' => 'Design System',
  ];
  ```

This mode works best for large doc sets or when you want distinct landing pages per section. Each category directory can still contain nested folders with their own `.settings.php` files; the configurator will build sub-menus the same way as in single mode.

## Directory shape in category mode

<div class="files">
  <div class="folder folder--open">
    docs
    <div class="folder folder--open">
      en
      <div class="file">.settings.php</div>
      <div class="folder folder--open">
        category1
        <div class="file">.settings.php</div>
        <div class="file">category1.md</div>
      </div>
        <div class="folder folder--open">
        category2
        <div class="file">.settings.php</div>
        <div class="file">category2.md</div>
      </div>
    </div>
  </div>
</div>

```php

  return [
      'menu' => [
          'category1' => 'Category1',
          'category2' => 'Category2',
          'https://example.com' => 'Link'
      ],
  ];

```

Ensure that each category folder has either an `index.md` or a file that matches the folder name (for example, `collections/collections.md`) so the configurator can mark the category as having a landing page.

## Creating pages with the CLI

The helper script `bin/docs-create.php` accepts a `--category=true|false` flag when scaffolding new entries. When the global `category` mode is on, passing `--category=true` tells the script to create the page under the right category directory and wire up the `.settings.php` entry automatically.

## Switching the flag

1. Update `config.php` (or set `CATEGORY=true` В `.env` file if you prefer environment overrides).
2. Бежать `npm run dev` или `npm run build` to rebuild the site so the configurator regenerates the menus.
3. Inspect the generated `_site` output: each locale will now expose either a unified sidebar (single mode) or category-specific sidebars with a top-level switcher (category mode).

If a sidebar appears empty after enabling categories, double-check that the `.settings.php` file at the locale root exposes the category key you expect and that the corresponding directory contains Markdown files.
