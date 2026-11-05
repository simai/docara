---
description: Docara builds breadcrumbs, the page outline and page-to-page links automatically from the project structure and the article headings.
tags: [breadcrumbs, outline, toc, anchors, page-links]
---

# Reading context

The reading context answers three questions: where the page is, what is
inside it and where to go next. Docara builds it automatically from the
structure of the Markdown project and the article headings.

## What appears on the page

The `docs` preset has these settings:

| Field | Default | What it does |
| --- | --- | --- |
| `reading.breadcrumbs` | `true` | Shows the path from the home page to the current page |
| `reading.toc` | `true` | Shows an outline built from the article headings |
| `reading.mobile_toc` | `"auto"` | Controls the mobile outline button: `auto`, `always` or `never` |
| `reading.toc_depth` | `3` | Includes headings from H2 to the given level, 2–6 allowed |
| `reading.previous_next` | `true` | Shows the neighbouring pages in tree order |

The page contents is the SIMAI Framework `sf-contents` component. On a wide
screen it is a column on the right that marks the section you are reading.
Below 1152 px the `auto` mode shows a button with the current section above
the article, which opens the list, only if the outline has at least four items
or a nested H3–H6 heading. A short flat page does not spend the first screen
on an extra element.

"Previous" and "Next" below the article are the two cards of the SIMAI
Framework `sf-pager` component.

`always` keeps the mobile button whenever the outline is not empty, and
`never` hides it whatever the page length. These modes do not affect the
fixed outline of the desktop version.

If the path has more than three levels, Docara keeps the root and the current
page visible and folds the middle levels into a button with an ellipsis. It
expands the full chain without leaving the page. Before JavaScript loads and
when it is off, the whole server-side chain stays available and scrolls
inside its own area on a narrow screen.

Folding uses the base `breadcrumbs` component of SIMAI Framework. Docara
keeps no separate copy of the path and creates no component of its own: the
source of the levels stays one with the section tree and the page links.

The `landing` preset does not show breadcrumbs, the outline or page links:
these belong to reading documentation, not to a landing page.

## Site and section settings

Usually it is enough to turn the context on once in `docara.json`:

```json
{
  "schema": "docara.site.v1",
  "reading": {
    "breadcrumbs": true,
    "toc": true,
    "mobile_toc": "auto",
    "toc_depth": 3,
    "previous_next": true
  }
}
```

A section can change only the field it needs and keep the other values:

```json
{
  "schema": "docara.section.v1",
  "reading": { "toc_depth": 4 }
}
```

## Settings of one page

To keep stable links to headings but remove all the helper surfaces, add to
`<page>.page.json`:

```json
{
  "schema": "docara.page.v1",
  "reading": {
    "breadcrumbs": false,
    "toc": false,
    "previous_next": false
  }
}
```

Docara still gives the headings deterministic `id`s. While the text and order
of the headings stay the same, a link to a section of the article keeps
working even with the outline hidden. Renaming a heading changes the
generated anchor, so check incoming deep links.

## How anchors are built

All H1–H6 headings get a deterministic Unicode anchor. The text is
lowercased, and spaces and punctuation turn into hyphens:

```text
## Installation and setup
→ #installation-and-setup
```

If the same heading repeats, Docara adds the suffix `-1`, `-2` and so on. A
heading without letters or digits gets the base `section`. The outline holds
only H2–H6 within `toc_depth`; the H1 stays the page title.

In Markdown you can link to the resulting section with an ordinary local
link:

```markdown
[Go to the setup](#installation-and-setup)
```

The static build check refuses a link to a missing fragment and a page with a
repeated HTML `id`.

## Page order

Breadcrumbs and page links use one canonical topology:

1. the folder structure sets the nesting;
2. `navigation.order` sets the order of neighbours;
3. on a tie the stable source path decides;
4. pages of another language and the `landing` preset do not become document
   neighbours;
5. a hidden page stays part of the path but does not become a link target.

`navigation.hidden: true` hides a page only from the global menu. If a hidden
page has visible children, the branch stays in the menu as a group without a
false link. Thanks to the shared topology the breadcrumbs of a hidden page
keep its real path, and page links find the nearest visible neighbours.

The home page does not show breadcrumbs of a single item. A page with only an
H1 does not show an empty outline, but the H1 still gets an anchor.

## When to turn elements off

- Turn off `toc` if the page is short and has only one heading.
- Use `mobile_toc: "never"` if mobile navigation is not needed even on a long
  page, or `"always"` if constant quick access matters to the author.
- Lower `toc_depth` if deep technical subsections overload the right area.
- Turn off `previous_next` for a standalone reference page where the reading
  order does not matter.
- Turn off `breadcrumbs` only if the page itself already makes the path
  clear; the left tree stays available.

A change of the `reading` settings for the site or a section needs a full
build. The resulting values and their sources can be checked in the
[page plan after the build](/en/reference/schemas/#the-page-plan-after-the-build).

## See also

- [Menu and sections](/en/authoring/navigation/) — page order in the tree
- [Reader panel](/en/settings/reader-preferences/) — the "Reading mode" and "Hide page contents" buttons
- [The reading field](/en/reference/section-and-page/)
