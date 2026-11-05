---
description: Site search in the browser — turning it on, excluding pages, what goes into the index, synonyms through page tags, a link that opens search, and what to do if search does not work.
tags: [search, index, synonyms, tags]
---

# Search

Docara builds the search index together with the static site. Search runs in
the browser, sends no queries to an outside service and needs no server
database.

## Turning it on

Add the settings to `docara.json`:

```json
{
  "schema": "docara.site.v1",
  "search": {
    "enabled": true,
    "indexed": true
  }
}
```

- `enabled` shows the search button and window on the pages of the current
  area;
- `indexed` adds the pages of the area to the index.

Both settings are inherited through `section.json` and the page file. The
branch supports `$reset` just like branding, layout, navigation and settings
(see the [example](/en/settings/levels-and-inheritance/#search)). A change of
the search settings needs a full build.

## Excluding pages

To keep a section out of the results, set in its `section.json`:

```json
{
  "schema": "docara.section.v1",
  "search": { "indexed": false }
}
```

`navigation.hidden` and `search.indexed` solve different tasks. A page can
take no place in the menu but stay reachable by a direct link and through
search. Neither setting is access control: the built HTML page and the JSON
index itself stay public static files. Do not publish secret or private data
in a portable site.

If search is on for a locale but all its pages are excluded, the build stops
with `SEARCH_INDEX_LOCALE_EMPTY` before it clears the previous output.

## What is indexed

The index holds:

- the page title and description;
- the `tags` from the top of the page;
- the section path from the same tree the menu uses;
- the Markdown headings;
- the visible text of paragraphs, lists, tables and code blocks;
- the text attributes of Smart components: `title`, `text`,
  `supporting-text`, `label` and `description`.

The text comes from the finished page, so no separate component list for
search is needed. `script`, `style`, `template`, hidden nodes (`hidden`,
`aria-hidden="true"`) and technical parameters are not indexed. The menu, the
page outline, breadcrumbs and page-to-page links are built from the same page
tree as the section path in the index.

## Synonyms: page tags

Search finds a page by the words it contains. If readers search with another
word, for example "deploy" instead of "publishing" or "icons" instead of
"glyph", add these words to the `tags` at the top of the page:

```markdown
---
tags: [deploy, publishing, release]
---

# Publishing the site
```

Tags go into the search index. A page whose tag matches the query ranks
above pages where the word appears only in the text or subheadings, but
below a page with the word in its title. The tags are not visible on the
page itself.

A tag is one word or several words joined with `-`: lowercase letters of any
alphabet, digits, `.`, `_` and `-`, for example `deploy`, `i18n` or
`material-symbols`. Capital letters and spaces stop the build with
`FRONT_MATTER_TAG_INVALID`. Search compares the start of a word, so the tag
`icons` also matches the query "icon", but `icon` does not match "icons": add
the word forms you need as separate tags.

## In the browser

The search window is the SIMAI Framework `sf-search` component. Docara gives
it its own results: pages in the current language, ranked by title, tags,
headings and text. The index loads only on the first query. Press the
"Search" button, `Cmd/Ctrl + K` or `/`, type at least two characters and use
the arrows and Enter to go to a result. On a wide screen the words around the
match show beside the list. The window remembers recent queries in the
browser. Escape first clears the query and, on an empty field, closes the
window and returns focus to the button.

### A link that opens search

To make a link in the text open the search window, write `#docara-search` in
Markdown instead of an address:

```markdown
Did not find the answer? [Open search](#docara-search).
```

Any link can be written this way: in a paragraph, in a list, a button in a
Hero or a CTA. The build turns it into a link to the language's home page
with the `data-docara-search-open` attribute; by it `_docara/search.js` opens
the search window, like the "Search" button. Search does not work without
JavaScript, so the link leads to the home page. If search is off on the page
(`search.enabled: false`), it is an ordinary link to the home page. The
built-in [404 page](/en/build/hosting/#the-404-page) has the same
link.

Search normalizes case, Unicode and the Russian `е/ё`. Results are ranked by
title, `tags`, headings, description and main text. This is local full-text
search with prefix matching; it does not yet claim typo correction,
morphology or semantic AI search.

## If search does not work

1. Make sure `search.enabled: true` reached the resolved page plan.
2. Check that `_docara/search-index.json` and `_docara/search.js` exist.
3. Run `php vendor/bin/docara verify-static build_production`.
4. Fix the first search error code and rebuild the site.

The "No results found" message means a valid empty result. The "Search is
temporarily unavailable" message means the index did not load or broke the
pinned contract.

## See also

- [Page metadata](/en/authoring/markdown/#page-metadata) — where the `tags` are set
- [Search error codes](/en/reference/errors/site/#search)
- [Menu and sections](/en/authoring/navigation/) — hidden pages and search
