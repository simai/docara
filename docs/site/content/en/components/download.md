---
description: "How to present a file for download: the reader sees its name, format, size and checksum before downloading."
---

# Download

A download block shows a file as a clear action and tells the reader its
name, format, size and, if needed, its checksum in advance.

## Example

:::example {label="Download"}
```markdown
:::download {title="docara-mark.svg" format="SVG" file_size="1 KB"}
[Download the Docara mark](../../assets/docara-mark.svg)
:::
```
:::

## Markup

```markdown
:::download {title="archive.zip" format="ZIP" file_size="4.8 MB"}
[Download the archive](../../files/archive.zip)
:::
```

- Inside is exactly one Markdown link to the file. Without a link the build
  stops.
- The link text is the label on the button: name the action and the file.
- The parameters are optional. Without them the block becomes a single
  download button.

## Variants

### File details

`title`, `format`, `file_size` and `checksum` are shown next to the button.
Give only verified values.

:::example {label="With a checksum"}
```markdown
:::download {title="docara-mark.svg" format="SVG" file_size="1 KB" checksum="SHA-256: 1078…aebc"}
[Download and verify](../../assets/docara-mark.svg)
:::
```
:::

### Action

`action=download` offers to save the file, `action=open` opens it in the
browser — handy for PDFs and images.

:::example {label="Open in the browser"}
```markdown
:::download {title="docara-mark.svg" format="SVG" action=open}
[Open the Docara mark](../../assets/docara-mark.svg)
:::
```
:::

## Accessibility

- The button is a link: it gets focus with the Tab key and works with Enter.
- The link text is its name for screen readers. "Download the documentation
  archive", not "Download".
- The format and size stand next to the button as text, so the reader knows
  what they will download before pressing it.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `title` | string | — | The file name the reader sees. The old name `name` is accepted with a warning. |
| `format` | string | — | The file format, for example `PDF` or `ZIP`. |
| `file_size` | string | — | The file size, for example `2 MB`. The old name `size` is accepted with a warning. |
| `checksum` | string | — | A checksum for verifying the file. |
| `action` | `download`, `open` | `download` | `download` downloads the file, `open` opens it in the browser. |

<!-- docara:component-parameters:end -->

## See also

- [Button link](../button/) — a move without file details
- [Figure](../figure/)
- [Links and images](/en/authoring/markdown/#links-and-images)
