---
description: How to insert a figure with a description for screen readers, a caption and fixed proportions.
---

# Figure

A figure shows one image with a caption and fixed proportions. Choose it
when the image explains the text and needs a caption or a neat frame.

## Example

:::example {label="Figure"}
```markdown
:::figure {ratio=16/9 fit=cover}
![A project dashboard with a chart and a card](../../assets/figure-scene.webp)

A wide figure fills a 16:9 area.
:::
```
:::

## Markup

```markdown
:::figure {ratio=4/3}
![Image description](../../assets/scheme.webp)

The caption under the image.
:::
```

- The first line is a Markdown image. A block without an image stops the
  build.
- The line after the image becomes the caption. The caption is optional.
- One image per block.

## Variants

### Proportions

`ratio` fixes the aspect ratio of the frame without distorting the file. By
default the image is shown in its own proportions.

:::example {label="Square and panorama"}
```markdown
:::figure {ratio=1/1 fit=contain}
![The Docara mark](../../assets/docara-mark.svg)

A square frame.
:::

:::figure {ratio=21/9 fit=cover}
![A project dashboard with a chart and a card](../../assets/figure-scene.webp)

A 21:9 panorama.
:::
```
:::

### Fill

`fit=cover` fills the frame and crops the edges; it suits photos and scenes.
`fit=contain` shows the whole image; it suits marks and diagrams.

:::example {label="Crop or show whole"}
```markdown
:::figure {ratio=16/9 fit=cover}
![A phone with a documentation page](../../assets/figure-tall.webp)

`cover` crops a tall screenshot at the top and bottom.
:::

:::figure {ratio=16/9 fit=contain}
![A phone with a documentation page](../../assets/figure-tall.webp)

`contain` shows the same screenshot whole.
:::
```
:::

## Accessibility

- The text in square brackets is the image description (`alt`) for screen
  readers. Describe the meaning: "Build diagram: Markdown → HTML", not
  "picture".
- An empty description `![](…)` makes the image decorative: screen readers
  skip it. Do this only if the image adds nothing to the text.
- The caption is visible to everyone but does not replace the description.
- The image loads when the reader reaches it and does not slow down opening
  the page.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `ratio` | `auto`, `1/1`, `4/3`, `3/2`, `16/9`, `21/9` | `auto` | The aspect ratio of the image frame. |
| `fit` | `cover`, `contain` | `cover` | `cover` makes the image fill the frame and crops it at the edges, `contain` fits it whole. |

<!-- docara:component-parameters:end -->

## See also

- [Text with an image](../media/) — an image next to text
- [Links and images](/en/authoring/markdown/#links-and-images) — a plain image in the text
- [Hero](../hero/)
