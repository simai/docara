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
simai-framework.lock.json         immutable Simai Framework runtime
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

The starter enables deterministic local search with
`"search": {"enabled": true, "indexed": true}`. It publishes a locale-aware
`_docara/search-index.json` and pinned browser runtime, uses no external search
service, and can exclude a page with `search.indexed: false`. Exclusion from
search or navigation is not access control; generated HTML remains public.

Documentation pages also enable the inherited `reading` contract: breadcrumbs,
a depth-limited page outline and previous/next links. They are derived from the
same canonical content topology as the menu. Deterministic Unicode heading IDs remain
available even when the visible outline is disabled, and the static verifier
rejects duplicate or unresolved fragments. Renaming a heading changes its
generated fragment, so published deep links must be checked during migration.

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

Portable Markdown also includes semantic recipes composed from Simai Framework
utilities. `:::cta` requires one native Markdown link; `:::features` requires a
flat unordered list with two to six single-paragraph items:

```markdown
:::cta
[Start](/guides/getting-started/)
:::

:::features
- **Markdown.** Keep content readable.
- **JSON.** Inherit validated settings.
- **PHP.** Build the portable static result.
:::
```

Use `ui.button` to render a visual action control; portable Docara does not bind
or execute the action. Use `cta` for navigation. Docara does not invent an
`href` for the exact button manifest.

Every portable build also writes
`<build-directory>/_docara/component-catalog.json`. This deterministic
`docara.effective_component_catalog.v1` projection describes the native
Markdown, typed Docara and Smart Simai Framework elements that the exact build
can use, together with known pending, gap and deferred requirements. It is
derived from the portable Markdown profile, typed definitions and the exact
Simai Framework lock; it is not a second Simai Framework registry and cannot
admit a Smart-component on its own.

Every generated page receives a human- and machine-readable resolved plan in
`<build-directory>/.docara/resolved-page-plans.json`. Simai Framework assets use exact
revisions from `simai-framework.lock.json`; moving `main` or `latest` references
are rejected. Exact Smart assets for the bounded `ui.alert` and `ui.button`
surface are copied to `<build-directory>/_docara/framework/` and addressed with a
cache version derived from the accepted runtime pair and canonical asset
projection. Simai Framework Core and the Material Symbols font still use
an exact-commit jsDelivr URL, so this prototype is not an offline build.

`ui.alert` with `closable: true` is intentionally rejected: the accepted Simai
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
-   run the locked frontend dependency install (`yarn install --frozen-lockfile` in the project root).
-   If you changed files in `source/_core`, init/update will detect your edits (whitespace-insensitive) and leave those files untouched.

Docara owns the legacy theme's `packageManager`, `engines.node`, `dependencies`,
`devDependencies`, standard scripts and `yarn.lock`. Before changing `.env`,
source files, cache or build output, init rejects npm locks, extra packages,
automatic lifecycle scripts, install metadata, Yarn/npm project configuration,
unsafe links, an `engines` contract other than the canonical
`^20.19.0 || >=22.12.0`, and any Yarn version
other than exactly `1.22.22`. Project name, version, description, `config` and
additional explicit scripts are preserved.

`DOCARA_SKIP_FRONTEND_INSTALL=true` is intended for controlled CI/migration
steps. It skips only the Yarn process; it does **not** skip package/lock merge,
scaffold refresh or any safety check, and it is not proof of frontend
readiness. A controlled caller must immediately run the exact frozen install
(`YARN_IGNORE_PATH=1 npx --yes yarn@1.22.22 --no-default-rc install --frozen-lockfile --production=false --non-interactive`)
before building or
deploying.

### Run

-   Development/watch (if defined in your template): `yarn run watch`
-   Build: `yarn run prod`
-   Translate test: `php vendor/bin/docara translate --test`
-   Update existing project in-place (no delete/archive, keeps `source/_core`): `php vendor/bin/docara init --update`
-   If you already have your own docs in `source/docs`, they won’t be overwritten; otherwise stubs/docs are copied.
-   If you already have `config.php` in the project root, it will be preserved during init/update.

## CLI commands

-   `php vendor/bin/docara init --portable [--update]` — initialize or safely fill missing files in a portable project.
-   `php vendor/bin/docara init [--update] [--force-core-configs] [--force-core-files] [preset]`  
    Initializes or updates the project. `--force-core-configs` overwrites template configs from `_core` even if you changed them (by default changed files are skipped). `--force-core-files` overwrites the **entire** `_core` tree from stubs (ignoring your edits), but files that are already tracked in your git repo are never overwritten, even with this flag.
-   `php vendor/bin/docara build [env]` — build the site for the given environment.
-   `php vendor/bin/docara verify-static [build-directory]` — verify the generated portable build; the directory defaults to `build_production`.
-   `php vendor/bin/docara translate [--test]` — translate docs (requires AZURE_*), `--test` for a dry run.

## Legacy structure

-   `source/` — your site source.
-   `source/_core/` — Docara/Jigsaw core (bundled and copied on init).
-   `stubs/` — template stubs used during `docara init`.
-   `build_*` — build outputs.

## Source-repository quality checks

-   PHP: `vendor/bin/pint --test`
-   Tests: `php vendor/bin/phpunit`
-   Maintainer verification of the generated documentation build: `php ./docara verify-static docs/site/build_production`

## Branding and private theme tools

Configure visible identity and metadata in project `config.php` instead of
forking a Docara layout:

```php
'brand' => [
    'title' => 'My documentation',
    'logoSvg' => null,
    'socialImage' => 'assets/build/img/logo.svg',
    'favicon' => 'favicon.ico',
],
'footerContent' => [
    'text' => 'Built with Docara',
    'url' => 'https://github.com/simai/docara',
],
```

Relative `socialImage` and `favicon` values are resolved against `baseUrl`, so
subdirectory deployments remain valid. `brand.logoSvg` is emitted as raw SVG
markup and is restricted to trusted, maintainer-authored project
configuration. Never populate it from Markdown, user input, a database or an
external feed. Keep `themeBuilder` disabled on public documentation; it exposes
private design/debug tooling and is opt-in only.

## License

MIT
