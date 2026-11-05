---
description: "How to put an important warning under the page outline on the right: a SIMAI Framework alert in the second part of the outline region."
---

# A note next to the page outline

On the right, under the page outline, there is a warning the reader sees while
scrolling the page. It suits rules that must not be missed.

::::example {src="/en/demonstrator-results/region-aside/" label="Result"}
### page.json

:::code {src="../demonstrator-results/region-aside.page.json" lang=json highlight="11-32"}
:::

### Page Markdown

:::code {src="../demonstrator-results/region-aside.md" lang=markdown}
:::
::::

Click "Check responsive preview" above the example and choose a phone. There is no right
column there, and the note is not shown with it. Put a warning
that everyone must see in the page text.

## How it works

1. The `outline` region is the right column. The first part, `docara.outline`,
   builds the outline from the page headings.
2. The second part holds the SIMAI Framework `ui.alert` alert. Docara
   checks its parameters during the build.
3. The alert follows the site theme: in the dark theme it is dark too.

:::code {src="../demonstrator-results/region-aside.page.json" lang=json lines="16-30" highlight="5-13" dedent=true}
:::

## What you can change

| Parameter | What it changes |
|---|---|
| `type` | The alert meaning: `info`, `success`, `warning`, `danger` or `clear` |
| `variant` | The look: `default` filled, `flat`, or `outlined` with a border |
| `title` | Title, up to 160 characters |
| `supporting-text` | Text, up to 500 characters |
| `closable` | Whether the alert can be closed |
| `aria-label` | The alert name for screen readers |

## See also

- [A hint under the menu](../region-sidebar/)
- [An alert in the page text](../smart-alert/)
- [SIMAI Framework components](/en/components/smart-ui/)
