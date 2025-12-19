---
extends: _core._layouts.documentation
section: content
title: Installation
description: Installation
---

# Installation

Docara is our documentation framework, built on top of Jigsaw with extra tooling for multi-language docs, collections,
and automated core updates.

Requirements: PHP 8.2+, Composer, Node.js/Yarn (or npm).

## Quick start

1. Install Docara into an empty project:
    ```bash
      composer require simai/docara
    ```

2. (Optional) Adjust `.env` overrides. `docara init` creates a default `.env` for you; only set `DOCS_DIR` if your docs
   folder differs from `docs` (and add Azure keys if you use translation):

    ```text
    AZURE_KEY=<AZURE_KEY>
    AZURE_REGION=<AZURE_REGION>
    AZURE_ENDPOINT=https://api.cognitive.microsofttranslator.com
    DOCS_DIR=docs
    ```

3. Initialize (copies stubs, fetches `source/_core`, installs npm/yarn deps, preserves existing `source/<DOCS_DIR>` and
   `config.php`; `--update` additionally wipes `.cache` and all `build_*` folders before refreshing):

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

!folders

- source
    - _core
      -- copy-template-configs.js (copies template configs into your docs dir)
      -- webpack.mix.js (Mix build config)
      -- package.json (scripts + frontend deps)
      -- package.json (scripts + frontend deps)
      -- config.php (shared Docara config)
      -- bootstrap.php (helpers / Docara events )
      -- translate.config.php (translator setup)
      -- collections.php (generates collections)
      -- 404.blade.php
        - _assets (theme sources)
            - css (SCSS theme, colors, menus, search)
            - js (main.js + helpers)
            - img (logos, icons)
            - fonts (Inter variable/woff2)
        - _layouts (... master/head/core/documentation blades)
        - _components (... UI partials: language, settings, nav, etc.)
    - {$DOCS_DIR}
        - {$locale}/section
          -- .lang.php
          -- .settings.php
          -- index.md
          -- index.blade.md
          -- favicon.ico
          -- .gitignore
          -- config.php
- build_*
- composer.json
  !endfolders

`source/_core` is pulled from the package to provide the base layouts, assets, and build scripts; you typically don't
edit it directly. `stubs/site` is the scaffold that `docara init` copies into a fresh project so you get a
ready-to-build multilingual docs site with sample content in `docs/en` and `docs/ru`, baseline configs, and `.env`
templates.
