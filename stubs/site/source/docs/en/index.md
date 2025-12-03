---
extends: _core._layouts.documentation
section: content
title: Installation
description: Installation
---

# Installation

Docara is our documentation framework, built on top of Jigsaw with extra tooling for multi-language docs, collections, and automated core updates.

Requirements: PHP 8.2+, Composer, Node.js/Yarn (or npm).

## Quick start

1. Install Docara into an empty project:

```bash
composer require simai/docara
```

2. Create `.env` in the project root:

```text
AZURE_KEY=<AZURE_KEY>
AZURE_REGION=<AZURE_REGION>
AZURE_ENDPOINT=https://api.cognitive.microsofttranslator.com
DOCS_DIR=docs
```

3. Initialize (copies stubs, fetches `source/_core`, installs npm/yarn deps, preserves existing `source/<DOCS_DIR>` and `config.php`):

```bash
php vendor/bin/docara init --update
```

4. Build assets and site:

```bash
yarn prod
php vendor/bin/docara build production
```

For development watching:

```bash
yarn run watch
```

## Directory structure

<div class="files">
    <div class="folder folder--open">source
        <div class="folder folder--open">_core (auto-fetched)
            <div class="file">copy-template-configs.js (copies template configs into your docs dir)</div>
            <div class="file">webpack.mix.js (Mix build config)</div>
            <div class="file">package.json (scripts + frontend deps)</div>
            <div class="file">config.php (shared Jigsaw config)</div>
            <div class="file">bootstrap.php / translate.config.php (helpers + translator setup)</div>
            <div class="file">collections.php / navigation.php / 404.blade.php</div>
            <div class="folder folder--open">_assets (theme sources)
                <div class="folder">css (SCSS theme, colors, menus, search)</div>
                <div class="folder">js (main.js + helpers)</div>
                <div class="folder">img (logos, icons)</div>
                <div class="folder">fonts (Inter variable/woff2)</div>
            </div>
            <div class="folder">_layouts (... master/head/core/documentation blades)</div>
            <div class="folder">_components (... UI partials: language, settings, etc.)</div>
            <div class="folder">_nav (... nav partials: menu, search-input, breadcrumbs, side-menu)</div>
            <div class="folder">helpers/CustomTags (example custom tags)</div>
        </div>
        <div class="folder folder--open">{$DOCS_DIR}
            <div class="folder">{$locale}/section</div>
            <div class="file">.lang.php</div>
            <div class="file">.settings.php</div>
            <div class="file">index.md</div>
        </div>
        <div class="file">index.blade.md</div>
    </div>
    <div class="folder folder--open">stubs (copied by `docara init`)
        <div class="folder folder--open">site (starter project)
            <div class="file">.gitignore</div>
            <div class="file">config.php</div>
            <div class="folder folder--open">source
                <div class="file">.env.example</div>
                <div class="file">.gitignore</div>
                <div class="file">.gitkeep</div>
                <div class="file">config.php</div>
                <div class="file">index.blade.md</div>
                <div class="folder folder--open">docs
                    <div class="folder">en/... (full starter docs)</div>
                    <div class="folder">ru/... (synced starter docs)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="folder">build_*</div>
    <div class="file">config.php (preserved on init)</div>
    <div class="file">composer.json</div>
</div>

`source/_core` is pulled from the package to provide the base layouts, assets, and build scripts; you typically don't edit it directly. `stubs/site` is the scaffold that `docara init` copies into a fresh project so you get a ready-to-build multilingual docs site with sample content in `docs/en` and `docs/ru`, baseline configs, and `.env` templates.
