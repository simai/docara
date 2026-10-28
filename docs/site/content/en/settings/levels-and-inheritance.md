---
description: How Docara combines site, section and page settings, how inheritance and reset work, and where a value came from.
tags: [settings, configuration, inheritance, reset, docara-json, section-json, page-json]
---

# Site, section and page

Docara reads settings from JSON files on three levels:

| Level | File | Scope |
| --- | --- | --- |
| Site | `docara.json` | All pages |
| Section | `section.json` | The folder and all pages inside it |
| Page | `<page>.page.json` | One Markdown page |

Each file has a `schema` field. `version` is optional and, if given, equals
`1` — it is the file format version, not the documentation version. An
unknown field, a wrong type, an empty styling branch and an unsafe path stop
the build. Each field is described in the reference (in Russian):
[docara.json](/ru/reference/docara-json/) and
[section.json and page.json](/ru/reference/section-and-page/).

## What can be set on each level

| Field | Site | Section | Page |
| --- | :---: | :---: | :---: |
| `preset`, `title`, `locale` | ✓ | ✓ | ✓ |
| `branding`, `layout`, `settings`, `examples`, `code` | ✓ | ✓ | ✓ |
| `navigation`, `header_navigation`, `search`, `reading` | ✓ | ✓ | ✓ |
| `reader_preferences` | ✓ | — | — |
| `framework_lock`, `content_root`, `base_url`, `site_url`, `default_locale`, `locales`, `locale_routing`, `translation_tracking`, `documentation_tracking` | ✓ | — | — |
| `documentation_version`, `redirects_file`, `page_transforms`, `not_found`, `discovery`, `portal`, `theme`, `effects`, `smart` | ✓ | — | — |
| `description`, `slug` | — | — | ✓ |

`section.json` applies to the neighbouring overview page and all children of
the folder. It does not own the Framework lock, the content root, the base
address, the language registry, the documentation version, redirects or the
project namespace. If a branch needs no settings of its own, do not create
the file.

The page file is optional too: `install.md` builds without
`install.page.json`, inheriting the site and parent sections. The main text
stays in `<route>.md`; `title`, `description`, `tags`, `draft` and
`translation_key` are easier to set in the
[Markdown metadata](/en/authoring/markdown/#page-metadata), and the page file
is needed for `slug`, the page view, the menu, the layout and other styling.
Renaming a page needs a full build and a
[redirect](/en/authoring/redirects/) from the old address.

## Built-in values and the starter

Docara starts from a small built-in set:

| Field | Built-in value |
| --- | --- |
| `content_root` | `"content"` |
| `layout.key` | `"docara.docs"` |
| `layout.container.max` | `7` |
| `layout.content.gap` | `0` |
| `layout.scrollbar.preset` | `"overlay"` |
| `layout.regions.header.enabled` | `true` |
| `layout.regions.sidebar.enabled` | `true` |
| `layout.regions.main.enabled` | `true` |
| `layout.regions.outline.enabled` | `true` |
| `layout.regions.footer.enabled` | `false` |
| `search.enabled` | `false` |
| `search.indexed` | `true` |
| `reading.breadcrumbs` | `true` |
| `reading.toc` | `true` |
| `reading.mobile_toc` | `"auto"` |
| `reading.toc_depth` | `3` |
| `reading.previous_next` | `true` |
| `examples.fullscreen` | `true` |
| `examples.wrap` | `true` |
| `code.wrap` | `true` |

The `init` starter then sets its own values in `docara.json`: the `docs`
view, the language, the root `base_url`, the brand, container `7`, the system
theme, the documentation version, the redirects file, search turned on and
the reader panel with the theme field. These are not hidden defaults — they
are visible in the project file and can be changed.

## A docara.json example

```json
{
  "schema": "docara.site.v1",
  "title": "My project",
  "preset": "docs",
  "content_root": "content/en",
  "framework_lock": "simai-framework.lock.json",
  "default_locale": "en",
  "locales": {
    "missing_page_policy": "skip",
    "en": {
      "label": "English",
      "direction": "ltr",
      "content_root": "content/en",
      "public_prefix": "en",
      "fallbacks": []
    }
  },
  "locale_routing": {
    "strategy": "prefixed",
    "root": "redirect",
    "detect_browser_language": false,
    "legacy_unprefixed_redirects": true
  },
  "documentation_version": "current",
  "redirects_file": "redirects.json",
  "base_url": "/",
  "branding": {
    "title": "My project",
    "mode": "compact",
    "size": "medium",
    "logo": "assets/logo.svg",
    "favicon": "assets/favicon.ico"
  },
  "layout": {
    "key": "docara.docs",
    "container": { "max": 7 },
    "content": { "gap": 0 },
    "scrollbar": { "preset": "overlay" }
  },
  "settings": {
    "theme": "system",
    "modal_blur": "none",
    "ui_radius": "default"
  },
  "examples": {
    "fullscreen": true,
    "wrap": true
  },
  "code": {
    "wrap": true
  },
  "reader_preferences": {
    "enabled": true,
    "view": "menu",
    "groups": [
      {
        "id": "appearance",
        "fields": ["appearance.theme", "appearance.font_size", "appearance.content_width"]
      }
    ]
  },
  "search": { "enabled": true, "indexed": true },
  "reading": {
    "breadcrumbs": true,
    "toc": true,
    "mobile_toc": "auto",
    "toc_depth": 3,
    "previous_next": true
  }
}
```

A section, for example `content/en/guides/section.json`:

```json
{
  "schema": "docara.section.v1",
  "title": "Guides",
  "branding": { "label": "User guide" },
  "layout": { "container": { "max": 6 } },
  "search": { "indexed": true },
  "reading": { "toc_depth": 4 }
}
```

A page, for example `content/en/guides/install.page.json`:

```json
{
  "schema": "docara.page.v1",
  "title": "Installation",
  "description": "How to install the project.",
  "slug": "guides/install",
  "navigation": { "order": 20 },
  "reading": { "previous_next": false }
}
```

## Inheritance order

For `content/guides/install.md` the layers apply in one order:

:::steps
1. Docara's built-in values.
2. `docara.json`.
3. An optional root `section.json`.
4. `content/section.json`.
5. `content/guides/section.json`.
6. `content/guides/install.page.json`.
7. Markdown as the content.

:::

A later layer replaces an earlier one only where it explicitly holds a value;
a closer level overrides only an allowed field, and merging objects does not
make unknown keys acceptable.

| Input | Result |
| --- | --- |
| The field is absent | The inherited value is kept |
| An object with some of the keys | Objects merge recursively |
| An array | The previous array is replaced entirely |
| A scalar | The previous value is replaced |
| `{}` or `[]` in a styling branch | A schema error |
| `{"$reset": true}` | The branch is cleared to an empty object |
| Reset together with other keys | The branch is cleared and filled again |

To get back the value of a higher level, remove the field on the closer
level. Do not copy the whole resulting object down: it hides where the value
came from and gets in the way of future default updates.

The exception is the structural part of `layout`: after a clear Docara
returns the registered `layout.key` and the full `layout.regions` map,
otherwise the page would have no checkable frame. After a reset
`layout.container` takes the built-in value `{"max": 7}` unless a new one is
set next to it. The author's layout values are removed.

The same hierarchy applies to `examples.fullscreen`, `examples.wrap` and
`code.wrap`. `fullscreen` turns on the responsive check mode of an isolated
example; its name is kept for compatibility. The `fullscreen` and `wrap`
parameters of a particular `:::example` apply after the resulting
configuration and control only that block.

## Inheritance and reset examples

### Partial override

The root brand can hold title, label and logo. A section changes only the
label:

```json
{
  "schema": "docara.section.v1",
  "branding": { "label": "API" }
}
```

Title and logo keep being inherited.

### Reset with new values

To remove the inherited brand files and keep a text name:

```json
{
  "schema": "docara.page.v1",
  "branding": {
    "$reset": true,
    "title": "Print version"
  }
}
```

The resulting page no longer has the inherited `logo`, `logo_dark`,
`favicon` and `label`.

### Reset without values

```json
{
  "schema": "docara.page.v1",
  "navigation": { "$reset": true }
}
```

In the resulting plan the `navigation` branch becomes an empty JSON object,
and the origin of the branch itself names the page file. Values from earlier
layers disappear. At render time the absence of a specific value means safe
behaviour: the page is not hidden and has no explicit order. This differs
from the plain absence of the whole branch in the page file, where earlier
values keep being inherited.

### Search

A section excludes pages from the index:

```json
{
  "schema": "docara.section.v1",
  "search": { "indexed": false }
}
```

One page clears this branch and sets a complete new result:

```json
{
  "schema": "docara.page.v1",
  "search": {
    "$reset": true,
    "enabled": true,
    "indexed": true
  }
}
```

If you write only `{"indexed": true}` without a reset, `enabled` keeps being
inherited.

### Reading context

```json
{
  "schema": "docara.page.v1",
  "reading": {
    "$reset": true,
    "breadcrumbs": true,
    "toc": true,
    "toc_depth": 2,
    "previous_next": false
  }
}
```

The reset touches only `reading`; branding, layout, search and navigation are
inherited independently.

### Layout regions

A section turns off the right outline for all children:

```json
{
  "schema": "docara.section.v1",
  "layout": {
    "regions": {
      "outline": { "enabled": false }
    }
  }
}
```

One page can turn it on again:

```json
{
  "schema": "docara.page.v1",
  "layout": {
    "regions": {
      "outline": { "enabled": true }
    }
  }
}
```

The inherited `sections` list is kept. If `sections` is given explicitly, the
new array replaces the previous composition entirely. Details are in
[Layout regions](/en/settings/layout-and-regions/).

[A live example of site, section and page composition](/ru/examples/composition-inheritance/)
(in Russian) shows an inherited brand, a replaced sidebar, an aside turned off
by a section, its reset on the page level and a footer turned on for one page
only.

## Where a value came from

After the build open the receipt next to the result:

```text
build_<environment>.docara/resolved-page-plans.json
```

Find the page by `output` or `url`, then:

1. look at the result in `resolved_page_plan.configuration`;
2. take the JSON Pointer of the field, for example `/layout/container/max`;
3. find the same pointer in `provenance`;
4. check the source named in `trace` and its SHA-256.

`@defaults` means a built-in value. `docara.json`, `section.json` or a page
file name the exact owning file. If a pointer is missing after a reset, the
value really is removed; the pointer of the cleared branch itself stays and
names the file that did the reset. The receipt fields are described in the
[«Схемы JSON»](/ru/reference/schemas/#план-страницы-после-сборки) reference
(in Russian).

For one page the same is shown by a command:

```bash
docara inspect page /en/start/quick-start/ --json
docara build production --page=/en/start/quick-start/
```

## When a full build is needed

A change of languages, addresses, the menu, shared `lang.json` labels, the
schema or `section.json` needs a full build: the menu, search, page links and
several child pages change at once. An ordinary edit of the text or settings
of one page allows rebuilding one page (`build --page`) while the full build
receipt is kept.

## See also

- [docara.json](/ru/reference/docara-json/) (in Russian) — all site fields
- [section.json and page.json](/ru/reference/section-and-page/) (in Russian) — section and page fields
- [Brand and theme](/en/settings/branding-and-theme/)
