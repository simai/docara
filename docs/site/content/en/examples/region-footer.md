---
description: "How to add a footer with text and a link at the bottom of a page: the footer region in page.json, without HTML."
---

# Page footer

A footer appears at the bottom of the page: a line about the company and a link. By default
there is no footer; the page turns it on itself.

::::example {src="/en/demonstrator-results/region-footer/" label="Result"}
### page.json

:::code {src="../demonstrator-results/region-footer.page.json" lang=json highlight="7-30"}
:::

### Page Markdown

:::code {src="../demonstrator-results/region-footer.md" lang=markdown}
:::
::::

Scroll the page in the example window to the end to see the footer.

## How it works

1. The `footer` region is off on every page. `"enabled": true` turns it on.
2. The footer has one `docara.shell` part with two elements: a paragraph and a
   link.
3. Docara adds the line above the footer and the spacing itself, and the footer text starts
   where the menu items start. You need no CSS of your own.

:::code {src="../demonstrator-results/region-footer.page.json" lang=json lines="7-30" highlight="2,7" dedent=true}
:::

## What you can change

| Parameter | What it changes |
|---|---|
| `enabled` | Whether the footer is shown |
| `tag` | The element type: `p`, `a`, `span` or `div` |
| `text` | The element text, up to 500 characters |
| `href` | The link address: a path inside the site or `#anchor` |
| `utilities` | SIMAI Framework classes for spacing, background and placement |

To show the footer on every page, move `layout` to `docara.json`.

## See also

- [A hint under the menu](../region-sidebar/)
- [Settings on three levels](../composition-inheritance/)
- [Layout regions](/en/settings/layout-and-regions/)
