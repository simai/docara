---
description: How to build the static site, what lies in the finished folder and next to it, how styles and scripts are attached, processing of the finished pages and reproducibility of the build.
tags: [build, static-site, reproducibility, caching]
---

# Building the site

The build needs PHP 8.2+, Composer and Node.js 20+. PHP reads the Markdown
and the settings, Node.js runs the Composition Recipe runtime, and Docara
writes the finished HTML pages. The pinned SIMAI Framework release is part of
the Docara package, so you need no separate Framework copy.

```bash
php vendor/bin/docara build local
php vendor/bin/docara build production
```

If `node` is missing from `PATH`, set `DOCARA_NODE_BINARY=/path/to/node`. You
do not need to install the npm dependencies of the site.

The first argument is the name of the environment. The default is `local`,
and the result goes into the direct child folder `build_<environment>` inside
the site root.

The builder allows clearing only a safe `build` or `build_*` folder. A
symlink, a path outside the root or an overlap with the input files stops the
build before the existing result is deleted.

Hosting the finished site needs no PHP, Composer or Node.js: the web server
serves the HTML, the CSS and the JavaScript. An ordinary site needs no project
PHP code.

## Processing the finished pages

`page_transforms` are commands that get the finished site before Docara
measures it — for example, they colour the code at build time. Each command
runs in the project root, gets the site folder as its last argument and
changes the pages in place:

```json
{
  "page_transforms": [
    {"command": ["node", "scripts/highlight-code.mjs"]}
  ]
}
```

The commands run in order, after the pages are written and before the
performance report and the build receipt, so `verify-static` checks the pages
as they were published. A non-zero exit code stops the build with
`PORTABLE_PAGE_TRANSFORM_FAILED`. A page changed by hand after the build
still fails the check.

## What lies in the finished folder

The built folder holds the HTML of the pages, copies of the content assets
and the Framework service assets. Next to it Docara puts the build receipts —
they describe how the site was built and are not published:

```text
build_production/                  published as a whole
  index.html
  404.html                         the page for addresses that do not exist
  section/page/index.html
  _docara/search-index.json
  _docara/search.js
  _docara/docara-shell.<sha256>.css   shared first-paint styles, several per page
  _docara/framework/
  _docara/vendor/simai-framework/runtime/<revision>/
  old/route/index.html
build_production.docara/           the build receipts, not published
  resolved-page-plans.json
  redirects.json
  locale-routes.json
  performance.json
  examples.json
  …
```

You can copy the `build_production/` folder to your hosting as it is: it
holds no build service files. The receipts from `build_production.docara/`
are read by `verify-static`, by the single-page rebuild `build --page`, by
`preview` and by `inspect`. A site built by Docara 3.0.0 kept them inside the
result in `.docara/`; such folders are still checked, but a new build moves
the receipts outside.

`_docara/vendor/` gets only what the built pages use. Mermaid is published if
at least one page holds a diagram, KaTeX with its fonts if there is a
formula, the Rounded and Sharp icon faces if the markup, an example script or
the site's own styles ask for that family, and the full Framework
`utility.full.css` if a page refers to it. A library's licence is published
together with it. The icon subset, the Outlined catalogue and the full
Outlined font are always published: icons outside the subset are picked by
Smart components in the browser. `verify-static` recomputes that choice from
the pages and reports `@vendor-asset-selection` if the needed file is missing
or differs from the package file.

The icon catalogue in `_docara/vendor/docara/icon-catalog/` holds static
copies of all Material Symbols icons for every family (`outlined`,
`rounded`, `sharp`), weight (`light`, `regular`, `medium`) and fill. Each
copy is about 0.3 MB, and the browser downloads only the ones the icons of
the page need. An ordinary page gets by with the Outlined subset; a page with
Rounded or Sharp icons, another weight or a fill gets one copy per such
combination instead of the full 3.5–5.3 MB font. The full font of a family
loads only for an icon with another weight (`bold`, for example), another
`grade` or another optical size.

The HTML can be served by an ordinary static web server. The `base_url` value
must match the deployment prefix: `/` for the domain root, or a safe path
such as `/project/docs/` for a nested deployment.

The search files appear only when `search.enabled: true` for at least one
page. The index is built before the destination is cleared, passes the JSON
Schema and holds only the URLs of the published pages, the locale, the
titles, the descriptions, the navigation path and the visible text. The
browser loads it lazily. The index and the runtime have independent SHA-256
query revisions.

The artifact check reads `base_url` from
`resolved-page-plans.json`: a matching deployment prefix is removed before
the file is looked up inside the build folder, and a similar or foreign
absolute path is refused.

The verifier requires an exact schema manifest, a non-empty list of pages and
an exact match of its `output` and of the redirect receipt with every HTML
file of the result. A missing, duplicated, extra, symlink or hardlink output
is refused. The HTML `<base>` element is forbidden so that relative quoted
`href`/`src` always resolve the same way for the verifier and the browser.

`404.html` is the "Page not found" page inside the site shell. It is listed
in `resolved-page-plans.json` with `"not_found": true`, all its addresses are
written from the site root, and it is absent from the search index,
`sitemap.xml`, `llms.txt` and `_docara/page-metadata.json`. Setting it up and
wiring it on the server is described in
[Hosting the site](/en/build/hosting/#the-404-page).

If `redirects_file` is not empty, the `redirects.json` receipt records the
SHA-256 of the source, the locale, the documentation version, `base_url`, the
source and target URL and the output of every redirect. The redirect HTML
holds a canonical, `noindex`, a meta refresh and an ordinary visible link to
one and the same internal target.

Local links with a `#fragment` are checked against the actual HTML `id`
values — both in raw Unicode and in percent-encoded form. The main
diagnostic markers:

| Marker | Reason |
| --- | --- |
| `@duplicate-html-id` | One `id` appears more than once on the page |
| `@missing-fragment` | A local link points at a missing anchor |
| `@unsafe-fragment-encoding` | The fragment holds wrong percent-encoding or invalid UTF-8 |

Any of these markers makes the artifact check fail.

The current lock publishes the SIMAI Framework files Docara needs locally:
Core, the utilities in use, the webpack and language chunks Docara needs,
Inter, Material Symbols and the admitted Smart assets. A page does not depend
on runtime loading from a CDN.

Before the first paint the shared SIMAI Framework production Asset Planner
analyses the finished HTML of the page by the same `distr/rule/rule.json`
rules the dynamic Loader uses. It expands the dependencies and attaches the
exact CSS set of the page and the Docara geometry in cascade order.

These CSS files are published as several shared `docara-shell.<sha256>.css`
files whose name is set by their content. Once all the pages are built,
Docara groups their source CSS into files that suit many pages at once: the
large shared part becomes one file for the whole site, frequent component
sets get their own files, and rare ones small separate files. A page attaches
only the CSS it needs, nothing extra, 4–6 files on average instead of fifty;
the browser caches them across pages. The total size of the files is capped
at twice the size of the distinct source CSS of the site.

The cascade of the page does not change. A file is the exact bytes of its
source CSS one after another. Two source CSS files swap places only when the
order between them cannot affect a single declaration: they share no property
inside one cascade layer (shorthands, logical and physical properties and
prefixed aliases count as one property) and have no `@keyframes`,
`@property`, `@font-face` or similar rules with the same name. The order of
the layers is set by the Framework `core.css`, which is attached earlier.
Anything the analysis does not understand stays where it is.
The JavaScript modules needed are attached in dependency order before Core,
and `window.SF_PRELOADED` lists only the resources actually published. So the
Loader does not load them again after the first paint and stays responsible
only for the content that appeared later.

Docara is the first consumer of this shared build-time mechanism, not the
owner of a separate list of Framework components. A project without
production planning keeps working the old dynamic way through `sfPath`,
`sfSmartPath`, `core.css` and `core.js`. A rare first-paint resource that
cannot be seen in the final HTML can be named explicitly with the optional
`data-sf-require`.

The same early resources include the subsets of Inter and Material Symbols in
use. An ordinary fenced code block gets a server-side language header right
away, with the same geometry as the final interactive copy bar. This is not a
global skeleton: the content of the page is available at once, and a late
component registration does not change the size of the shell or of the code
blocks.

While Inter loads, the Framework uses a local metrically compatible fallback.
It keeps the natural size of the text and of the controls, so the font swap
does not rebuild the header, the menu or the columns. You need no fixed
header height, no hidden page and no separate project cache for that.

`resolved-page-plans.json` holds a separate Framework asset receipt for every
HTML output: the input files and their SHA-256, the JavaScript order, the
list of preload modules, the hash of the HTML content and the public names of
the generated CSS in attachment order.
`verify-static` recomputes every plan from the final HTML and refuses a false
preload, changed CSS, a missing resource, a stale receipt or a different
attachment order. In the exact production mode `utility.full.css` is not
published.

On static hosting it is best to split the caching:

- files with a SHA-256 in the name — `Cache-Control: public, max-age=31536000,
  immutable`;
- HTML — without `immutable`, with an ordinary freshness check (`ETag` or
  `Last-Modified`).

This does not
turn an arbitrary upstream component into a supported one: admission is still
decided by the manifest, the provider policy and the exact lock.

Public production or release readiness should be claimed only after a
separate acceptance of the specific artifact and of the distribution terms.

### What the build reports about itself

The system shows exactly what it built. The receipt
`build_production.docara/resolved-page-plans.json` keeps the effective
settings of every page and the origin of the values, and
`_docara/component-catalog.json` records the set of components available in a
particular build.

## Reproducibility

With the same Markdown, JSON, Framework lock and generator version, a
repeated build must produce a byte-for-byte identical result.

Docara does not write the current date or absolute local paths into the
portable output. `_docara/page-metadata.json` uses the Git history of the
exact source if it is available; in a Composer dist without `.git` the
`updated_at`, `revision` and `author` fields are `null`. The time a file was
unpacked or changed does not reach the public result. For every page the
receipt `build_production.docara/resolved-page-plans.json` holds:

- the final configuration;
- an ordered trace of the input files with their SHA-256;
- the provenance of every value;
- the canonical hash of the plan;
- the normalized component calls;
- the exact asset plan;
- the output path and the public URL.

To check this, build the site twice into clean folders and compare the hashes
of the trees. A change of the input or of the lock must change the matching
plan in an explainable way.

If search is on, `content_sha256` in `_docara/search-index.json` is computed
from the canonical documents, and the query revision of `_docara/search.js`
from the exact bytes of the runtime. So a change of the content and a change
of the search code invalidate the cache independently and stay reproducible.

The CSS of the permanent shell is assembled from the pinned Framework
resources and `declarative-shell.css` and published as shared
`docara-shell.<sha256>.css` files; the name is set by the exact bytes of the
file. The grouping depends only on the CSS sets of all the pages, so an
identical build gets the same files. The Framework shell receipt records for
every page its source CSS, the files attached and their order, the preloaded
modules and the hash of every file, and any meaningful change gives a new
name. The single-page rebuild `build --page` does not touch the other pages:
it uses the published files and adds new ones only for the set that changed,
while a full build groups the whole site again.

If redirects are configured, the `redirects.json` receipt records in the same
way the canonical records, the SHA-256 of the canonically normalized source,
the locale, the documentation version and `base_url`. The formatting and the
order of the entries in the input JSON do not change the build result.

## See also

- [Checking the build](/en/build/verify/) — what `verify-static` checks
- [Hosting the site](/en/build/hosting/)
- [Local preview](/en/build/local-preview/)
