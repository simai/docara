---
description: verify-static checks a built site: it reads the static files, runs no project PHP and repairs nothing, it reports the broken contract.
---

# Checking the build

`verify-static` checks a portable output that is already built. The command
runs no project PHP configuration and repairs nothing; it reads the static
files and reports a broken contract fail closed.

## A named folder

```bash
php vendor/bin/docara verify-static build_production
```

## The default folder

```bash
php vendor/bin/docara verify-static
```

Without an argument it checks `build_production`.

## Build receipts

The verifier reads the build receipts from the neighbouring
`build_production.docara/` folder. For a folder built by Docara 3.0.0, where
they still sat inside the result in `.docara/`, it uses the former place. If
the result is already copied to a host without the receipts, name their folder
explicitly:

```bash
php vendor/bin/docara verify-static /var/www/docs --receipts=build_production.docara
```

If the receipts sit outside and the checked folder still holds a `.docara/`,
the check fails with the `@build-receipts-in-output` marker: service files of
the build do not belong in a published folder.

## What it checks

- the `resolved-page-plans.json` manifest from the build receipts;
- that the pages it declares match the physical output;
- local routes, links and fragments;
- duplicate HTML IDs and fragment encoding;
- the published content assets;
- the search index and the pinned search runtime;
- the redirect receipt, the static redirect pages and their targets;
- the effective component catalogue and the physical Markdown owners of the
  component pages;
- the exact SIMAI Framework asset projection and the shared
  `docara-shell.<sha256>.css` files: every file is the exact bytes of its
  source CSS, a page loads the files from its receipt in the receipt's order,
  their source CSS is exactly the page's own set, and the cascade order
  between them is kept;
- that no surface covered by the manifest is missing or extra.

A successful exit code means the checked folder is self-consistent. It is no
permission for a production deployment or a public release.

## The recommended loop

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Open `http://127.0.0.1:8000`, check the root and a nested page, the search and
a link with a fragment. Press `Ctrl+C`.

`--no-build` matters: the preview then shows exactly the folder that has just
passed the verifier. Do not use `file://`.

## If the check fails

1. Do not publish the folder.
2. Find the first diagnostic marker or file in the output.
3. Fix the source Markdown/JSON or the runtime input that owns it.
4. Do not edit `build_production` by hand.
5. Build and verify again in the same order.

The common markers for links and fragments:

- `@missing-fragment`;
- `@duplicate-html-id`;
- `@unsafe-fragment-encoding`.

Every check marker with its cause and its fix is collected on the
[Checking the build](/en/reference/errors/verify-static/) page,
and the build error codes are in the
[error code reference](/en/reference/errors/). If the cause is
unclear, keep the command, the exit code, the whole output, the exact source
revision and the path of the checked folder for diagnosis.

## Page weight

`verify-static` sets no limit on the size of a page. To keep a heavy image or
a needless font from reaching a publication unnoticed, run a separate check on
the built folder:

```bash
php vendor/simai/docara/scripts/verify-page-weight.php build_production --config=page-weight.json
```

For every page the script adds up the HTML and the files the page makes a
browser load: stylesheets with their `@import`, scripts, preloads, the favicon,
images (from a `srcset` it takes the largest variant), fonts and backgrounds
from CSS. A font from `@font-face` and a `url()` in a rule count only when the
page has every class of the selector; a font with a `unicode-range` counts
when the text holds a character from that range. Of the faces of one family it
counts the one nearest to regular (400): the others are loaded only when text
or an icon uses them. One file counts once per page. Files that scripts fetch
while they run are invisible to a static analysis: on this documentation site
that is about 0.8 MB per page.

The check fails when:

- a page is heavier than `max_page_bytes` (3 MB by default);
- an image is heavier than `max_image_bytes` (1 MB by default);
- a page with no variant icons and no icons outside the shell subset loads the
  whole Material Symbols font;
- an `<img>` with a `width` or a `height` attribute has a raster larger than
  the given size by more than `image_oversize_ratio` (2.5 by default).

The budgets live in a JSON file, and those of a single page in `pages`:

```json
{
  "max_page_bytes": 4500000,
  "max_image_bytes": 1300000,
  "pages": {
    "/en/components/diagram/": {"max_page_bytes": 6900000, "reason": "Mermaid"}
  }
}
```

The command-line options `--max-page-bytes`, `--max-image-bytes`,
`--image-oversize-ratio` and `--base-url` override the file, and `--json`
prints the report for machines. Redirect pages are not checked.

## Checking reproducibility

For a release candidate, build the site twice from one immutable source tree
into two empty destinations and compare the canonical tree digest. A mutable
worktree or an old ignored `build_*` is no exact evidence.

[Why the output has to be deterministic](/en/build/php-only/#reproducibility).
