# Changelog

All notable changes to Docara are recorded here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and Docara uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

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
