---
description: "How to add your own interactive component to a site without changing Docara: a smart/ folder with a descriptor, template, script and styles."
---

# Your own project component

When the built-in blocks are not enough, the site adds its own component. Here there are three:
an install command builder, a plan calculator and footer links. Docara
does not change: the components live in the site's own `smart/` folder.

::::example {src="/en/project-demos/" label="Result"}
### Page Markdown

:::code {src="../project-demos.md" lang=markdown highlight="13-15,21-23"}
:::

### manifest.json

:::code {src="../../../smart/project.install-builder/manifest.json" lang=json highlight="18-32"}
:::

### view/default.json

:::code {src="../../../smart/project.install-builder/view/default.json" lang=json}
:::

### template/default.php

:::code {src="../../../smart/project.install-builder/template/default.php" lang=php}
:::
::::

Choose another system or version in the example window: the install command
changes at once. You can copy it with the "Copy" button.

## How it works

1. Each component is a folder in `smart/` named like `project.name`.
2. `manifest.json` describes the component: its parameters (`props`), places for
   nested elements (`slots`) and its style and script files.
3. `view/default.json` builds the component from ready SIMAI Framework
   elements: `ui.dropdown` lists, `ui.input` fields and `ui.checkbox`
   checkboxes. The Russian labels sit next to it in `view/ru.json`; the
   Russian page picks that view with `"view":"ru"`.
4. `template/default.php` is the markup Docara outputs during the build. A script
   from `assets/` brings it to life in the browser.

On a page, you call the component the same way as SIMAI Framework components:

:::code {src="../project-demos.md" lang=markdown lines="13-15"}
:::

## What you can change

| File | What changes in it |
|---|---|
| `manifest.json` | The component parameters and the allowed nested elements |
| `view/default.json` | Which elements are inside and with which labels |
| `template/default.php` | The markup around the elements |
| `assets/*.css`, `assets/*.js` | Appearance and behaviour in the browser |

The example components send nothing over the network: the builder only
assembles the command, and the calculator computes the total in the browser.

## See also

- [SIMAI Framework components](/en/components/smart-ui/)
- [Page footer](../region-footer/)
