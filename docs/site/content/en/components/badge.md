---
description: "How to add a short status, version or category label to the text: the look, colour and size of a badge."
---

# Badge

A badge is a short label inside a line: a status, a version, a category.
Choose it for one or two words that the reader must notice at once.

## Example

:::example {label="Badge"}
```markdown
The component is ready to use :badge[New]{variant=tonal scheme=primary size=1}
```
:::

## Markup

```markdown
:badge[Label text]{scheme=success}
```

- The square brackets hold the label text, the curly braces the parameters.
  All parameters are optional.
- A badge sits inside a line: in a paragraph, a list item, a table cell or a
  heading.

The badge is built on the SIMAI Framework badge (`sf-badge`).

## Variants

### Look

`variant` sets how strongly the badge stands out.

:::example {label="Badge look"}
```markdown
:badge[Filled]{variant=main size=1}
:badge[Soft background]{variant=tonal size=1}
:badge[Outline]{variant=outline size=1}
```
:::

### Colour

`scheme` picks a colour from the SIMAI Framework palette: the primary colour,
the additional ones, neutral or a status colour.

:::example {label="Badge colours"}
```markdown
:badge[Primary]{scheme=primary size=1}
:badge[Secondary]{scheme=secondary size=1}
:badge[Tertiary]{scheme=tertiary size=1}
:badge[Neutral]{scheme=neutral size=1}
:badge[Info]{scheme=info size=1}
:badge[Success]{scheme=success size=1}
:badge[Warning]{scheme=warning size=1}
:badge[Error]{scheme=danger size=1}
:badge[On surface]{variant=main scheme=on-surface size=1}
```
:::

### Size

`size` picks a step from the SIMAI Framework scale. By default the badge is
small so that it does not stand out of the line of text.

:::example {label="Badge sizes"}
```markdown
:badge[1/3]{size=1/3}
:badge[1/2]{size=1/2}
:badge[1]{size=1}
```
:::

## Accessibility

- A badge is plain text: screen readers read it together with the line.
  Write the label so that it is clear without the colour.
- Colour adds to the meaning but does not replace it: "Error" on a red
  background, not just a red dot.
- A badge is not clickable. For navigation use a [button link](../button/).

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `variant` | `main`, `tonal`, `outline` | `tonal` | The look: `main` is filled with the main colour, `tonal` has a soft background, `outline` has only a border. The old name `type` is accepted with a warning. |
| `scheme` | `primary`, `secondary`, `tertiary`, `neutral`, `info`, `success`, `warning`, `danger`, `on-surface` | `primary` | A colour from the SIMAI Framework palette: primary, additional, neutral or a status colour. |
| `size` | `1/3`, `1/2`, `1` | `1/3` | The size step of the badge. |

<!-- docara:component-parameters:end -->

## See also

- [Button link](../button/)
- [Icon](../icon/)
- [Keyboard input](../kbd/)
