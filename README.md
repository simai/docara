# Docara

Docara builds static documentation and small content sites. The repository has
two deliberately separate modes:

- **portable JSON + Markdown** — the new opt-in declarative prototype;
- **legacy Blade/Jigsaw** — the existing `.settings.php`, `config.php` and
  `source/_core` pipeline, kept compatible for current projects.

The portable mode is an experimental compatibility candidate. It does not claim
production readiness or readiness of every Simai Framework component.

## Install the framework

```bash
composer require simai/docara
```

## Portable JSON + Markdown quick start

From an empty project directory:

```bash
php vendor/bin/docara init --portable
php vendor/bin/docara build local
```

No `.env`, Node.js build, `source/_core` copy or legacy preset is required. The
generated project contains:

```text
docara.json                       site defaults
simai-framework.lock.json         immutable Framework runtime
content/_section.json             settings inherited by all content
content/guides/_section.json      nested section settings
content/page.md                   Markdown content
content/page.page.json            optional page override
```

Resolution order is deterministic:

```text
docara.json -> ancestor _section.json files -> optional page sidecar -> Markdown
```

Objects merge recursively, arrays replace inherited arrays, and
`{"$reset": true}` explicitly clears an inherited object. The `docs` and
`landing` presets share the same format and Simai Framework runtime.

Portable Markdown can call bounded Smart-components through their real
manifests:

```markdown
:::ui.alert
{"type":"info","title":"Ready","supporting-text":"The page was built from Markdown."}
:::

:::ui.button
{"text":"Continue","size":"1","type":"default","scheme":"primary"}
:::
```

Every generated page receives a human- and machine-readable resolved plan in
`build_local/.docara/resolved-page-plans.json`. Framework assets use exact
revisions from `simai-framework.lock.json`; moving `main` or `latest` references
are rejected. Exact Smart assets for the bounded `ui.alert` and `ui.button`
surface are copied to `build_local/_docara/framework/` and addressed with a
cache version derived from the accepted runtime pair and canonical asset
projection. Simai Framework Core and the Material Symbols font still use
an exact-commit jsDelivr URL, so this prototype is not an offline build.

`ui.alert` with `closable: true` is intentionally rejected: the accepted
Framework pair does not contain the required `sf-icon-button` Smart dependency.
It will become available only after that dependency is added to a future pinned
runtime contract.

To add only missing scaffold files without overwriting existing JSON or
Markdown:

```bash
php vendor/bin/docara init --portable --update
```

See [the portable format contract](docs/portable-format.md) for inheritance,
security boundaries and the planned Larena import boundary.

The local Smart projection is a non-release prototype. Public packaging,
tagging or pushing it requires an explicit owner decision about redistribution
of the upstream `ui-smart` files; the inspected source revision has no bundled
license file. This README does not claim production or release readiness.

## Legacy Blade/Jigsaw mode

The following instructions are for existing legacy projects.

### Configure `.env`

Create `.env` in your project root (example):

```text
AZURE_KEY=<AZURE_KEY>
AZURE_REGION=<AZURE_REGION>
AZURE_ENDPOINT=https://api.cognitive.microsofttranslator.com
DOCS_DIR=docs
```

### Initialize a new project

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

### Run

-   Development/watch (if defined in your template): `yarn run watch` or `npm run watch`
-   Build: `yarn run prod` / `npm run prod` (or your template’s build script)
-   Translate test: `php vendor/bin/docara translate --test`
-   Update existing project in-place (no delete/archive, keeps `source/_core`): `php vendor/bin/docara init --update`
-   If you already have your own docs in `source/docs`, they won’t be overwritten; otherwise stubs/docs are copied.
-   If you already have `config.php` in the project root, it will be preserved during init/update.

## CLI commands

-   `php vendor/bin/docara init --portable [--update]` — initialize or safely fill missing files in a portable project.
-   `php vendor/bin/docara init [--update] [--force-core-configs] [--force-core-files] [preset]`  
    Initializes or updates the project. `--force-core-configs` overwrites template configs from `_core` even if you changed them (by default changed files are skipped). `--force-core-files` overwrites the **entire** `_core` tree from stubs (ignoring your edits), but files that are already tracked in your git repo are never overwritten, even with this flag.
-   `php vendor/bin/docara build [env]` — build the site for the given environment.
-   `php vendor/bin/docara translate [--test]` — translate docs (requires AZURE_*), `--test` for a dry run.

## Legacy structure

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
