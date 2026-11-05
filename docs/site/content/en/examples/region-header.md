---
description: "How to add the main call-to-action button to the site header next to the logo: one section in page.json, without HTML."
---

# A header with an action button

The site's main button appears next to the logo: "Get a key",
"Start" or "Download". You do not need to write HTML: one section in
`page.json` is enough.

::::example {src="/en/demonstrator-results/region-header/" label="Result"}
### page.json

:::code {src="../demonstrator-results/region-header.page.json" lang=json highlight="12-29"}
:::

### Page Markdown

:::code {src="../demonstrator-results/region-header.md" lang=markdown}
:::
::::

Click "Check responsive preview" above the example and switch the width. On a
wide screen the button sits on the right, before search. On a phone the header
collapses: the button moves into the slide-out menu together with the sections.

## How it works

1. The header is made of sections. The first, `docara.header`, is the standard Docara
   header: the name, sections, search and settings.
2. The second section adds the SIMAI Framework `ui.button` button. Everything
   you add to the header, Docara places on the right, before search.
3. Docara checks the button parameters during the build. A typo in a parameter name
   stops the build with a clear error instead of silently breaking the page.

:::code {src="../demonstrator-results/region-header.page.json" lang=json lines="10-31" highlight="3-20" dedent=true}
:::

The shared `section.json` of the demo section sets the name "Acme Pay" and the header sections,
so `page.json` does not have them. On a real site they are usually set once
in `docara.json`.

## What you can change

| Parameter | What it changes |
|---|---|
| `text` | The button label |
| `preset` | The button look: `primary` for the main one, `secondary` for a secondary one |
| `aria-label` | The button name for screen readers, if the label is not enough |
| Section order | The order of elements in the header |

To show the button on every page of a section, move `layout` from
`page.json` to that section's `section.json`.

## See also

- [Layout regions](/en/settings/layout-and-regions/)
- [SIMAI Framework components](/en/components/smart-ui/)
