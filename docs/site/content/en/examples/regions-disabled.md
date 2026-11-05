---
description: "How to remove the menu and the page outline from a long text page so the text takes the full width."
---

# A page without side columns

For a long document such as terms, a policy or a contract, side columns only
get in the way. The page turns off the menu and the outline, and the text takes the full width.

::::example {src="/en/demonstrator-results/regions-disabled/" label="Result"}
### page.json

:::code {src="../demonstrator-results/regions-disabled.page.json" lang=json highlight="6-9"}
:::

### Page Markdown

:::code {src="../demonstrator-results/regions-disabled.md" lang=markdown}
:::
::::

## How it works

1. `"enabled": false` turns off a region. Docara leaves no empty space
   in its place.
2. `container.max` sets the text column width on the SIMAI Framework scale from
   1 to 8. Here 7 is a wide column.
3. The header stays: it holds the name, search and settings.

## What you can change

| Parameter | What it changes |
|---|---|
| `sidebar.enabled` | The left menu |
| `outline.enabled` | The outline on the right |
| `container.max` | Text column width: from 1, narrow, to 8, full screen |

## See also

- [Header, menu and page outline](../regions-composed/)
- [Shared section settings with a page exception](../inheritance/)
- [Layout regions](/en/settings/layout-and-regions/)
