---
description: "What a regular Docara documentation page looks like: the menu and the page outline with one line in page.json."
---

# Documentation page

The `docs` preset is a regular documentation page: a header, a menu on the left and a page outline
on the right. Every page of the
site gets it unless something else is set.

::::example {src="/en/demonstrator-results/preset-docs/" label="Result"}
### page.json

:::code {src="../demonstrator-results/preset-docs.page.json" lang=json highlight="5"}
:::

### Page Markdown

:::code {src="../demonstrator-results/preset-docs.md" lang=markdown}
:::
::::

## How it works

1. `"preset": "docs"` picks the whole page view: which regions exist and
   how they are arranged.
2. You do not have to write it: `docs` is the default preset. It is set here
   to show where the preset is chosen.
3. Everything else on the page is regular Markdown.

## What you can change

| Parameter | What it changes |
|---|---|
| `preset` | The page view: `docs` for documentation, `landing` for a landing page |

## See also

- [Product landing page](../preset-landing/)
- [Header, menu and page outline](../regions-composed/)
