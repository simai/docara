# Composition Recipe in Docara

Composition Recipe is the internal composition contract for every Docara
page. Authors still write Markdown and small inherited JSON settings. During a
build Docara turns those sources into a Recipe, the exact SIMAI Framework
runtime resolves it into a Composition Document, and Docara renders that
verified document into the static page.

The three levels have separate jobs:

1. Markdown, `docara.json`, `section.json` and page sidecars are the editable
   source.
2. Recipe explains how the page is assembled; Composition Document is the
   fully resolved tree with no hidden data reads.
3. HTML is the published result and is never an editable source.

The generated Document contains Docara page, region, section and block nodes.
Docara reconstructs its renderer input from that Document before producing
HTML. Each page receipt records the Recipe digest, Document digest, exact
execution contract and used dependencies. A mismatch fails before rendering.

## Explicit file-backed Recipe pages

Most pages need no Recipe files: Docara creates their Recipe automatically.
Use an explicit file-backed Recipe when a page must select reusable JSON
templates, fragments, content or settings before it reaches the normal Docara
shell.

The Markdown file then contains only public metadata and a project-relative
descriptor path:

```markdown
---
title: Product catalogue
description: A catalogue assembled from reusable page parts.
composition_recipe: composition/catalogue/descriptor.json
---
```

Visible Markdown after the front matter is rejected. This prevents two sources
from silently owning the same visible content. The descriptor and every source it
admits remain inside the project root.

## Exact runtime

Every production build uses the executable module from an exact generated
`ui` distribution. It never loads implementation files from `ui-source`.
Configure the local build process before running the normal command:

```bash
export DOCARA_SIMAI_UI_ROOT=/path/to/exact/ui
export DOCARA_NODE_BINARY=/path/to/node
php vendor/bin/docara build production
```

`DOCARA_NODE_BINARY` is optional when `node` is available on `PATH`. The
existing `SIMAI_UI_ROOT` variable is accepted when `DOCARA_SIMAI_UI_ROOT` is
not set, including in package checks and CI. The
Framework entry file is checked before the build session starts. One managed
Node process is reused for the complete build. The build receipt records the
generated or selected Recipe, its Document digest and all dependencies.

Docara first builds the complete candidate site in an isolated directory. An
invalid Recipe or a mismatch between the resolved Document and the Docara
projection fails the page build and leaves the previously accepted static site
unchanged. The generated site is therefore the active immutable snapshot;
Docara does not create a second active-pointer system inside the source project.

A [complete explicit page example](../examples/composition-recipe-site/README.md)
contains the Markdown front matter, Recipe, inputs, template and two header
fragments. Copy it into a newly initialized site to try the normal build.
