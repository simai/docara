---
description: Docara site settings — setting levels, the site address, brand and theme, layout regions, search, reading context and the reader panel. Where to start and how to check the result.
tags: [settings, configuration, docara-json]
---

# Site settings

Docara settings are checked JSON files on three levels: the site
(`docara.json`), a section (`section.json`) and a page (`<page>.page.json`).
Start from the task, change only the project file you need and check the
resulting page plan.

## Sections

- [Site, section and page](/en/settings/levels-and-inheritance/) — what is
  set on each level, inheritance and reset, where a value came from.
- [Site address and search engines](/en/settings/site-address/) —
  `base_url`, `site_url`, `sitemap.xml`, `robots.txt` and `llms.txt`.
- [Brand and theme](/en/settings/branding-and-theme/) — logo, name, favicon,
  colours and the light or dark scheme.
- [Layout regions](/en/settings/layout-and-regions/) — the page view, width,
  scrollbars, header, menu, outline and footer.
- [Search](/en/settings/search/) — the index, excluding pages and synonyms.
- [Reading context](/en/settings/reading/) — breadcrumbs, the page outline and
  page-to-page links.
- [Reader panel](/en/settings/reader-preferences/) — the theme, font size and
  text width the reader picks.

Languages and translations are described in
[Multilingual site](/en/authoring/multilingual-site/), the menu in
[Menu and sections](/en/authoring/navigation/). The full list of fields with
types and defaults is in the reference:
[docara.json](/en/reference/docara-json/) and
[section.json and page.json](/en/reference/section-and-page/).

## A safe order of work

1. Find the field in the reference and check on which level it can be set.
2. Change `docara.json`, the nearest `section.json`, the page file or the
   Markdown metadata — only as the schema allows.
3. Run `docara doctor`, a single-page build `build --page` and
   `verify-static`; when the shared structure changes, run a full build.
4. If a value is not what you expected, find its source in the
   [page plan](/en/settings/levels-and-inheritance/#where-a-value-came-from).
