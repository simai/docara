---
description: "How site, section and page settings add up on one page: what comes from above, what changes and how to reset an inherited setting."
---

# Settings on three levels

The upper levels set the name and the theme. The Payments section adds a block
under the menu and removes the page outline. One page brings the outline back with its own note and
turns on the footer.

::::example {src="/en/demonstrator-results/composition-inheritance/page/" label="Result"}
### Section section.json

:::code {src="../demonstrator-results/composition-inheritance/section.json" lang=json highlight="8-21"}
:::

### Page page.json

:::code {src="../demonstrator-results/composition-inheritance/page.page.json" lang=json highlight="7-9,22-23"}
:::

### Page Markdown

:::code {src="../demonstrator-results/composition-inheritance/page.md" lang=markdown}
:::
::::

## How it works

1. **Upper levels.** The site's `docara.json` sets the theme, and the shared
   `section.json` of the demo section sets the name "Acme Pay" and the header
   sections. That is why the Payments section and page files do not have them.
2. **Section.** `section.json` puts its own block under the menu and turns
   off the page outline (`"outline": {"enabled": false}`) for every page in the section.
3. **Page.** `"$reset": true` in the `outline` region drops what came from the
   section, and the page sets the right column again: the outline and its
   own note. The page turns on the footer only for itself.

:::code {src="../demonstrator-results/composition-inheritance/page.page.json" lang=json lines="5-21" highlight="3-5" dedent=true}
:::

Without `$reset`, the page fields would merge with the section fields. With a reset, the region
is fully described in `page.json`, and you do not need to remember what came from above.

## What you can change

| Parameter | What it changes |
|---|---|
| `$reset` | Drops the inherited setting and sets it again |
| `enabled` | Whether the region is shown |
| `sections` | What the region holds, top to bottom |

## See also

- [Shared section settings with a page exception](../inheritance/)
- [Page footer](../region-footer/)
- [Site, section and page](/en/settings/levels-and-inheritance/)
