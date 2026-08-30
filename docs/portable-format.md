# Portable project format

A Docara project is a directory that can be built with PHP and Composer.

## Required files

- `composer.json`, `composer.lock` and `vendor/` form the exact project-local
  Docara runtime used for build, verification and compatible upgrades.
- `docara.json` defines the site, locale registry, routes, preset and reading
  defaults.
- `simai-framework.lock.json` pins the admitted Framework pair and asset
  hashes.
- `content/<locale>/` contains Markdown pages.

Optional `section.json` files configure a directory and its descendants.
Optional `<page>.page.json` files configure one Markdown page. `redirects.json`
contains explicit redirects. `assets/` contains project-owned public files.

## Ownership

`docara upgrade` may replace only the project-local dependency runtime,
package-owned engine state and the verified build after an isolated candidate
passes every gate. Authored Markdown, examples, page and section settings,
redirects, project assets, translations and the Framework lock are hash-bound
inputs and are never rewritten. `docara update` is the lower-level transaction
for `.docara/engine` only. `init --update` remains disabled.

## Build contract

The builder validates paths, schemas, locales, component props, templates and
the Framework lock before publishing. Output is written atomically. A
production build is byte-deterministic for the same source and exact package
revision. `verify-static` checks receipts, generated pages, assets, redirects,
search data and local references.

Generated `build_*` and `.docara` directories are outputs, not authoring
surfaces.
