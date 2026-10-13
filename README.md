# Docara

Docara turns a folder of Markdown pages and a few small JSON settings files
into a static documentation site or landing page. You write content; Docara
builds navigation, search, page outlines, previous/next links, live examples
and a consistent interface from [SIMAI Framework](https://github.com/simai/ui)
components, and writes plain HTML, CSS and JavaScript you can put on any static
host. It is for teams that want product or developer documentation without
maintaining a frontend toolchain or a theme.

## Requirements

On the machine that builds the site:

- **PHP 8.2 or newer** with the `ctype`, `dom`, `intl`, `json`, `libxml` and
  `mbstring` extensions (Composer checks them on install);
- **Composer**;
- **Node.js 20 or newer**, available as `node` on `PATH` (or set
  `DOCARA_NODE_BINARY` to its path). The build runs SIMAI Framework's page
  composition code with it. Commands that do not build need no Node.js.

Docara bundles the SIMAI Framework release it uses, so you do not need a
Framework checkout or any environment variables. The built site is static:
the web server needs neither PHP nor Node.js.

## Install

Install Docara into the project that will hold your site. This pins the
exact version in your `composer.lock`, and `docara upgrade` can update it
later:

```bash
mkdir my-docs && cd my-docs
composer require simai/docara:^3.0
```

Run it as `php vendor/bin/docara`. A global install
(`composer global require simai/docara:^3.0`, then `docara`) also works for
`init`, `build` and `serve`; `upgrade` only manages a project-local install.

## Quick start

```bash
# 1. Create a starter site in the current directory
php vendor/bin/docara init .

# 2. Build it into build_production/
php vendor/bin/docara build production

# 3. Check the built folder: pages, links, assets, search index
php vendor/bin/docara verify-static build_production

# 4. Look at it in the browser
php vendor/bin/docara serve production --no-build
```

Open <http://localhost:8000>. Use the HTTP server rather than opening the
HTML files directly: routes, search and assets are resolved over HTTP.

`init` takes two options:

| Option | Values | Default |
|---|---|---|
| `--template` | `minimal`: a home page, a page on writing Markdown with a live example, and a page of Framework components. `showcase`: the full component catalogue, a landing page, nested sections and project Smart components. | `minimal` |
| `--lang` | `en` or `ru`: the language of the starter content. The showcase is Russian only. | `en` |

For example, `php vendor/bin/docara init . --lang=ru` creates the minimal
starter in Russian, and `php vendor/bin/docara init . --template=showcase
--lang=ru` creates the catalogue.

Now edit `content/en/index.md` and build again. Add a Markdown file to
`content/en/` and it appears in the menu. To publish, copy `build_production/`
to your web server.

## What a project looks like

```text
docara.json                 site settings: title, languages, branding, layout, search
content/en/                 your pages (Markdown) and optional settings files
  index.md                  the home page
  section.json              settings inherited by every page in this folder
  writing.page.json         settings for one page (optional)
  lang.json                 interface strings for this language
assets/                     logo, favicon and other files you reference
redirects.json              old URL -> new URL
simai-framework.lock.json   the SIMAI Framework release the site uses
.docara/                    files Docara manages; do not edit
build_production/           the built site (generated); publish this folder
build_production.docara/    build receipts for verify-static (generated; not published)
```

Settings are inherited: `docara.json`, then each `section.json` from the
language root down to the page, then the page's own `<page>.page.json`. Most
pages need no JSON file at all.

## docara.json in brief

```json
{
  "schema": "docara.site.v1",
  "title": "My documentation",
  "preset": "docs",
  "framework_lock": "simai-framework.lock.json",
  "default_locale": "en",
  "locales": {
    "en": {"label": "English", "direction": "ltr", "content_root": "content/en", "public_prefix": "", "fallbacks": []}
  },
  "locale_routing": {"strategy": "default_unprefixed", "root": "default_locale", "detect_browser_language": false},
  "base_url": "/",
  "branding": {"title": "My documentation", "logo": "assets/docara-mark.svg", "favicon": "assets/favicon.ico"},
  "search": {"enabled": true, "indexed": true}
}
```

- `preset`: `docs` for documentation pages, `landing` for a landing page.
- `locales`: one entry per language, each with its own `content/<locale>/`
  folder. One build publishes every language.
- `base_url`: set it to `/docs/` (for example) when the site is served from a
  sub-path.
- `layout`, `reading`, `settings` and `reader_preferences` control the page
  layout, the outline and breadcrumbs, the theme and the reader's settings
  panel.

The full reference, generated from the JSON schemas, is in
[the configuration guide](docs/site/content/ru/authoring/configuration.md) and
[the settings reference](docs/site/content/ru/settings.md). Run
`php vendor/bin/docara schema site.schema.json --json` to print the schema itself.

## Commands

Run `php vendor/bin/docara` to list them and `php vendor/bin/docara help
<command>` for every option. Most commands take `--json` for machine-readable
output.

| Command | What it does |
|---|---|
| `init [path] [--template=minimal\|showcase] [--lang=en\|ru]` | Create a new site in an empty directory. |
| `build [environment] [--page=/url/]` | Build the site into `build_<environment>/` (default `local`). `--page` rebuilds one existing page after a full build. |
| `serve [environment] [--host=localhost] [--port=8000] [--no-build]` | Build, then serve the site over HTTP. |
| `verify-static [directory]` | Check a built folder (default `build_production`): pages, links, assets, search index and build receipts. |
| `upgrade [path] [--check] [--to=X.Y.Z] [--dry-run] [--apply=<plan>] [--rollback=<id>]` | Move to a newer compatible Docara release (same major version), verify it with a full build, and roll back if anything fails. |
| `update [path] --verify\|--dry-run\|--diff\|--apply\|--rollback=<id> [--adopt]` | Synchronize the files Docara manages in `.docara/engine/` with the installed package. |
| `doctor` | Check that the project, its settings and the Framework lock are consistent. |
| `capabilities` | Describe the installed Docara: version, commands, schemas. Tools and AI assistants read this. |
| `list [kind]` | Without a kind, list the commands. With one (`page`, `source`, `smart`, `layout`, `view`, `section`, `block`, `binding`, `provider`, `fixture`, `state`, `schema`), list what the project has of that kind. |
| `inspect <kind> <id>` | Show one page, component or design element with where it comes from. |
| `schema <kind>` | Print the JSON schema used for a kind of file. |
| `validate <kind> [id]` | Validate the project, a page, a source, a Smart component or a design element. |
| `scaffold [kind] [id] [--locale] [--title] [--profile] [--source] [--entity] --dry-run`, then `scaffold --apply=<plan>` | Plan, review, then create a new page, Smart component or design element. |
| `preview <target> [--page] [--selector] [--watch]` | Render one Smart component, region, layout or page through the production path. |
| `test <kind> <id> [--page]` | Render one Smart or layout fixture and check it. |
| `qa [kind] [id] --page=<url> --dry-run`, `--finalize-reference`, `--verify` | Plan and verify optional browser, accessibility and visual checks. |
| `atlas` | Show the Design Atlas: every layout, section, block, view and Smart component the project can use. |
| `translations status\|accept` | Report which translated pages are out of date, and record a reviewed translation. |
| `documentation status\|accept` | Report which pages are out of date against a source project's public contract, and record a review. |

`docs/site/content/ru/reference/cli.md` documents every option.

## Documentation

The Docara documentation site is built with Docara from
[`docs/site`](docs/site). Its content is in Russian:

- [Quick start](docs/site/content/ru/start.md)
- [Writing pages](docs/site/content/ru/authoring/markdown.md) and
  [components](docs/site/content/ru/components.md)
- [Configuration](docs/site/content/ru/authoring/configuration.md)
- [Multilingual sites](docs/site/content/ru/authoring/multilingual-site.md)
- [Build, verification and publishing](docs/site/content/ru/build.md)
- [Command reference](docs/site/content/ru/reference/cli.md)
- [Troubleshooting](docs/site/content/ru/troubleshooting.md)
- [Composition Recipe pages](docs/composition-recipe.md) (English)

To read it as a site: `cd docs/site && php ../../docara serve production`.

Contributing, the test suite and the release process are described in
[CONTRIBUTING.md](https://github.com/simai/docara/blob/main/CONTRIBUTING.md).
Report security issues as described in
[SECURITY.md](https://github.com/simai/docara/blob/main/SECURITY.md).
Changes are listed in
[CHANGELOG.md](https://github.com/simai/docara/blob/main/CHANGELOG.md).

## License

[Apache License 2.0](LICENSE); see [NOTICE](NOTICE).
