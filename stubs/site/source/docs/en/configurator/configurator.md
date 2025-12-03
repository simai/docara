---
extends: _core._layouts.documentation
section: content
title: Configurator
description: Configurator
---

# Configurator

`Simai\Docara\Configurator` is the core helper that collects, structures, and exposes localized docs metadata for Docara builds (menus, breadcrumbs, prev/next, headings, translations). It is resolved from the container and wired into the build lifecycle; you don’t instantiate it manually.

## Core purpose
- Read per-locale docs trees (e.g., `source/_docs-<lang>/`).
- Load `.lang.php` translations and `.settings.php` metadata.
- Build hierarchical menus and flattened navigation structures.
- Store headings and generate unique anchors.
- Provide helpers for breadcrumbs and prev/next navigation.

## How it’s wired (Docara core)
- Registered as a singleton (`configurator`) in `ConfiguratorServiceProvider`.
- Injected during lifecycle events in `DocaraEventsServiceProvider`:
  - **beforeBuild**: merges locales (from cache + project), prepares the configurator, stores it in config (`configurator`), attaches locale/sha metadata.
  - **afterCollections**: parses page headings, sets paths, builds search indexes (`INDEXES`).
  - **afterBuild**: injects heading anchors into HTML, writes `search-index_<lang>.json`.

Because it is bound in core, templates can access it via `$page->configurator` (or `app('configurator')`) without manual setup.

## Usage in templates/config
- Prev/Next navigation: `$page->configurator->getPrevAndNext($page->getPath(), $page->language)`
- Breadcrumbs: `$page->configurator->generateBreadCrumbs($page->language, $segments)`
- Translations: `$page->configurator->getTranslate($key, $page->language)`

## File/folder conventions (per locale)
<div class="files">
    <div class="folder folder--open">source
        <div class="folder folder--open">_docs-&lt;locale&gt;
            <div class="file">index.md</div>
            <div class="file">.settings.php</div>
            <div class="file">.lang.php</div>
            <div class="file">page.md</div>
            <div class="folder">section/...</div>
        </div>
    </div>
</div>

## Key responsibilities
- **Translations**: `makeLocales()` loads `.lang.php` per locale into `$translations`.
- **Settings**: `makeSettings()` scans `.settings.php`, builds the tree, flattens menus (`$flattenMenu`, `$realFlatten`), and constructs nested `$menu`.
- **Headings**: `setHeading()` stores parsed headings; `makeUniqueHeadingId()` creates stable IDs for anchors.
- **Paths**: `setPaths()` collects page paths; used for search index generation.

## Lifecycle touchpoints (core)
- **beforeBuild**: configurator prepared and exposed via config; locales merged from cached `.config.json` + project `config.php`.
- **afterCollections**: headings extracted, paths stored, `INDEXES` populated for search.
- **afterBuild**: anchors injected, search indexes written to disk.

## Search index output
Per-locale search data is written to the build destination as `search-index_<lang>.json`, containing title, url, language, plain content, and headings for each page.

## Notes
- Configurator expects locale folders named after your `DOCS_DIR` prefix, e.g., `docs_en`, `docs_ru` (or `DOCS_DIR_en` if you customize the prefix).
- Cached locales come from `temp/translations/.config.json` (or your `cache_dir`); they are merged automatically by core events.
- You no longer need to touch `bootstrap.php` to wire the configurator; Docara core handles registration and lifecycle calls.
