---
description: How to build a card with a heading and text for short explanations, choices and small groups of links.
---

# Card

A card brings a heading and the text related to it together on a separate
surface. Choose it for short explanations, choices and small groups of
links; put several cards in a [grid](../grid/).

## Example

:::example {label="Card"}
```markdown
:::card
### A portable project

Markdown and JSON can live next to the code and be reviewed through Git.
:::
```
:::

## Markup

```markdown
:::card
### A clear heading

Short related content.
:::
```

- Start the card with a heading: it names the card's content.
- Inside is plain Markdown. Docara blocks (`:::`) do not nest inside a card.

## Variants

### Look

`variant=plain` removes the border, background and padding: this way the
card fits into another surface, for example a band or a grid with a shared
background.

:::example {label="Card look"}
```markdown
:::card
### Styled

A surface, a border and inner padding.
:::

:::card {variant=plain}
### Unstyled

Only the content, without its own surface.
:::
```
:::

## Accessibility

- A card is a landmark region (`section`) with its own heading: screen
  readers move through it like a section of the page.
- Choose the heading level by its place on the page: `###` for a card inside
  a `##` section.
- Do not make a card just for a border around long text: a long article
  reads better without it.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `variant` | `default`, `plain` | `default` | The look: `default` is a card with a border and background, `plain` is without them. |

<!-- docara:component-parameters:end -->

## See also

- [Grid](../grid/) — several cards in a row
- [Surface](../surface/) — a background for a group of blocks
- [Landing blocks](../landing-blocks/) — feature cards
