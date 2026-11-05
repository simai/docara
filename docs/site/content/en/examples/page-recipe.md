---
description: "How to build a page from a SIMAI Framework template where one setting picks the header variant before any HTML is created."
---

# A page from a template with a header choice

The page template describes its parts, and one setting picks which header
to use: compact or expanded. Both pages below are built from the same
template files and differ only in the setting value.

This is an advanced feature for those who build pages with a program.
Regular documentation pages do not need it.

## Compact header

::::example {src="/en/demonstrator-recipes/compact/" label="Result"}
### inputs-compact.en.json

:::code {src="../../../composition/inputs-compact.en.json" lang=json highlight="5-7"}
:::

### header-compact.en.json

:::code {src="../../../composition/header-compact.en.json" lang=json}
:::

### recipe.json

:::code {src="../../../composition/recipe.json" lang=json highlight="13-21"}
:::
::::

## Expanded header

::::example {src="/en/demonstrator-recipes/expanded/" label="Result"}
### inputs-expanded.en.json

:::code {src="../../../composition/inputs-expanded.en.json" lang=json highlight="5-7"}
:::

### header-expanded.en.json

:::code {src="../../../composition/header-expanded.en.json" lang=json}
:::

### recipe.json

:::code {src="../../../composition/recipe.json" lang=json highlight="13-21"}
:::
::::

## How it works

1. `recipe.json` is the template: the header gets a fragment chosen by the
   `headerMode` value, and the main part gets the text from the `body` value.
2. The values file sets `headerMode`: `compact` or `expanded`. Everything else is
   shared by the two pages.
3. The headers are separate files. The compact one has only the title. The expanded one has
   a line about the section and API version above the title and a summary below it: the request, the deadline and
   the event that arrives with the result.
4. A Docara page points to its set of files with the
   `composition_recipe` line at the start of the Markdown. SIMAI Framework builds the page
   during the site build; nothing is computed in the browser.

:::code {src="../demonstrator-recipes/compact.md" lang=markdown highlight=4}
:::

## What you can change

| Parameter | What it changes |
|---|---|
| `headerMode` | The header variant: `compact` or `expanded` |
| `composition_recipe` | Which set of template files builds the page |

## See also

- [Header, menu and page outline](../regions-composed/)
- [Documentation page](../preset-docs/)
