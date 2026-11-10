---
description: The docs or landing page view, container width and spacing, scrollbars and the five layout regions — header, sidebar, main, outline and footer — with turning them on and filling them.
tags: [layout, regions, preset, landing, width]
---

# Layout regions

The layout sets the frame of a page: the page view, the grid width, the
scrollbars and the regions the page is assembled from. All of it is set as
JSON data and checked by a schema before rendering.

## Two page views

One content format supports two page views (`preset`):

| View | When to use | What the reader sees |
| --- | --- | --- |
| `docs` | A guide, a reference, a knowledge base | A menu, breadcrumbs, the page outline, links to neighbouring pages |
| `landing` | A main promo page or a focused entry | The content without the documentation menu |

Both views use the same Markdown, JSON, Framework lock and safety rules. The
view is set with the `preset` field in `docara.json`, `section.json` or
`<page>.page.json`.

## A documentation page recipe

Create `content/guides/install.md`, then the neighbouring
`content/guides/install.page.json`:

```json
{
  "schema": "docara.page.v1",
  "title": "Installation",
  "description": "Installing the project from scratch.",
  "preset": "docs",
  "layout": { "container": { "max": 6 } },
  "navigation": { "order": 20 },
  "reading": {
    "breadcrumbs": true,
    "toc": true,
    "toc_depth": 3,
    "previous_next": true
  }
}
```

Build the site and check that the page is in the menu, its path is expanded,
the breadcrumbs lead to the section overview, and the page outline holds the
H2/H3 headings.

## A landing page recipe

Create `content/landing.md` and a sidecar:

```json
{
  "schema": "docara.page.v1",
  "title": "Landing",
  "preset": "landing",
  "layout": { "container": { "max": 7 } },
  "navigation": { "hidden": true },
  "search": { "enabled": false, "indexed": false }
}
```

The content stays Markdown, and the landing blocks set the composition:

- [hero](/en/components/hero/) creates a full-width first screen;
- [grid](/en/components/grid/) and [card](/en/components/card/) build a row
  of features without a special component;
- [figure](/en/components/figure/) or [media](/en/components/media/) show a
  product screenshot;
- [logos](/en/components/logos/) shows participants or customers;
- [banner](/en/components/banner/) with [button](/en/components/button/)
  ends the page with a clear action.

Ordinary headings, text and code stay in the inner container. Only
registered full-width blocks take the whole width of the landing area; the
author adds no negative margins, CSS classes or media sizes.

Ready blocks for benefits, showing a result and a call to action are
described on [Landing blocks](/en/components/landing-blocks/).
[Open the home page of this documentation](/en/).

## Checking both recipes

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Check `/guides/install/` and `/landing/` over HTTP, then press `Ctrl+C`.

## Width, spacing and scrollbars

```json
{"layout":{"key":"docara.docs","container":{"max":7},"scrollbar":{"preset":"overlay"},"content":{"gap":0}}}
```

- `layout.key` picks a registered layout; `docara.docs` is available now. The
  settings name no template, file or class.
- `layout.container.max` is an integer `1..8`. Docara turns it into the
  SIMAI Framework class `max-container-N`; the actual width comes from the
  Framework system token and is not duplicated in Docara's CSS. The value `7`
  suits most sites: a smaller number narrows the overall grid, a larger one
  widens it. The header, the documentation grid, the footer and the inner
  content of full-width landing blocks are aligned to one grid.
- `layout.content.gap` is the distance between neighbouring blocks of the
  article on the SIMAI Framework `gap-0..gap-8` scale. For ordinary
  documentation use `0`: the article stays in the normal block flow, so the
  vertical margins of neighbouring headings, paragraphs, lists and other
  elements collapse by the standard CSS rules and keep their own rhythm.
  Values `1..8` are needed only for pages where blocks deliberately form a
  free vertical stack, for example a landing page.
- `layout.scrollbar.preset` sets the scrollbars of the left menu and the page
  outline.

Docara draws no scrollbars of its own: the left tree and the page outline use
the `sf-scrollbar` component of the pinned SIMAI Framework.

| Mode | Behaviour |
| --- | --- |
| `overlay` | The default. A thin bar takes no grid space, appears on scroll, widens on hover and hides after a pause |
| `persistent` | The same compact bar stays visible all the time |
| `standard` | The standard bar of the browser and operating system is used |
| `hidden` | The bar is hidden visually, but the content still scrolls |

Without the `layout.scrollbar` branch `overlay` is used. The setting can be
inherited on the section level or replaced for a single page. The colour,
width, hover, dragging, hide timer, keyboard scrolling and the `ltr/rtl`
direction belong to the Framework and are not overridden by project CSS.

The scheme, blur and rounding are described in
[Brand and theme](/en/settings/branding-and-theme/#light-and-dark-scheme-blur-and-rounding).

## Five regions

The `docara.docs` layout consists of five named regions:

| Region | Default content | Required |
| --- | --- | :---: |
| `header` | The brand and a link to the home page | — |
| `sidebar` | A multi-level menu | — |
| `main` | The Markdown of the current page | ✓ |
| `outline` | The page outline: links to H2–H6 headings | — |
| `footer` | Empty | — |

`sidebar` and `outline` are rendered as semantic `aside` elements. `outline`
is the stable technical name of the right region; in the interface it is
called "Contents". There is no separate region named `aside`.

## Simply turning regions on and off

To turn off the left menu and the right outline on one page, add to its
sidecar:

```json
{
  "schema": "docara.page.v1",
  "layout": {
    "regions": {
      "sidebar": { "enabled": false },
      "outline": { "enabled": false }
    }
  }
}
```

What remains is the header and the main content, and both `aside` elements
and the mobile menu button are absent from the HTML.
[Open the live example, the result and the exact sources](/examples/regions-disabled/).

The setting can go into:

- `docara.json` — for the whole site;
- `section.json` — for a section and all its children;
- `<page>.page.json` — only for the neighbouring Markdown page.

A later level changes only the regions it names explicitly.

You can compare turned-on regions, ready shell recipes, inheritance, both
page views and Smart components in the
[developer examples](/en/development/examples/). Their results
are built by the same generator as the whole site.

## What a region is built from

The extended form uses the same sequence as the SIMAI commercial packages:

```text
Page -> Region -> Section -> Block -> Smart
```

For example, the standard left navigation is described like this:

```json
{
  "layout": {
    "regions": {
      "sidebar": {
        "enabled": true,
        "sections": [
          {
            "id": "docs-navigation",
            "section": "docara.navigation"
          }
        ]
      }
    }
  }
}
```

Standard sections hold only a stable instance ID and a reference to a
registered section. The slots, the `shell.smart` block, the Smart component,
`maximum_depth` and the data binding live in the
`resources/sections/docara.navigation.json` definition and are not copied to
every page:

| Region | Section | Smart component | Data |
| --- | --- | --- | --- |
| `header` | `docara.header` | `docara.brand` | `docara.branding` |
| `sidebar` | `docara.navigation` | `docara.navigation` | `docara.navigation` |
| `outline` | `docara.outline` | `docara.toc` | `docara.outline` |

A section is a registered recipe for filling a region. A Smart component is a
self-contained interface element with a manifest, props, views, templates,
assets and readiness. That is why `docara.header` is kept as the section
name while the brand component is called `docara.brand`, and
`docara.outline` is kept as the section name while the page outline component
is called `docara.toc`.

The build refuses any other combination for a standard section.

## Your own region composition

For practical filling use the registered `docara.shell` section. It accepts
stable block IDs, checked SIMAI Framework utilities and two kinds of calls:

- `shell.element` — one safe semantic element with text and, for `a`, a local
  `href`;
- `shell.smart` — a registered Smart component with props checked against its
  exact manifest. If the Smart component reads shell data, `bind` is picked
  only from the typed `BindingRegistry` and must match the region's
  capability.

A minimal footer:

```json
{
  "layout": {
    "regions": {
      "footer": {
        "enabled": true,
        "sections": [
          {
            "id": "site-footer",
            "section": "docara.shell",
            "utilities": ["flex", "flex-col", "gap-1"],
            "blocks": [
              {
                "id": "copyright",
                "block": "shell.element",
                "slot": "content",
                "element": {
                  "tag": "p",
                  "text": "Docara — documentation as code.",
                  "utilities": ["m-0"]
                }
              }
            ]
          }
        ]
      }
    }
  }
}
```

Docara sets the part's distance from the column edges itself. In the left
column the text starts where the menu items start; in the right column it
starts where the contents start and stays off the column's line. The footer is
set off by a line and has room above and below. Utilities only arrange the
elements inside the part.

This is not an HTML string. The author picks only an allowed tag, text, a
local link and utilities from the exact Framework projection. PHP, Blade, raw
HTML, event handlers, JavaScript URLs, style or class strings, callbacks and
template paths stay out of reach.

The allowed utilities are listed in `resources/framework/view-utilities.json`;
these are SIMAI Framework classes, for example `bg-surface-page` for the page
background and the pair `border-bottom-1 border-bottom-solid` for a bottom
border. The former names `surface-0`, `bg-surface-0` and `border-bottom` are
gone from the Framework, but Docara still accepts them: the page gets these Framework
classes instead, and the replacement is noted in the page plan
(`deprecated_utilities`). Write the Framework names in new files.

Navigation has three registered presentation variants: `header`, `tree` and
`compact`. They belong to one `docara.navigation` and pass through the same
Gateway. An example block inside a compatible section:

```json
{
  "id": "navigation",
  "block": "shell.smart",
  "slot": "content",
  "smart": "docara.navigation",
  "view": "compact",
  "bind": "docara.navigation",
  "props": {"maximum_depth": 4}
}
```

The `items`, `label`, `expand_label`, `collapse_label` and
`contains_current_label` fields belong to the binding and are not set by
hand.

Live sources:

- [a branded header](/examples/region-header/);
- [a sidebar with an extra block](/examples/region-sidebar/);
- [an aside with an outline and an Alert](/examples/region-aside/);
- [a footer from safe elements](/examples/region-footer/).

### A component set's line in the footer

The documentation site of a component set (SIMAI View, for example) shows in
the footer of every page which set it is: its name and release, the SIMAI
Framework it needs, its licence, and links to its source code and to the
version history. This line is not written by hand. The `shell.set-passport`
block builds it on every build from the set's description `set.json` and its
package receipt, pinned in `simai-framework.lock.json`. When the set is
updated, the line follows.

```json
{
  "id": "set",
  "block": "shell.set-passport",
  "slot": "content",
  "package": "view",
  "history": "/ru/guide/history/"
}
```

- `package` — the set's name in the lock's `package_projections`; the package
  must be a set, that is, carry `set.json`.
- `history` — an optional link to the version history page, an address from
  the site root.

While `set.json` has no release number, the package's short build code is
shown in its place: "SIMAI View, build 4d70706". The source code link leads to
the set's GitHub repository named in its description; it is the only outside
link Docara puts in the footer itself. The block belongs in the `footer`
region only. The line's texts are set in `lang.json` with the `set.*` keys
(see the [`lang.json` reference](/reference/lang-json/)).

## How the markup is formed

Layout and Section refer to registered safe View Trees:

```text
Layout View Tree -> Region
Section View Tree -> Slot -> Block -> Smart
```

A View Tree is stored in `resources/views/*.json`. It allows only a limited
set of semantic tags, safe attributes and the exact projection of SIMAI
Framework utilities. An unknown class or tag, a repeated region or a repeated
slot ends the build with an error. Complex presentation leaves can use Blade
only through Docara's internal registry; a Blade path never comes from an
author's file.

## What cannot be turned off

`main` is required for `docara.docs`. The value
`layout.regions.main.enabled: false` ends the build with the
`DECLARATIVE_REQUIRED_REGION_DISABLED` error.

If you need a fundamentally different frame, register another layout instead
of breaking the contract of the existing one.

## Inheritance and value origin

`sections` is an array: a later array replaces the inherited one entirely. A
partial change of `enabled` does not touch the inherited section list.
Section and block IDs must be unique and keep the deterministic array order.
[A live example of three levels with reset](/examples/composition-inheritance/) shows the result of `docara.json → section.json → page.page.json`.

The result and the owning file are in:

```text
build_<environment>.docara/resolved-page-plans.json
```

Check `configuration.layout.regions` and the matching pointers like
`/layout/regions/sidebar/enabled` in `provenance`.

:::ui.alert
{"type":"info","title":"Regions are already data","supporting-text":"The compiler gets the resolved layout contract and no longer hard-codes the header, sidebar and outline."}
:::

The sections, slots and blocks compatible with regions are listed in
[Interface composition](/en/development/composition/). An
unknown region, an incompatible section or block, a disallowed View Tree
utility or an unsafe path are refused before rendering.

## See also

- [Inheritance of regions](/en/settings/levels-and-inheritance/#layout-regions)
- [Smart components](/en/development/smart-components/) — how the header, menu and outline components work
- [A live example of the header, menu and outline](/en/examples/regions-composed/)
