---
description: How Docara builds the menu from files and folders — the section page, order and hidden pages, the site's top menu, tree depth and behaviour on a narrow screen.
tags: [menu, navigation, sections, order, hidden]
---

# Menu and sections

Docara builds the left menu from the structure of the Markdown files. There is
no separate file listing the items: add a page and it appears in the menu,
move a file and its place in the tree changes.

## How the tree is built

The hierarchy comes from folders and Markdown files, not from the public
`slug`. A section page and the folder of the same name join into one item:
the link opens the section overview, and a separate button expands the pages
inside it.

```text
content/
  guides.md
  guides/
    install.md
    configuration.md
    configuration/
      theme.md
      advanced/
        overrides.md
```

You can use `guides/index.md` instead of `guides.md`, but not both at once. A
folder without an overview page stays a group without a false link.

The menu shows up to four nesting levels. If a page lies deeper, the build
stops with `DECLARATIVE_NAVIGATION_DEPTH_EXCEEDED`: move it one level up or
merge sections.

## Order and visibility

The order and visibility of an item are set in the neighbouring
`<page>.page.json` file, and for a section page in the overview's file, for
example `guides.page.json`:

```json
{
  "schema": "docara.page.v1",
  "navigation": { "order": 20 }
}
```

- `navigation.order` is a non-negative integer; a smaller value stands higher
  among its neighbours;
- a page without `order` comes after the pages with an explicit `order`;
- on a tie the stable path of the source file decides;
- `navigation.hidden: true` removes the page from the menu.

Set `order` explicitly on every page of a section: otherwise the order depends
on the file names, which rarely match the reading order.

A hidden page stays public HTML and opens by a direct link. To remove it from
search too, also set `search.indexed: false`. If a hidden page has visible
children, the branch stays in the menu as a group without a link. Neither
`hidden` nor `indexed` closes access to the page.

## The site's top menu

Set the key links of the header in the root `content/<locale>/section.json`.
The labels belong to one language, so each language version can have its own
items and order:

```json
{
  "schema": "docara.section.v1",
  "header_navigation": {
    "enabled": true,
    "items": [
      {
        "id": "guide",
        "label": "Guide",
        "href": "/en/start/",
        "sections": ["/en/start/", "/en/authoring/", "/en/settings/", "/en/build/"]
      },
      {"id": "components", "label": "Components", "href": "/en/components/"}
    ]
  }
}
```

`id` is a stable technical identifier, `label` the localized label, and
`href` an internal path, an anchor or a safe HTTPS address. No more than eight
single-level items are allowed.

To show a repository icon on the right of the header, last after search, language and settings, add a
`repository` field with an `https://…` address to `header_navigation`:

```json
{
  "header_navigation": {
    "enabled": true,
    "repository": "https://github.com/simai/docara",
    "items": [{"id": "home", "label": "Home", "href": "/en/"}]
  }
}
```

For an address on github.com Docara shows the GitHub logo, for other
addresses a general code icon. The label for screen readers comes from the
`navigation.repository` string in `lang.json`. The icon is there only while
`repository` is set: to hide it on the whole site, remove the field. A nested
section inherits the field; to remove the icon only there, set
`header_navigation` with `"$reset": true` and the other fields without
`repository`.

The optional `sections` groups several first-level sections under one item
without changing their addresses. The item is marked on the pages of each of
them, and the side panel shows them together, each as its own group. This way
the header keeps two or three items even when there are more sections. To
turn the menu off in a nested section, set
`"header_navigation": {"enabled": false}`. To replace the inherited list, pass
a new `items`: lists are inherited as a whole, not by index.

A top menu item is marked not only on its own page but on every page of its
section. At build time Docara picks one item: first the one that stands
highest in the section chain of the current page in the navigation, and if
there is none, the item with the longest matching address start. The home
page item is marked only on the home page itself. The current page gets
`aria-current="page"` and the section item `aria-current="true"`; both look
like the active item of the left menu.

The site header is the SIMAI Framework `sf-header-site` component, one header
for every SIMAI site. On a wide screen the top menu sits between the brand and
the header buttons: search, language, settings and the repository. Below
1152 px (the Framework `xl` step) it neither shrinks nor gets cut: a menu
button appears before the brand, and the links move into the "Navigation"
side panel, which starts with a "Main" group followed by the documentation
tree. Without a top menu the button appears below 960 px (the `lg` step), when
the left menu hides. On a landing page without a tree the same panel holds
only the key links. The panel closes with its button, with Escape, with a
click outside it or when a link is chosen, and remembers how far it was
scrolled.

## The whole tree or one section

`navigation.scope=section` leaves in the side panel only the pages of the
first-level section the reader is in. `navigation.scope=site` shows the whole
site tree.

If the site has a top menu, `section` is the default: the reader picks a
section at the top and sees only its pages on the left. So put the main
sections into the top menu. Without a top menu the default is `site`. An
explicit `navigation.scope` wins over this rule.

A section hidden from the site menu (`navigation.hidden: true` in its
`section.json`) — for example a demo site for examples — shows the menu of
the whole site on its pages. To let its pages see only each other, add
`"scope": "section"` to the same `section.json`:

```json
{
  "schema": "docara.section.v1",
  "navigation": {"hidden": true, "scope": "section"}
}
```

The pages of such a section follow their `order` in the side panel, and the
section still stays out of the site menu.

The top menu, breadcrumbs, search and page-to-page links keep using the full
tree.

## The current page and a narrow screen

The current link gets `aria-current="page"`, and all its ancestors expand
automatically. On a wide screen the tree is fixed on the left. On a narrow
one it opens over the article in a "Navigation" window, so the content does
not shift. Escape, the close button and a click on the dimmed background close
the panel and return focus to the button that opened it.

Breadcrumbs, the page outline and the "Previous" and "Next" links are built
from the same tree, so the order never diverges; they are described in
[Reading context](/en/settings/reading/). A landing page
(`preset: landing`) does not show them.

The menu is drawn by one Smart component, `docara.navigation`, in three
views: `header` (the top menu), `tree` (the left tree) and `compact` (the
mobile panel). Site settings do not pick a template, class or rendering
method for the menu; how to replace or extend the menu in a layout region is
described in [Layout regions](/en/settings/layout-and-regions/).

## After a change

After you add, rename or remove a page or change `order`, `hidden` or the top
menu items, run a full build: the menu, search, breadcrumbs and page-to-page
links are shared derived data of all pages. When you rename a page, add a
[redirect](/en/authoring/redirects/) from the old address.

## See also

- [Reading context](/en/settings/reading/) — breadcrumbs and page-to-page links
- [Redirects](/en/authoring/redirects/)
- [The navigation and header_navigation fields](/en/reference/section-and-page/)
