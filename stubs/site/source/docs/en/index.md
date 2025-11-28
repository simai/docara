---
extends: _core._layouts.documentation
section: content
title: Installation
description: Installation
---

# Installation

Docara, our documentation framework, is built on top of Jigsaw with a layer of custom improvements to better handle multi-language setups and collection routing.

Jigsaw requires PHP 8.1+ and Composer. Before installing Jigsaw, make sure you have [Composer](https://getcomposer.org/)
installed on your machine.

## Clone the repository with submodules:

This will copy the project to your local machine and include all core submodules:

```bash
git clone --recurse-submodules git@github.com:simai/ui-doc-template.git
```

```bash
cd <repo>
```

### Initialize submodules (if you didn't use --recurse-submodules)

This command fetches all submodules required by the project:

```bash
git submodule update --init --remote
```

### Install dependencies:

Install all required Node.js and PHP packages for development and building:

```bash
yarn install
```

```bash
composer install
```

### Configure environment variables:

In the root directory of your project, create a `.env` file and add the required configuration values:

```text
AZURE_KEY=<AZURE_KEY>
AZURE_REGION=<AZURE_REGION>
AZURE_ENDPOINT=https://api.cognitive.microsofttranslator.com
DOCS_DIR=docs
```

## Run in development mode:

Launch the development server and watches for changes to auto-build your docs:

```bash
yarn run watch
```

The project will be rebuilt automatically whenever you modify the source files.

## Directory structure

A brief overview of the main directories and files:

<div class="files">
    <div class="folder">build_env</div>
    <div class="folder folder--open">bin
        <div class="file">translate.php</div>
        <div class="file">docs-create.php</div>
    </div>
    <div class="folder folder--open">source
        <div class="folder folder--open">_core
            <div class="folder folder--open">_assets
                <div class="folder">css</div>
                <div class="folder">js</div>
                <div class="folder">fonts</div>
                <div class="folder">img</div>
            </div>
            <div class="folder folder--open">_components
                <div class="file">language.blade.php</div>
                <div class="file">more.blade.php</div>
                <div class="file">right-top-menu.blade.php</div>
                <div class="file">settings.blade.php</div>
            </div>
            <div class="folder folder--open">_layouts
                <div class="file">core.blade.php</div>
                <div class="file">documentation.blade.php</div>
                <div class="file">head.blade.php</div>
                <div class="file">header.blade.php</div>
                <div class="file">logo.blade.php</div>
                <div class="file">main.blade.php</div>
                <div class="file">master.blade.php</div>
            </div>
            <div class="folder folder--open">_nav
                <div class="file">bottom-nav.blade.php</div>
                <div class="file">breadcrumbs.blade.php</div>
                <div class="file">menu.blade.php</div>
                <div class="file">menu-toggle.blade.php</div>
                <div class="file">search-input.blade.php</div>
                <div class="file">side-menu.blade.php</div>
                <div class="file">top-menu.blade.php</div>
            </div>
            <div class="folder folder--open">helpers
                <div class="folder folder--open">CommonMark
                    <div class="file">Attrs.php</div>
                    <div class="file">BaseTag.php</div>
                    <div class="file">CustomTagAdapter.php</div>
                    <div class="file">CustomTagNode.php</div>
                    <div class="file">CustomTagRegistry.php</div>
                    <div class="file">CustomTagRender.php</div>
                    <div class="file">CustomTagExtension.php</div>
                    <div class="file">CustomTagSpec.php</div>
                    <div class="file">TagRegistry.php</div>
                    <div class="file">UniversalBlockParser.php</div>
                    <div class="file">UniversalInlineParser.php</div>
                </div>
                <div class="folder folder--open">CustomTags
                    <div class="file">ExampleTag.php</div>
                </div>
                <div class="folder folder--open">Interface
                    <div class="file">CustomTagInterface.php</div>
                </div>
                <div class="folder folder--open">Handlers
                    <div class="file">CollectionDataLoader.php</div>
                    <div class="file">CollectionHandler.php</div>
                    <div class="file">CustomCollectionItemHandler.php</div>
                    <div class="file">CustomIgnoredHandler.php</div>
                    <div class="file">CustomOutputPathResolver.php</div>
                    <div class="file">MultipleHandler.php</div>
                </div>
                <div class="file">Parser.php</div>
                <div class="file">Translate.php</div>
                <div class="file">Configurator.php</div>
            </div>
            <div class="file">.gitignore</div>
            <div class="file">404.blade.php</div>
            <div class="file">bootstrap.php</div>
            <div class="file">collections.php</div>
            <div class="file">composer.json</div>
            <div class="file">config.php</div>
            <div class="file">translate.config.php</div>
            <div class="file">copy-template-configs.js</div>
            <div class="file">eslint.config.js</div>
            <div class="file">favicon.ico</div>
            <div class="file">index.md</div>
            <div class="file">navigation.php</div>
            <div class="file">package.json</div>
            <div class="file">webpack.mix.js</div>
        </div>
            <div class="folder folder--open">docs
                <div class="folder folder--open">{$lang}
                    <div class="folder">section-name</div>
                    <div class="folder">section-name</div>
                    <div class="folder">section-name</div>
                    <div class="folder">section-name</div>
                    <div class="file">.lang.php</div>
                    <div class="file">.settings.php</div>
                    <div class="file">index.md</div>
                </div>
            </div>
        <div class="file">index.blade.md</div>
    </div>
    <div class="folder">vendor</div>
    <div class="file">.gitignore</div>
    <div class="file">.gitmodules</div>
    <div class="file">eslint.config.js</div>
    <div class="file">package.json</div>
</div>
