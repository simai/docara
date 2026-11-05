---
description: How to place a short text and one image side by side so that they stack into one column on a phone.
---

# Text with an image

The block places a short explanation and one image side by side. On a wide
screen they stand in two columns, on a phone one under the other in reading
order.

## Example

:::example {label="Text with an image"}
```markdown
:::media
## Text on the left

The image adds to the explanation and moves below it on a narrow screen.

![Diagram: source files, components and the finished page](../../assets/media-flow.webp)
:::
```
:::

## Markup

```markdown
:::media
## A clear heading

A short explanation of how the text and the image relate.

![Image description](../../assets/scheme.webp)
:::
```

- Inside are the text and one image as a separate paragraph. Without an
  image or without text the build stops with the error
  `MARKDOWN_MEDIA_CONTENT_REQUIRED`.
- If there are several images, the first goes into the column, and the
  others stay in the text. For several images choose
  [figures](../figure/).

## Variants

### Side

`side` picks which side the image is on a wide screen. The order in the
source Markdown does not change.

:::example {label="Image on the left"}
```markdown
:::media {side=left}
## Image on the left

The mirrored variant uses the same order of the source Markdown.

![Page components around the Docara mark](../../assets/media-components.webp)
:::
```
:::

### Proportions

`ratio` fixes the aspect ratio of the image so that the layout does not
jump while it loads.

:::example {label="Square image"}
```markdown
:::media {ratio=1/1}
## Square

Suits marks and portraits.

![Page components around the Docara mark](../../assets/media-components.webp)
:::
```
:::

## Accessibility

- The reading order does not depend on the side: first the text, then the
  image.
- The image description must explain the meaning: "A Docara page on a wide
  and a narrow screen", not "screenshot".
- The image loads as you scroll and does not slow down opening the page.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `side` | `left`, `right` | `right` | Which side the image is on. |
| `ratio` | `1/1`, `4/3`, `3/2`, `16/9`, `21/9` | `16/9` | The aspect ratio of the image. |

<!-- docara:component-parameters:end -->

## See also

- [Figure](../figure/)
- [Landing blocks](../landing-blocks/)
- [Hero](../hero/)
