---
description: A styled band around ordinary Markdown — at text width or across the full width of its area, with a background tone, a decorative image and an overlay.
---

# Surface

A surface highlights a group of content with a background: several
paragraphs, a grid with cards or a project component. It can stand at the
text width or stretch across the full width of its area, and the text inside
stays aligned with the rest of the page.

The [hero](../hero/), `showcase` and `promo` are already styled the same way
and are not wrapped in a surface.

## Example

:::example {label="Full-width surface"}
```markdown
:::surface {width=full tone=muted padding=lg}
## One rhythm with the text

The text inside the surface is aligned with the neighbouring text of the page.
:::
```
:::

## Markup

```markdown
:::surface {width=full tone=muted}
## Heading

Ordinary Markdown: paragraphs, lists, a grid with cards.
:::
```

- Inside are ordinary Markdown and the blocks that may be nested: a grid,
  cards, steps and project components that declare themselves embeddable.
- A surface inside a surface, or around a hero, `showcase` or `promo`, is
  rejected at build time.
- The chain "surface → grid → card" is allowed.

## Variants

### Width

`width=content` is a surface at the text width. `width=full` spans the full
width of its area: on a landing page up to the window edges, in the
documentation up to the edges of the main column, without covering the side
menu and the outline.

`content_width` picks the width of the content inside: `container` keeps
the text aligned with the rest of the page, `full` gives the content the
full width of the surface.

### Tone and padding

`tone` picks the background: default, muted, accent or contrast. `padding`
sets the inner padding. With `content_width=container` it changes only the
top and bottom padding — on the left and right the main text padding stays.

:::example {label="Accent surface at the text width"}
```markdown
:::surface {width=content tone=accent padding=md}
## Accent

A surface at the text width with an accent background.
:::
```
:::

### Decorative background

`background_image` sets an image from the project's `assets/` folder as the
background. `background_fit`, `background_x` and `background_y` set the
position and fill, `overlay` and `overlay_strength` a darkening or
lightening over the background so that the text is readable.

:::example {label="Background image with an overlay"}
```markdown
:::surface {width=full background_image=/assets/surface-backdrop.webp background_x=right overlay=dark overlay_strength=strong padding=xl tone=contrast}
## Documentation as a product

The text stays above the decorative layer.
:::
```
:::

### A project component inside

You can put a project component in a surface if its description declares
it embeddable (`content.embeddable`). Project components are not shown
inside an interactive example, so below are a live surface and its markup.

:::::surface {width=content padding=md}
:::project.product-configurator
{"title":"Local configurator","base_price":2500,"team_price":4500,"business_price":8000,"currency":"₽"}
:::
:::::

``````markdown
:::::surface {width=content padding=md}
:::project.product-configurator
{"title":"Local configurator","base_price":2500,"team_price":4500,"business_price":8000,"currency":"₽"}
:::
:::::
``````

## States

### Checks at build time

| Author error | Result |
| --- | --- |
| A surface inside a surface or around a hero, `showcase`, `promo` | The nesting is rejected |
| A background at an external address, `data:`, a path outside the project, a symbolic link | The path is rejected |
| `background_x`, `background_y` or `background_fit` without `background_image` | `MARKDOWN_SURFACE_BACKGROUND_REQUIRED` |
| `overlay_strength` without `overlay` | `MARKDOWN_SURFACE_OVERLAY_REQUIRED` |
| An unknown parameter, CSS class or style | A parameter error |

## Accessibility

- The background image is decoration: it is output with an empty
  description and hidden from screen readers.
- The overlay does not get focus and does not catch clicks: the text and
  links over it work as usual, in source order.
- Choose the overlay so that the text is readable over the background in
  the light and dark themes.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `width` | `content`, `full` | `content` | `content` is at the text width, `full` spans the full window width. |
| `content_width` | `container`, `full` | `container` | The width of the content inside the surface: by the container or the full width. |
| `background_image` | string | — | The background image of the surface. |
| `background_fit` | `cover`, `contain`, `auto` | `cover` | How the background image fills the surface. |
| `background_x` | `left`, `center`, `right` | `center` | The horizontal position of the background. |
| `background_y` | `top`, `center`, `bottom` | `center` | The vertical position of the background. |
| `overlay` | `none`, `light`, `dark` | `none` | A darkening or lightening over the background. |
| `overlay_strength` | `soft`, `medium`, `strong` | `medium` | The strength of the darkening or lightening. |
| `padding` | `none`, `sm`, `md`, `lg`, `xl` | `md` | The inner padding of the surface. |
| `tone` | `default`, `muted`, `accent`, `contrast` | `default` | The background tone: default, muted, accent or contrast. |

<!-- docara:component-parameters:end -->

## See also

- [Hero](../hero/)
- [Grid](../grid/) and [card](../card/)
- [Landing blocks](../landing-blocks/)
- [Steps](../steps/) — a sequence inside a surface
