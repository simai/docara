---
description: "How to embed a video, a map or other external content: it loads only on the reader's action and keeps responsive proportions."
---

# Embed

The block shows a video, a map or other trusted content in a frame with
fixed proportions. The external site loads only after the reader acts: until
then it receives no requests at all.

## Example

:::example {label="Embedded video"}
```markdown
:::embed {provider=video label="External demo"}
[Open the embedded content](https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ)
:::
```
:::

## Markup

```markdown
:::embed {provider=map ratio=4/3 label="Interactive map"}
[Open the map](https://www.openstreetmap.org/export/embed.html)
:::
```

- Inside is one Markdown link. Its address is what to embed, its text is
  the label on the load button.
- The address is checked at build time. An unsuitable one stops the build
  with the error `MARKDOWN_EMBED_ORIGIN_FORBIDDEN`.

## Variants

### Provider

`provider` defines which addresses are accepted:

| `provider` | Accepted address |
| --- | --- |
| `generic` | Only an address on the same site: a path from the root, for example `/demo/widget/` |
| `video` | Only `https`: `youtube.com`, `www.youtube.com`, `youtube-nocookie.com`, `www.youtube-nocookie.com`, `player.vimeo.com` |
| `map` | Only `https`: `openstreetmap.org`, `www.openstreetmap.org` |
| `external` | Any `https` address, but only with `consent=required` |

:::example {label="A map and an external widget"}
```markdown
:::embed {provider=map ratio=4/3 label="Interactive map"}
[Open the map](https://www.openstreetmap.org/export/embed.html)
:::

:::embed {provider=external ratio=1/1 label="External widget"}
[Open the external widget](https://example.com/)
:::
```
:::

### Proportions

`ratio` sets the aspect ratio of the frame. The space for the content is
reserved at once, and the page does not jump when it loads.

## States

### Before consent

With `consent=required` a load button stands in place of the content.
Pressing it loads the frame in that place.

### At once

`consent=none` loads the content when the page opens. This is allowed only
for an address on the same site: a video, a map and an external widget
always wait for the reader's action.

## Accessibility

- Before loading, a button with the link text stands in place of the
  content: it gets focus with the Tab key.
- `label` is the frame's name for screen readers. It is not visible on the
  page, but without it the frame stays unnamed: always set it.
- The frame is isolated: the embedded site cannot see the documentation
  page.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `provider` | `generic`, `video`, `map`, `external` | `generic` | What is embedded: `generic` is a page, `video` a video, `map` a map, `external` an external service. |
| `id` | string | — | The block's anchor on the page: you can link to it as `#id`. Latin letters, digits, dots, hyphens and underscores. |
| `ratio` | `1/1`, `4/3`, `3/2`, `16/9`, `21/9` | `16/9` | The aspect ratio of the embed window. |
| `label` | string | `Embedded content` | The name of the embedded content for screen readers. The old name `title` is accepted with a warning. |
| `consent` | `required`, `none` | `required` | `required` loads the content only after the reader's consent, `none` loads it at once. |

<!-- docara:component-parameters:end -->

## See also

- [Isolated HTML](../html/)
- [Figure](../figure/)
- [Security](/en/reference/security-and-errors/)
