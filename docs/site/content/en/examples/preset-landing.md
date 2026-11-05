---
description: "How to make a product landing page without the documentation menu: the landing preset, a hero, cards and a call-to-action button."
---

# Product landing page

A product home page does not look like documentation: it has no menu and no
outline, but it has a large hero, feature cards and a call-to-action
button. The same Docara builds it from the same Markdown.

::::example {src="/en/demonstrator-results/preset-landing/" label="Result"}
### page.json

:::code {src="../demonstrator-results/preset-landing.page.json" lang=json highlight="5"}
:::

### Page Markdown

:::code {src="../demonstrator-results/preset-landing.md" lang=markdown}
:::
::::

## How it works

1. `"preset": "landing"` removes the menu and the outline and gives the page the full
   width.
2. `:::hero` is the first screen: a title, a short text and the main button.
3. `:::features` turns a list into cards, and `:::cta` turns a link into a button.
4. Search is turned off on this page: a landing page does not need it.

:::code {src="../demonstrator-results/preset-landing.md" lang=markdown lines="5-12"}
:::

## What you can change

| Parameter | What it changes |
|---|---|
| `preset` | The page view: `landing` or `docs` |
| `layout.container.max` | Content width, from 1 to 8 |
| `search.enabled` | Search in the header |
| `search.indexed` | Whether the page appears in search results |

## See also

- [Hero](/en/components/hero/)
- [Landing blocks](/en/components/landing-blocks/)
- [Documentation page](../preset-docs/)
