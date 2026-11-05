---
description: "How to build a documentation page from a header, a left menu and a right outline: three regions in page.json, the text in Markdown."
---

# Header, menu and page outline

A regular documentation page has four parts: the header, the left menu,
the text and the outline on the right. You write the text in Markdown, and the other parts
are connected by name in `page.json`.

::::example {src="/en/demonstrator-results/regions-composed/" label="Result"}
### page.json

:::code {src="../demonstrator-results/regions-composed.page.json" lang=json highlight="7-18"}
:::

### Page Markdown

:::code {src="../demonstrator-results/regions-composed.md" lang=markdown}
:::
::::

Click "Check responsive preview" above the example and choose a phone. The menu and
the header sections move behind the button on the left of the header, and the right column with the outline
is hidden.

## How it works

1. The page is divided into regions: `header` is the header, `sidebar` the left menu,
   `outline` the right column. The Markdown text always goes to the main
   region.
2. Each region holds a ready Docara part: `docara.header` is the header with
   the name, search and settings, `docara.navigation` the site menu,
   `docara.outline` the page outline.
3. Docara builds the outline from the page's `##` and `###` headings. The menu
   is built from the site folder structure.

:::code {src="../demonstrator-results/regions-composed.page.json" lang=json lines="5-20" highlight="3,7,11" dedent=true}
:::

Every documentation page looks like this even without these lines: Docara turns on
the three regions itself. You write them out when you change what they hold, as in
the [neighbouring examples](../region-sidebar/).

## What you can change

| Parameter | What it changes |
|---|---|
| `enabled` | Whether the region is shown: `true` or `false` |
| `sections` | What the region holds, top to bottom; up to eight parts |
| `id` | The name of the part in this region, in Latin letters; it tells the parts apart |
| `section` | Which Docara part: `docara.header`, `docara.navigation`, `docara.outline` or `docara.shell` for your own blocks |

## See also

- [Layout regions](/en/settings/layout-and-regions/)
- [A menu with a hint](../region-sidebar/)
- [A page without side columns](../regions-disabled/)
