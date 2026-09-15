# Composition Recipe pages

Docara can publish a page whose visible content is assembled from a Simai
Framework Composition Recipe. The normal portable-site pipeline still owns the
page shell, navigation, theme, assets, search and atomic publication.

Use a Recipe page when the page must combine reusable JSON templates,
fragments, content and settings. Keep ordinary prose pages in Markdown.

The Markdown file contains only public metadata and a project-relative
descriptor path:

```markdown
---
title: Product catalogue
description: A catalogue assembled from reusable page parts.
composition_recipe: composition/catalogue/descriptor.json
---
```

Visible Markdown after the front matter is rejected. This prevents two sources
from silently owning the same page content. The descriptor and every source it
admits remain inside the project root.

Recipe compilation uses the executable module from an exact generated `ui`
distribution. It never loads implementation files from `ui-source`. Configure
the local build process before running the normal command:

```bash
export DOCARA_SIMAI_UI_ROOT=/path/to/exact/ui
export DOCARA_NODE_BINARY=/path/to/node
php vendor/bin/docara build production
```

`DOCARA_NODE_BINARY` is optional when `node` is available on `PATH`. The
Framework entry file is checked before every compilation, and the build receipt
records the selected descriptor, document digest and all used Recipe
dependencies.

Docara first builds the complete candidate site in an isolated directory. An
invalid Recipe fails the page build and leaves the previously accepted static
site unchanged. The generated site is therefore the active immutable snapshot;
Docara does not create a second active-pointer system inside the source project.
