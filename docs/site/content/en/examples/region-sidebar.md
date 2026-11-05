---
description: "How to add a short hint with a link under the left menu: the second part of the sidebar region in page.json, without HTML."
---

# A hint under the menu

A short block appears under the left menu: a hint and a link to the right
page. This way a reader who came to the page from search does not get lost.

::::example {src="/en/demonstrator-results/region-sidebar/" label="Result"}
### page.json

:::code {src="../demonstrator-results/region-sidebar.page.json" lang=json highlight="11-29"}
:::

### Page Markdown

:::code {src="../demonstrator-results/region-sidebar.md" lang=markdown}
:::
::::

Click "Check responsive preview" above the example and choose a phone. The menu
moves behind a button in the header, and the hint goes with it.

## How it works

1. The `sidebar` region gets two parts. The first, `docara.navigation`, is
   the regular site menu.
2. The second, `docara.shell`, is an empty frame for your own blocks. It holds two
   elements: a paragraph `p` and a link `a`.
3. Elements are set with fields, not HTML. Docara writes the markup itself and does not let you
   insert a script or a foreign address.

:::code {src="../demonstrator-results/region-sidebar.page.json" lang=json lines="11-29" highlight="10,16" dedent=true}
:::

## What you can change

| Parameter | What it changes |
|---|---|
| `tag` | The element type: `p` for a paragraph, `a` for a link, `span` or `div` |
| `text` | The element text, up to 500 characters |
| `href` | The link address: a path inside the site or `#anchor` |
| `aria_label` | The link name for screen readers |
| `utilities` | SIMAI Framework classes for spacing and background, up to 16 |

To show the hint on every page of a section, move `layout` to
that section's `section.json`.

## See also

- [Header, menu and page outline](../regions-composed/)
- [A note next to the page outline](../region-aside/)
- [Layout regions](/en/settings/layout-and-regions/)
