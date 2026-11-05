---
description: "How to put a SIMAI Framework button in the page text: a ui.button call with a label and a preset right in Markdown."
---

# A button in the page text

A button in the SIMAI Framework style appears in the text. It is set in Markdown
with three lines, without HTML.

::::example {src="/en/demonstrator-results/smart-button/" label="Result"}
### Page Markdown

:::code {src="../demonstrator-results/smart-button.md" lang=markdown highlight="10-12"}
:::
::::

## How it works

1. `:::ui.button` calls the button from SIMAI Framework.
2. `text` is the label, `preset` a ready button look. The other parameters
   come from the view.
3. This is a button, not a link: it does not go anywhere by itself. To go to another
   page, use the [Docara link button](/en/components/button/).

## What you can change

| Parameter | What it changes |
|---|---|
| `text` | Label, up to 120 characters |
| `preset` | Ready look: `primary`, `secondary`, `outline`, `link` and their `_on_surface` variants |
| `size` | Size: `1/3`, `1/2`, `1`, `2` or `3` |
| `disabled` | The button cannot be clicked |
| `aria-label` | The button name for screen readers |

## See also

- [A header with an action button](../region-header/)
- [An alert in the page text](../smart-alert/)
- [SIMAI Framework components](/en/components/smart-ui/)
