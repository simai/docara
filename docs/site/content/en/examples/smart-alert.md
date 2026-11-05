---
description: "How to put a prominent SIMAI Framework alert in the page text: a ui.alert call with parameters right in Markdown."
---

# An alert in the page text

A prominent alert appears in the middle of the text: here, a warning that in
test mode no money is charged. It is set in Markdown, without HTML.

::::example {src="/en/demonstrator-results/smart-alert/" label="Result"}
### Page Markdown

:::code {src="../demonstrator-results/smart-alert.md" lang=markdown highlight="10-12"}
:::
::::

## How it works

1. `:::ui.alert` calls the alert from SIMAI Framework.
2. The next line holds the parameters in JSON. Docara checks them during the build:
   an unknown parameter or a wrong value stops the build.
3. The alert follows the site theme.

## What you can change

| Parameter | What it changes |
|---|---|
| `type` | Meaning: `info`, `success`, `warning`, `danger` or `clear` |
| `variant` | Look: `default`, `flat` or `outlined` |
| `title` | Title, up to 160 characters |
| `supporting-text` | Text, up to 500 characters |
| `closable` | Whether the alert can be closed |
| `aria-label` | The alert name for screen readers |

For simple notes without SIMAI Framework there is the [Alert block](/en/components/alert/).

## See also

- [A note next to the page outline](../region-aside/)
- [A button in the page text](../smart-button/)
- [SIMAI Framework components](/en/components/smart-ui/)
