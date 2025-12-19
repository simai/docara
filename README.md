# Docara (based on Jigsaw)

Quick start to install Docara via Composer and initialize a project.

## Install the framework

```bash
composer require simai/docara
```

## Configure `.env`

Create `.env` in your project root (example):

```text
AZURE_KEY=<AZURE_KEY>
AZURE_REGION=<AZURE_REGION>
AZURE_ENDPOINT=https://api.cognitive.microsofttranslator.com
DOCS_DIR=docs
```

## Initialize a new project

From an empty project directory:

```bash
php vendor/bin/docara init
```

This will:

-   copy the base template (stubs),
-   copy bundled `source/_core`,
-   copy template configs from `_core`,
-   run frontend dependency install (`npm/yarn install` in the project root).
-   If you changed files in `source/_core`, init/update will detect your edits (whitespace-insensitive) and leave those files untouched.

## Run

-   Development/watch (if defined in your template): `yarn run watch` or `npm run watch`
-   Build: `yarn run prod` / `npm run prod` (or your template’s build script)
-   Translate test: `php vendor/bin/docara translate --test`
-   Update existing project in-place (no delete/archive, keeps `source/_core`): `php vendor/bin/docara init --update`
-   If you already have your own docs in `source/docs`, they won’t be overwritten; otherwise stubs/docs are copied.
-   If you already have `config.php` in the project root, it will be preserved during init/update.

## CLI commands

-   `php vendor/bin/docara init [--update] [--force-core-configs] [preset]`  
    Initializes or updates the project. `--force-core-configs` перезаписывает шаблонные конфиги из `_core` даже если вы их меняли (по умолчанию такие файлы пропускаются).
-   `php vendor/bin/docara build [env]` — сборка статики.
-   `php vendor/bin/docara translate [--test]` — перевод документации (требует AZURE_*).

## Structure

-   `source/` — your site source.
-   `source/_core/` — Docara/Jigsaw core (bundled and copied on init).
-   `stubs/` — template stubs used during `docara init`.
-   `build_*` — build outputs.

## Lint

-   PHP: `vendor/bin/pint --test`
-   Markdown: `npx markdownlint-cli2 "**/*.md" "!vendor" "!node_modules" "!build_*" "!dist" "!public"`

## Customize the logo

-   Replace the SVG at `source/_core/_assets/img/logo.svg` (and, if you use the wide mark, `source/_core/_assets/img/icon_and_text_logo.svg`) with your own asset.
-   If you prefer a different markup (e.g., PNG, text), edit `source/_core/_components/header/logo.blade.php`; it is now a regular file in your repo, not a submodule.
-   Rebuild assets (`yarn prod` / `npm run prod` or your preset’s build) so the new logo is emitted to `assets/build`.
-   Commit/push as usual—`source/_core` is just files, so the logo change lives in your repository.

## License

MIT
