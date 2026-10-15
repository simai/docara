# Changelog

All notable changes to Docara are recorded here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and Docara uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [3.0.3] - 2026-10-15

### Changed

- Install from this repository. The README and the documentation site add
  the repository to the project before `composer require simai/docara:^3.0`
  (a one-line `composer.json` for a new project, `composer config
  repositories.docara vcs https://github.com/simai/docara.git` for an
  existing one): the Packagist entry for simai/docara lists only 2.x until
  its maintainership moves, so a bare `composer require` would install 2.x.

## [3.0.2] - 2026-10-14

Fewer stylesheet requests. A page links a few shared first-frame stylesheets
instead of one per Framework source. Pages look and behave the same.

### Changed

- **Grouped first-frame stylesheets.** 3.0.1 published each source
  stylesheet of a page's first-frame closure as its own
  `docara-shell.<sha256>.css`, so a page linked about fifty stylesheets. Once
  every page is built, Docara now groups the sources of all pages into shared,
  content-addressed stylesheets: the common core of the site is one file,
  frequent combinations get their own files, and a page links only stylesheets
  whose sources it needs, nothing more. On the SIMAI Framework documentation
  (922 pages) a page links 3–18 stylesheets, 4.5 on average, instead of 48–64;
  on Docara's own documentation 5–11 instead of 46–62. The published
  stylesheets stay within twice the bytes of the distinct sources.
- **The cascade is unchanged.** A stylesheet is the exact bytes of its sources
  one after the other. Two sources change places only where their order cannot
  decide any declaration: they declare no common property (shorthands,
  logical/physical twins and vendor aliases count as one) in a common cascade
  layer and share no name-keyed rule such as `@keyframes`, `@property` or
  `@font-face`. The layer order comes from the Framework `core.css`, which
  every page links first. A source the analysis does not understand stays in
  place.
- **Receipts.** The Framework shell receipt is now
  `docara.framework_asset_plans.v2`: each page's plan still lists its sources
  in cascade order, and the new `stylesheets` section
  (`docara.shell_stylesheets.v1`) lists every published stylesheet with its
  sources and each page's stylesheets in link order. `verify-static` checks
  that each stylesheet is exactly its sources, that a page links exactly its
  stylesheets in that order, that their sources are the page's closure with
  every order-dependent pair kept, and that no other shell stylesheet is
  published.
- **`build --page`** keeps the other pages' stylesheets: it reuses the
  published stylesheets and adds new ones only for a changed closure. A
  complete build groups the whole site again.

### Upgrade notes

- Run a complete build once after upgrading: `build --page` needs the 3.0.2
  receipt of a complete build.

## [3.0.1] - 2026-10-14

A smaller built site. The published folder no longer carries build receipts,
the first-frame stylesheets are shared between pages, and each page carries
its navigation tree once. Pages look and behave the same.

### Changed

- **Shared first-frame stylesheets.** Docara published one
  `_docara/docara-shell.<sha256>.css` per distinct page closure: every page
  whose set of Framework utilities and components differed got its own copy
  of the same CSS, so a large site carried hundreds of near-identical 1 MB
  files. Each source stylesheet of the closure is now published once, as its
  own content-addressed `docara-shell.<sha256>.css`, and a page links the
  files of its closure in the same order as before. The bytes and their order
  are those of the former single file, so the cascade is unchanged; the files
  are shared and cached across pages. The asset-plan receipt lists one
  generated stylesheet per source, and `verify-static` checks that a page
  links exactly its stylesheets in receipt order.
- **Build receipts are no longer published.** Resolved page plans,
  performance, examples, redirects, locale routes, backlinks, translation and
  documentation status and the other projections describe how a site was
  built; a visitor never needs them. They now live next to the output, in
  `build_<environment>.docara/`, instead of `build_<environment>/.docara/`.
  `build --page`, `verify-static`, `preview` and `inspect` read them there.
  `verify-static` still reads the in-tree `.docara/` of a folder built by
  3.0.0, takes `--receipts=DIR` for a deployed copy, and reports
  `@build-receipts-in-output` when a folder whose receipts live outside also
  carries a `.docara/` directory.
- **The navigation tree is in the page once.** The mobile documentation sheet
  held a second, id-stripped copy of the sidebar navigation in a `<template>`.
  The shell runtime now fills that template from the sidebar before any other
  script runs, so the server-rendered copy is gone; the opened sheet is the
  same as before.

### Upgrade notes

- A project script that reads or writes `build_<environment>/.docara/…`
  (for example the resolved page plans, or a `standalone-examples.json`
  receipt written after the build) must use
  `build_<environment>.docara/…` instead.
- Deployment needs no change: publish `build_<environment>/` as before. Do not
  publish `build_<environment>.docara/`.
- After `docara upgrade` from 3.0.0, run `build production` once: 3.0.0
  promotes the verified folder without the receipts that now live next to it.
  From 3.0.1 on, `upgrade` promotes and rolls back the receipts with the build.

## [3.0.0] - 2026-10-14

Docara 3.0 is the first public release of Docara: a static site generator
that builds documentation sites and landing pages from Markdown and small
JSON settings files, with the interface, components and page composition of
SIMAI Framework. The package is licensed under Apache-2.0.

What you get:

- `docara init` creates a working site, `build` writes a static folder,
  `verify-static` checks it and `serve` shows it over HTTP.
- Navigation, search, page outlines, breadcrumbs, previous/next links,
  reader settings (theme, font size, width), live examples and a component
  catalogue, built from the Markdown files themselves.
- Several languages in one site, each from its own `content/<locale>/`, with
  optional translation freshness reports.
- Settings inherited from `docara.json` through `section.json` to
  `<page>.page.json`, checked against published JSON schemas.
- A developer SDK (`list`, `inspect`, `schema`, `validate`, `scaffold`,
  `preview`, `test`, `qa`, `atlas`, `capabilities`) with `--json` output, and
  an optional MCP adapter in `tools/mcp-docara/`.
- `upgrade` and `update` for moving to newer compatible releases with a
  verified build and rollback.

### Changes for projects built with Docara 2.x

- **Framework bundled, no checkout needed.** Docara bundles the public SIMAI
  Framework release `ui-82fc0ba882ba` (repository `simai/ui`, Core and Smart
  from one commit), including the Composition Recipe runtime. A build needs
  PHP and Node.js, but no Framework checkout and no `DOCARA_SIMAI_UI_ROOT` or
  `SIMAI_UI_ROOT`; these variables are now only an optional way to use
  another distribution.
- **Framework locks.** A project lock that pins the lock Docara 2.18 shipped
  builds with the bundled release. A lock naming an older Smart revision falls
  back to the legacy portable Smart copy; replace it with
  `vendor/simai/docara/stubs/portable/simai-framework.lock.json`.
- **Components removed from the free Framework release:** `sf-admin-menu` and
  `sf-table` (they are part of the Larena package).
- **`init` options.** `--template=minimal|showcase` and `--lang=en|ru`. The
  default is a small English starter (`minimal`, `en`). The starter of 2.x,
  the component catalogue with project Smart demos, is
  `--template=showcase --lang=ru`.
- **Command line.** `docara` with no arguments and `docara list` without a
  kind list the commands; `docara --version` prints the Docara version in a
  checkout too.
- **Build output.** Shared assets are published once under `_docara/` at the
  site root, not again under every locale prefix (for example
  `/ru/_docara/`). The documentation site's output dropped from 220 MB to
  139 MB.
- **Interface language.** Text Docara writes into pages (the schema reference
  table, the Atlas index, the default internal preview title, scaffolded
  example labels) follows the page language: English, or Russian on Russian
  pages. Sites in other languages get English.
- **Component catalogue metadata.** Entries in `_docara/component-catalog.json`
  carry `release` and links under the release tag in place of `revision`,
  `author` and `changed_at`.
- **Composer package.** Requires the PHP extensions it uses (`ctype`, `dom`,
  `intl`, `json`, `libxml`, `mbstring`) and resolves stable dependencies only.

To move a 2.x project: `composer require simai/docara:^3.0 -W`, then
`php vendor/bin/docara update --dry-run` and `--apply`, then build and run
`verify-static`. `docara upgrade` does not cross major versions. The Russian
documentation site has a step-by-step page.
