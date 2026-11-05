---
description: "How to style an important link as a button: look, colour, size and icon, a move to a page or to a file."
---

# Button link

A button highlights an important move: to the next step, to a page or to a
file. It is a plain link styled as a button, so it works without scripts too.

## Example

:::example {label="Button link"}
```markdown
:button[View components]{href=../ variant=outline scheme=on-surface icon=arrow_forward}
```
:::

## Markup

```markdown
:button[A clear action]{href=/en/start/}
```

- The square brackets hold the button text, the curly braces the
  parameters. `href` is required: without an address the build stops.
- The address is a site path, an anchor or `https://`. The build does not
  accept unsafe addresses.

The button is built on the SIMAI Framework button (`sf-button`).

## Variants

### Look

`variant` sets the emphasis: from a filled main action to a button that
looks like a link.

:::example {label="Button look"}
```markdown
:button[Filled]{href=../../ variant=default}
:button[Soft background]{href=../../ variant=tonal}
:button[Outline]{href=../ variant=outline}
:button[Like a link]{href=../../ variant=link}
```
:::

### Colour

`scheme` picks the colour: the primary colour, the secondary one or the
page's text colour.

:::example {label="Button colour"}
```markdown
:button[Primary]{href=../../ scheme=primary}
:button[Secondary]{href=../../ scheme=secondary}
:button[Text colour]{href=../../ variant=outline scheme=on-surface}
```
:::

### Size and icon

`size` picks the size step, `icon` adds a Material Symbols icon by name.

:::example {label="Sizes and icon"}
```markdown
:button[Compact]{href=../../ size=1/2}
:button[Regular]{href=../../ size=1 icon=arrow_forward}
:button[Large]{href=../../ size=2}
```
:::

## Accessibility

- The button is an `<a>` link: it gets focus with the Tab key and opens with
  the Enter key, like any link.
- The button text is its name for screen readers. Write what will happen:
  "Download the archive", not "Click here". The icon is decoration and is not
  read.
- One prominent button per screen: make the main action filled and the
  others outlined or link-styled.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `href` (required) | string | — | Where the button leads: a site path, an anchor or an `https://` address. |
| `variant` | `default`, `tonal`, `outline`, `link` | `default` | The look: `default` is filled, `tonal` has a soft background, `outline` has a border, `link` looks like a link. The old name `type` is accepted with a warning. |
| `scheme` | `primary`, `secondary`, `on-surface` | `primary` | The button colour: primary, secondary or the page's text colour. |
| `size` | `1/2`, `1`, `2` | `1` | The size step of the button. |
| `icon` | string | — | The name of a Material Symbols icon after the button text. |

<!-- docara:component-parameters:end -->

## See also

- [Badge](../badge/) — a label, not a move
- [SIMAI Framework ui.* components](../smart-ui/) — the `ui.button` button
- [Links and images](/en/authoring/markdown/#links-and-images)
