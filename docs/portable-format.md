# Portable project format

A Docara project is a directory that can be built with PHP and Composer.

## Required files

- `docara.json` defines the site, locale registry, routes, preset and reading
  defaults.
- `simai-framework.lock.json` pins the admitted Framework pair and asset
  hashes.
- `content/<locale>/` contains Markdown pages.

Optional `section.json` files configure a directory and its descendants.
Optional `<page>.page.json` files configure one Markdown page. `redirects.json`
contains explicit redirects. `assets/` contains project-owned public files.

## Ownership

`docara init --update` may replace engine-owned starter files. Authored
Markdown, page and section settings, redirects and project assets are preserved
unless an explicit overwrite rule says otherwise. Update is staged and must not
leave a partial project after failure.

## Build contract

The builder validates paths, schemas, locales, component props, templates and
the Framework lock before publishing. Output is written atomically. A
production build is byte-deterministic for the same source and exact package
revision. `verify-static` checks receipts, generated pages, assets, redirects,
search data and local references.

Generated `build_*` and `.docara` directories are outputs, not authoring
surfaces.
