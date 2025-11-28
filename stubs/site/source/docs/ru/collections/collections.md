---
extends: _core._layouts.documentation
section: content
title: Коллекции
description: 'Collections with ENV-driven, multi-language setup'
---

# Коллекции

In this project, collections are defined **dynamically** from an environment-driven docs root.  
Instead of declaring collections inline in `config.php`, we load them from `source/_core/collections.php`, which scans language folders under `DOCS_DIR`.

## Как это работает

1. You set the docs root in `.env`:

   ```text
   DOCS_DIR=docs
   ```

2. The loader reads `source/<DOCS_DIR>/*` (e.g. `source/docs/en`, `source/docs/ru`) and creates **one collection per language**.

3. Each collection is named as `<docs-dir-with-dashes>-<lang>`, for example:

   - `docs-en`
   - `docs-ru`

4. The output paths are generated to keep Jigsaw’s _pretty URLs_:
   - `.../index.md` → `/en/index.html`, `/en/start/index.html`, …
   - `.../page.md` → `/en/.../page/index.html`

## Enabling the collections

> `config.php`

```php
<?php

return [
    // …
    'collections' => require_once 'source/_core/collections.php',
];
```

## The collections loader

> `source/_core/collections.php`

```php
<?php

use Illuminate\Support\Str;

$collections = [];

// Turn "docs" or "docs/content" into "docs" or "docs-content"
$collectionName = collect(explode('/', trim(str_replace('\\', '/', $_ENV['DOCS_DIR']), '/')))
    ->implode('-');

// Scan language folders: source/<DOCS_DIR>/*  -> en, ru, …
foreach (glob('./source/' . $_ENV['DOCS_DIR'] . '/*', GLOB_ONLYDIR) as $dir) {
    $lang = basename($dir);

    $collections["{$collectionName}-{$lang}"] = [
        // point to "docs" (the directory under source/)
        'directory' => basename('/source/' . $_ENV['DOCS_DIR']),
        'language'  => $lang,
        'extends'   => '_core._layouts.documentation',

        // only .md pages
        'filter' => fn ($page) => $page->_meta->extension === 'md',

        // build pretty, language-prefixed paths
        'path' => function ($page) use ($lang) {
            $relative = trim(str_replace('\\', '/', $page->_meta->relativePath), '/');

            // index.md (root or nested) → no explicit "/index" here;
            // PrettyOutputPathResolver will add index.html.
            if ($page->_meta->filename === 'index') {
                return $lang . ($relative ? '/' . $relative : '');
            }

            // other pages → /<lang>/<relative>/<filename>
            return $lang . ($relative ? '/' . $relative : '') . '/' . $page->_meta->filename;
        },
    ];
}

return $collections;
```

## Example structure

<div class="files">
  <div class="folder folder--open">
    source
    <div class="folder folder--open">
      docs
      <div class="folder folder--open">
        en
        <div class="file">.settings.php</div>
        <div class="file">index.md</div>
        <div class="folder folder--open">
          category
          <div class="file">index.md</div>
          <div class="file">install.md</div>
        </div>
        <div class="folder folder--open">
          category2
          <div class="file">index.md</div>
        </div>
      </div>
    </div>
    <div class="folder">ru</div>
  </div>
</div>

**Build output (simplified):**

<div class="files">
  <div class="folder folder--open">
    build_local
    <div class="folder folder--open">
      en
      <div class="file">index.html</div>
      <div class="folder folder--open">
        category
        <div class="file">index.html</div>
        <div class="folder folder--open">
          install
          <div class="file">index.html</div>
        </div>
      </div>
      <div class="folder folder--open">
        category2
        <div class="file">index.html</div>
      </div>
    </div>
  </div>
  <div class="folder folder--open">
    ru
    <div class="file">index.html</div>
  </div>
</div>

## Using collections in Blade

You can iterate over a language collection to build navigation, lists, etc.  
Collection variables follow the generated names (e.g. `docs-en`, `docs-ru`). If a hyphenated name is inconvenient in Blade, access through the page data:

```blade
@php
  $docsEn = $page->collections['docs-en'] ?? collect();
@endphp

<ul>
  @foreach ($docsEn as $doc)
    <li><a href="{{ $doc->getPath() }}">{{ $doc->title ?? $doc->getFilename() }}</a></li>
  @endforeach
</ul>
```

> Tip: front matter like `title`, `nav_order`, etc., can be defined per page and used for sorting or building menus.

## Notes & gotchas

- Keep folder names URL-friendly (no spaces); prefer `local-development` over `Local Development`.
- Для **root** `index.md` in each language, the path function must return only the language segment (`en`, `ru`) — the resolver will write `index.html` automatically.
- Vanilla Jigsaw collapses items with matching filenames across a collection (e.g. `category1/index.md` vs. `category2/index.md`). Our loader keys by relative path instead, so duplicate filenames in different folders now work.
- Clear your build/cache if paths change:

  ```bash
  rm -rf build_local/ cache/
  ```

That’s it — this mirrors your current setup while staying consistent with Jigsaw’s conventions.
