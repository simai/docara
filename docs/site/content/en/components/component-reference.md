---
description: A component reference is built from the component's manifests at build time — attributes, events, methods, CSS variables and accessibility — and translated with the locale's dictionary.
---

# Component reference

A component reference shows the component's tag, attributes, events,
methods, CSS variables and accessibility. The build reads them from the
component's manifests, so the page never drifts from what was released. Put
the block on the component's page under a «Parameters» or «Reference»
heading.

## Example

:::example {label="Component reference"}
```markdown
:::reference {component=demo-counter}
:::
```
:::

## Markup

```markdown
:::reference {component=sf-view-table}
:::
```

- The block is empty: the build collects its content.
- `component` is the component's tag. The build looks for its manifests in
  the project's `components/` folder: `components/<folder>/smart.manifest.json`
  and, if present, `components/<folder>/component.json`. The folder is the
  tag without the `sf-` prefix: `components/view-table/` for
  `sf-view-table`, `components/demo-counter/` for `demo-counter`. This is
  how the `smart/` folder of a SIMAI Framework or set release is laid out,
  so it can be copied as it is.
- The manifest must describe the same tag (`custom_element`), otherwise the
  build stops.

```text
docara.json
components/
  view-table/
    smart.manifest.json
    component.json
content/
  ru/
    lang.json
    component-reference.json
```

## What the reference shows

| Part | Where it comes from |
| --- | --- |
| `overview` | The manifest's tag, `summary`, `id` and `version`, `lifecycle` |
| `attributes` | `inputs.properties`: the type or allowed values, the default, `description`; required ones from `inputs.required` |
| `events` | `events`: the `detail` data from `payload` and `summary`; former names with `alias_of` are listed under the table |
| `methods` | `methods`: arguments, `returns` and `summary` |
| `css` | `css-variables` from `component.json`: name and type |
| `accessibility` | `accessibility`: role, accessible name, keys, focus |

Descriptions that add nothing to the name, such as «Public runtime method
reset.», are not shown. A part the manifest does not have is not hidden:
its heading is followed by «Not declared in the manifest.»

## Variants

### Only part of the reference

`sections` chooses the parts and their order. With several parts, each one
except `overview` gets its own level-three heading. A single part is shown
without a heading: the page author writes it.

:::example {label="Events only"}
```markdown
:::reference {component=demo-counter sections=events}
:::
```
:::

## Translation

Manifest text is written in English. For the reference on a locale's pages to
be in that language, put a `component-reference.json` dictionary in the
locale's content folder, next to `lang.json`:

```json
{
    "schema": "docara.component_reference.v1",
    "strings": {
        "The current value.": "Текущее значение.",
        "css:--demo-counter-gap": "Расстояние между полем и кнопками."
    }
}
```

- The key is the manifest string exactly as written, the value is its
  translation. Backticks in a translation make code.
- CSS variables have no reader-facing description in `component.json`: the
  key `css:<variable name>` gives one. Without a dictionary the description
  column is empty.
- A locale without a dictionary shows the manifest text as it is.
- The dictionary is checked on every build. If a string a block shows has no
  translation, the build stops with `COMPONENT_REFERENCE_TRANSLATION_MISSING`
  and names the first such strings. If the dictionary keeps a string no block
  on the site shows, a full build stops with
  `COMPONENT_REFERENCE_TRANSLATION_STALE`: remove it or update it to the new
  manifest text. A one-page build sees only that page and does not check for
  stale strings.

The reference's labels — «Attributes», «Default», «Description» and the
rest — are the `reference.*` strings of [`lang.json`](/en/reference/lang-json/),
and a site can replace them with its own.

## Accessibility

- Attributes, events, methods and CSS variables are ordinary tables with
  column headers.
- The tag, the contract and accessibility are description lists: screen
  readers read each name and value as a pair.
- The part headings are in the page outline.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `component` (required) | string | — | The tag of the component whose reference to show, for example `sf-view-table`. The manifests are read from `components/<folder>/`. |
| `sections` | string | `overview,attributes,events,methods,css,accessibility` | Which parts of the reference to show and in what order, separated by commas: `overview`, `attributes`, `events`, `methods`, `css`, `accessibility`. Without the parameter, all of them. |

<!-- docara:component-parameters:end -->

## See also

- [Page and Markdown errors](/en/reference/errors/content/)
- [lang.json interface strings](/en/reference/lang-json/)
