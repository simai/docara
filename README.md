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
- copy the base template (stubs),
- fetch `source/_core` (as submodule or clone),
- copy template configs from `_core`,
- run frontend dependency install (`npm/yarn install` in the project root).


## Run

- Development/watch (if defined in your template): `yarn run watch` or `npm run watch`
- Build: `yarn run prod` / `npm run prod` (or your template’s build script)
- Translate test: `php vendor/bin/docara translate --test`
- Update existing project in-place (no delete/archive, keeps `source/_core`): `php vendor/bin/docara init --update`
- If you already have your own docs in `source/docs`, they won’t be overwritten; otherwise stubs/docs are copied.
- If you already have `config.php` in the project root, it will be preserved during init/update.

## Structure

- `source/` — your site source.
- `source/_core/` — Docara/Jigsaw core (fetched automatically on init).
- `stubs/` — template stubs used during `docara init`.
- `build_*` — build outputs.

## License

MIT
