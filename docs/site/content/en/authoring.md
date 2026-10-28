# Writing pages

In Docara the project files are the source of truth. Markdown holds the text
and structure of a page, and JSON files hold the settings of the site, a
section and a single page. The build turns them into HTML, a search index and
receipts for the checks.

:::card
## A simple rule

Start with plain Markdown. Add JSON only for appearance and navigation. Use a
component only when Markdown is not enough, and copy the exact call from the
component's page.

:::

## The author's path

1. [Write the page in Markdown](/en/authoring/markdown/): headings, lists,
   links, tables, code and page metadata.
2. [Place the pages in the menu](/en/authoring/navigation/): sections, order,
   hidden pages and the top menu.
3. For several languages, build a
   [multilingual site](/en/authoring/multilingual-site/).
4. When a page address changes,
   [declare a redirect](/en/authoring/redirects/).
5. If you like, turn on the checks of
   [page profiles](/en/authoring/page-profiles/) and
   [documentation freshness](/en/authoring/documentation-tracking/).
6. If Markdown is not enough, [find a component](/ru/components/) (in Russian).
7. Check that the pages [work for every reader](/en/authoring/accessibility/).
8. Set up the appearance in [Site settings](/en/settings/) and [build and check the result](/ru/build/verify/) (in Russian).

## Do not edit the build output

`build_*`, the receipts in `build_*.docara/` (`resolved-page-plans.json`,
`redirects.json` and others) and `_docara/component-catalog.json` are created
anew on every build. If the result is wrong, fix the source Markdown or JSON
and build again.

## Documentation and landing pages

Both kinds of pages — documentation (`docs`) and landing (`landing`) — use one
content format, one SIMAI Framework release and one set of safety rules. Only
the frame differs: a documentation page with a menu and an outline, or a
focused page without them. More in
[Layout regions](/en/settings/layout-and-regions/).
