---
description: "How the Docara interface is built: a layout sets the regions, sections fill them with blocks, and Smart components draw the elements."
tags: [composition, design, layout, section, block, atlas, preview]
---

# Interface composition

Docara builds the interface like a construction kit on three levels. A layout
sets the page regions, a section fills a region with blocks, and a Smart
component or a view draws the final element. All of these are settings from
checked descriptions in registries, not a second template engine.

## Three levels

1. **A layout** is responsible for the page geometry and the names of its
   regions.
2. **A section** goes into a suitable region and lays out blocks in slots.
3. **A block**, Smart component or view receives checked data. It creates the
   final HTML through the shared registries and `SmartComponentGateway`.

The page Markdown is parsed separately, into a typed in-memory document
(Document IR). The length of a `:::` fence does not pick the composition
level.

The checked settings and the parsed Markdown turn into a shared recipe
(Composition Recipe). The standard SIMAI Framework builder creates a
Composition Document from it — the finished page tree. Docara restores its
renderers' input from the tree and creates the HTML. The author files are not
rewritten along the way.

## The path from a file to a page

Each step is described in its own registered file:

| Step | File or place | Responsible for |
| --- | --- | --- |
| Layout | `resources/layouts/docara.docs.json` | regions, whether they are required, container width |
| Layout tree | `resources/views/layout.docara.docs.json` | allowed elements and region places |
| Region | `header`, `sidebar`, `main`, `outline`, `footer` from the layout description | an insertion place, not a code branch by component ID |
| Section | `resources/sections/docara.article.json`, `docara.header.json`, `docara.navigation.json`, `docara.outline.json`, `docara.shell.json` | suitable regions and allowed blocks |
| Section tree | `resources/views/section.docara.article.json`, `section.docara.header.json`, `section.docara.shell.json` | allowed slots |
| Block | `resources/blocks/content.document.json`, `content.markdown.json`, `content.smart.json`, `shell.element.json`, `shell.smart.json` | the block data schema and its capability |
| Smart component | from the Docara package, SIMAI Framework or the project | parameters, views, presets, slots, assets, browser hydration |
| View | a registered view, preset or template | final HTML, assets, provenance |
| Shared presentation | `src/PortableSite/SurfacePresentation.php` | the single outer frame of Surface, Hero, Showcase and Promo |

This is how an ordinary documentation page goes through:

```text
Markdown owner
  -> typed Document IR
  -> Layout docara.docs
  -> region main
  -> Section docara.article
  -> slot content
  -> Block content.document
  -> generated Composition Recipe
  -> Framework resolveRecipe
  -> Composition Document
  -> Docara renderer projection
  -> SmartComponentGateway when a Smart leaf is present
  -> registered View
  -> DeclarativePageRenderer: regions into the layout View Tree
  -> PageBuilder result
```

## Who checks compatibility

For each container, Atlas shows the nesting rules: `allowed_children`,
`slots`, `min_children`, `max_children`, `order`, `max_depth` and
`depth_semantics`. The value `relative_subtree_root_level_1` means that each
container counts itself as level 1.

The registry rejects an unknown node kind, tag, attribute, utility, region or
slot in the markup tree (View Tree). This happens before registration. Project
settings only pick admitted IDs. They cannot name a class, a callback, PHP code
or a template path.

### Shared presentation does not change the owner

Surface is a container for author content. Hero, Showcase and Promo are typed
blocks of their own with their own meaning. Their owner, states, parameters
and provenance come from their own descriptions. The outer frame — width,
container and background — is assembled for all of them by one immutable
Surface presentation. You cannot wrap these blocks in Surface. The whole page
and `<head>` still belong to the application, and `DeclarativePageRenderer`
places the regions into the layout tree.

## The project atlas

Atlas is the list of everything you can put on a page: layouts, sections,
blocks, views, bindings and presets. `docara atlas --json` outputs it, and it
is built only from the admitted `DesignRegistry`, `SmartRegistry` and
`BindingRegistry` registries. The build records the Atlas fingerprint in its
receipt. Atlas is only a computed view of the registries; you cannot edit it as
a source. Below is its current content, with no hand-written list:

:::atlas_index {kind=layout,section,block,view,binding,preset}
:::

## Shell parts

One site shell serves the `docs`, `site` and `landing` page types. Variants
change the registered presentation, and the rendering path stays the same.

| Interface part | Who is responsible | Capability or presentation | Can the project replace it |
| --- | --- | --- | --- |
| Logo and name | `docara.brand` through the `docara.branding` binding | `full`, `compact`, `logo`, `text` | through an admitted brand capability |
| Header menu | `docara.navigation` | `header` | yes |
| Section tree in the sidebar | `docara.navigation` | `tree` | yes |
| Compact menu for phones | `docara.navigation` | `compact` | yes |
| Page contents | `docara.toc` | the `docara.outline` binding | yes |
| Search | a registered search Smart component | the search capability in the shell | yes |
| Breadcrumbs | a registered Smart component | reading context | yes |
| “Previous” and “Next” links | a registered Smart component | reading context | yes |
| Reading settings | `docara.preferences` | side panel | yes |
| Footer | a package or project Smart component | the footer capability | yes |
| The whole document: `<head>`, canonical address, metadata, asset list | `PageBuilder` and the application | belongs to the application | no |

`header`, `tree` and `compact` are three views of one `docara.navigation`.
`BindingRegistry` picks the right one by the capability and the provider
description. `SmartComponentGateway`, the renderer, `DeclarativePageRenderer`
and `PageBuilder` stay the same. If `view` is not given, the previous default
behaviour is kept.

A project contribution cannot replace the canonical address, the language
metadata, the build receipt, the provenance of search and the menu, or an
arbitrary `<head>` fragment. These parts belong to the application. This way
`verify-static` can check the site, and a full build and a single-page build
give the same result.

## Shell data bindings

The `shell.smart` block can pick only a registered binding in the `bind`
field. A binding connects page data with an already registered Smart
component. It is not a PHP function or a template path. The built-in registry
holds three bindings:

| ID | Capabilities | Smart component |
| --- | --- | --- |
| `docara.branding` | `shell.brand` | `docara.brand` |
| `docara.navigation` | `shell.primary-navigation`, `shell.secondary-navigation` | `docara.navigation` |
| `docara.outline` | `shell.outline` | `docara.toc` |

These commands show the binding description and its provenance:

```bash
docara list binding
docara inspect binding docara.navigation
docara schema binding
```

The layout region and the section declare suitable capabilities. Before
rendering, the build rejects an unknown binding and an incompatible
capability. It also rejects an attempt to pass parameters owned by the
binding or to substitute a package ID. Project JSON cannot set a class, a
function, or a path to a PHP file, a template or any other file.

## Your own project layouts and blocks

A project can add its own layout, section, block and safe markup tree to the
`design/` folder. This needs no changes to the engine. The chain is the same:

```text
Layout → Region → Section → Slot → Block → Smart or a safe element
```

One `DesignRegistry` reads all definitions. Built-in IDs belong to the
package, and project IDs to the namespace from `docara.json`:

```json
{"smart":{"namespace":"project"}}
```

A project ID must start with `project.`. It cannot replace `docara.*`,
`content.*`, `shell.*` or `ui.*`. Repeats, symbolic links, leaving the root and
unknown definitions stop the build.

### Files

```text
design/
├── layouts/project.docs.json
├── sections/project.article.json
├── blocks/project.document.json
└── views/
    ├── layout.project.docs.json
    └── section.project.article.json
```

The layout sets the safe markup tree, the regions, their defaults and the place
of the document:

```json
{
  "schema": "docara.layout.v1",
  "key": "project.docs",
  "default": false,
  "view": "layout.project.docs",
  "configuration": {
    "container": {"max": 7},
    "scrollbar": {"preset": "overlay"},
    "content": {"gap": 0}
  },
  "document": {
    "region": "stage",
    "section": "project.article",
    "slot": "content",
    "block": "project.document"
  },
  "regions": {
    "stage": {
      "required": true,
      "default_enabled": true,
      "default_sections": [],
      "section_types": ["content"]
    }
  },
  "assets": []
}
```

The section declares suitable regions, slots and blocks. The block picks one
of the already registered safe renderer kinds. JSON does not accept PHP, a
template path, a function, arbitrary HTML or CSS.

To pick the project layout, reset the inherited branch:

```json
{
  "layout": {
    "$reset": true,
    "key": "project.docs"
  }
}
```

## A project contribution to the shell

A shell part from the project is data files in the allowed `design/` or
`smart/` folder and a reference to an admitted binding ID. You do not need to
change the engine's `src/`. The connection order:

1. Choose your namespace in `docara.json`.
2. Create the files with `docara scaffold`: first `--dry-run`, then `--apply`
   with the plan hash.
3. Check the description, schema, namespace and capabilities.
4. Make sure with `docara atlas --json` that the element has `owner=project`
   and `support=project`.
5. Look at the result in a separate preview on a real page.
6. Only after that, build the whole site.

The build stops if:

- a project ID matches a package or SIMAI Framework ID;
- the settings try to override parameters owned by the binding;
- a capability, region or slot conflicts with ones already taken;
- a path leaves the root, goes through a symbolic or hard link, matches
  another one ignoring case, or points to an arbitrary file;
- a shell contribution tries to change the whole page or `<head>`.

The starter contains a small footer example. The project section and block
admit only the registered `project.footer-links` into the `footer` region.
Neither the settings nor Markdown pick a path to a PHP file or a template. The
ordinary project Smart component provider finds the component, its view and
assets.

The footer works as a shell part, and `project.install-builder` and
`project.product-configurator` as page content. All three go through the same
registries and `SmartComponentGateway`. The project elements on this site:

:::atlas_index {support=project}
:::

## Preview

The preview has no renderer of its own. `PreviewKernel` runs the ordinary
`PortableSiteBuilder` with the same Markdown parsing, `DesignRegistry`,
`SmartComponentGateway`, renderer, asset builder, `DeclarativePageRenderer`
and `PageBuilder`. It takes the needed part from the finished page.

### Full composition

:::internal_preview {size=tall title="Composition of regions"}
[Open the result](/en/demonstrator-results/regions-composed/)
:::

### The docs type

:::internal_preview {size=tall title="The docs type"}
[Open the result](/en/demonstrator-results/preset-docs/)
:::

### The landing type

:::internal_preview {size=tall title="The landing type"}
[Open the result](/en/demonstrator-results/preset-landing/)
:::

### Commands

The `docara preview smart|region|layout|page` command builds the chosen part
into `.docara-preview/output/<target>/`:

```bash
docara preview page --page=/en/components/alert/
docara preview layout --page=/en/components/alert/
docara preview region --page=/en/components/alert/ --selector=main
docara preview smart --page=/en/components/alert/ --selector=ui.alert
```

For automation, add `--json`. With `--watch`, the command watches everything
the chosen page depends on. That is the interface labels for its language and
the layout, section, block, Smart component, template and assets from the
package and the project that are actually used:

```bash
docara preview region --page=/en/components/alert/ --selector=main --watch
```

The result folder holds three files:

- `artifact.html` — exactly the chosen fragment;
- `index.html` — the same page as in the working build, for checking in the
  browser;
- `preview.json` — assets, provenance, dependencies and hashes.

The needed local CSS, JS, SIMAI Framework files and page assets are published
next to them. So open `index.html` over HTTP from the
`.docara-preview/output/<target>/` folder. The working build receipt and other
pages are not copied there. The preview does not overwrite the ordinary
`build_*` folders.

A change to an unrelated project Smart component does not rebuild the chosen
part. A change, addition or removal of a file inside a project or package
element that is actually used triggers one rebuild of this page. The
`target_only=true` flag is derived from this and from the rebuild result, not
set in advance.

### Why the preview does not reach the site

The preview result is isolated in `.docara-preview/` and does not count as a
working build. Its receipt records `build.purpose=preview`, so `verify-static`
fails with `PREVIEW_BUILD_PURPOSE_FORBIDDEN`. The protection comes from the
receipt itself, not from a separate service marker next to it. Publishing
needs an ordinary full build.

## Checking

```bash
php vendor/bin/phpunit --filter 'DesignRegistryTest|ProjectDesignCompositionTest|PreviewKernelTest'
cd docs/site
php ../../docara build production
php ../../docara verify-static build_production
```

Do not add project IDs to the engine's PHP code or to schema enumerations. If
you need a new executable renderer or API, that is a separate platform
extension, not project JSON.

## See also

- [Layout regions](/en/settings/layout-and-regions/) — how a site author sets up regions
- [Smart components](/en/development/smart-components/) — the component description, providers and the gateway
- [Architecture](/en/development/architecture/) — the build path from Markdown to HTML
- [SDK for developers and AI agents](/en/development/developer-sdk/) — the `inspect`, `schema`, `scaffold` and `preview` commands
- [Glossary](/en/reference/glossary/)
- [Examples for developers](/en/development/examples/)
