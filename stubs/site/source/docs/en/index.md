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
            <div class="file">copy-template-configs.js</div>
            <div class="file">webpack.mix.js</div>
            <div class="file">package.json</div>
            <div class="file">config.php</div>
            <div class="file">...</div>
        </div>
        <div class="folder folder--open">{$DOCS_DIR}
            <div class="folder">{$locale}/section</div>
            <div class="file">.lang.php</div>
            <div class="file">.settings.php</div>
            <div class="file">index.md</div>
        </div>
        <div class="file">index.blade.md</div>
    </div>
    <div class="folder">stubs</div>
    <div class="folder">build_*</div>
    <div class="file">config.php (preserved on init)</div>
    <div class="file">composer.json</div>
</div>
