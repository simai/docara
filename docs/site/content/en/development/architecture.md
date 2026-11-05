---
description: "Docara architecture: the parts of the package, the steps of a build and which files stay the source of data."
---

# Architecture

The Docara architecture lives in one package, `simai/docara`. It handles the
command line, the JSON schemas, settings inheritance, turning Markdown into
HTML, the link to SIMAI Framework, the starter for a new project and the
static build.

A folder is a Docara project if it contains `docara.json` with the
`docara.site.v1` schema.

## Build steps

:::steps
1. Load and check the site, section, page and Framework lock.
2. Resolve the inheritance and the origin of values (provenance) for each
   page.
3. Compile Markdown and Smart calls once into a typed in-memory `DocumentIr`.
4. Prepare the page, regions, sections and blocks from the checked settings.
   Turn this structure into a Recipe, pass it to the standard SIMAI Framework
   builder and get a finished Composition Document.
   Restore the PHP renderer input from this checked document.
5. Build the routes and the canonical topology.
6. Derive the visible menu, breadcrumbs and previous/next from the one
   topology.
7. Render the content through the single component registry and the shared
   entry for Smart components, and the layout regions through registered
   templates and immutable view models.
8. Build the index, menu, breadcrumbs, page contents, neighbouring pages,
   search and the machine-readable component catalogue from the PageBuilder
   results.
9. Assemble a candidate full document through the registered publisher
   template and pass it to the shared Framework Asset Planner.
10. From the existing Loader registry, determine the exact CSS/JS and
    first-frame dependencies, assemble the document again with this plan and
    check that it is stable against the final HTML.
11. Write the HTML, CSS with a content hash in the name, the Framework receipts
    for each page, the search index and the other plans.
12. Only after full success, replace the current `build_*` in one transaction.

:::

## Menu and page contents

The canonical site structure contains all pages, including hidden ones. The
menu is built from it: hidden pages are dropped, and their visible child
branches are kept. Breadcrumbs and neighbouring pages come from the same
structure, so the menu, the path and the page order never diverge.

The page contents are built from the safe HTML produced from Markdown before
Smart components are substituted. Each H1–H6 heading gets a unique `id`, and
the `docara.toc` component shows H2–H6 down to the `reading.toc_depth` depth.
In the settings, the right region is called `outline`: this is the permanent
name of the layout region, not the name of a component.

## Sources and derived results

- `resources/component-catalog/native`, `typed`, `smart` and `requirements`
  hold the owners' records about component capabilities;
- the exact version in the Framework lock and the component descriptions
  (manifests) define which Smart components are admitted;
- `EffectiveComponentCatalogBuilder` creates one checkable catalogue;
- component pages are written in Markdown, and their list is built from the
  metadata of the PageBuilder results, with no separate page generator;
- `_docara/component-catalog.json`, the HTML and the diagnostic receipt are
  derived results, not a second source of data.

The technical record of a component holds a permanent identifier, a contract
and a status. The text of each page is in one Markdown file per language, and
repeated interface labels are in that language's `lang.json`, for example
`content/en/lang.json`. The package fixtures and the data of other languages
do not own a page.

## The page document

All pages go through one PageBuilder and the shared Composition Recipe builder
from SIMAI Framework. Markdown first gets a typed `DocumentIr` representation,
and the finished structure of the whole page gets a Composition Document.
These are different documents: the first describes the Markdown that was read,
the second the result of building the page with its regions, sections and
blocks. The layout builder accepts only the typed PageBuilder result: you
cannot bypass it with raw HTML or a generated page.

The source of data is files. A database, editing in the browser, roles and
approvals are not part of Docara.

`resources/publisher/templates/page.php` is only the outer template of the HTML
document. The page shell elements are in the registered templates in
`resources/publisher/components`, and the templates, descriptions and CSS/JS of
the Docara Smart components are in `resources/smart`. `resources/portable`
keeps the shared shell grid and the behaviour of the whole document. The
builder itself contains no HTML, CSS or browser code.

## What the build needs

The build needs PHP 8.2+ and Node.js 20+. The pinned SIMAI Framework
distribution, including the Composition Recipe runtime, is part of the Docara
package. Node.js runs the shared Recipe resolver once for the whole build; the
author does not need a separate frontend project. The finished site stays
static: publishing it needs neither PHP nor Node.js.

If `node` is not in `PATH`, give the path to Node.js:

```bash
export DOCARA_NODE_BINARY=/path/to/node
php vendor/bin/docara build production
```

The optional `DOCARA_SIMAI_UI_ROOT` (or `SIMAI_UI_ROOT`) variable points the
build at another Framework distribution that implements the same Recipe
specification. An ordinary build does not need it.

Each page gets Recipe and finished Document fingerprints in the build report.
A check failure stops the candidate and keeps the previous result.
[Two page variants](/en/development/examples/) show an explicit Recipe that
picks the header by a setting; for ordinary pages the Recipe is created
automatically.
