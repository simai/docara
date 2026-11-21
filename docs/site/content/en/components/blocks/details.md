---
description: How to hide an extra explanation in a collapsible block so that the main instruction stays short.
---

# Details

A details block hides an extra explanation until the reader decides to open
it. Choose it for details and answers to frequent questions; the main
instruction stays visible on the page.

## Example

:::example {label="Details"}
```markdown
:::details
## What happens after the build?

Docara creates a static site that opens without a server runtime.
:::
```
:::

## Markup

```markdown
:::details
## A short question

An answer or an explanation: paragraphs, lists, code.
:::
```

- The first line inside the block is a `##`, `###` or `####` heading. It
  becomes the label on the block's button and does not appear in the page
  outline.
- After the heading there must be at least some content. A block without a
  heading or without content stops the build.

The block is built on the SIMAI Framework accordion (`sf-accordion`).

## Variants

### Look

`layout` picks the styling. The meaning of the block does not change.

:::example {label="Block look"}
```markdown
:::details
## In a thin border

The default look: fits almost anywhere.
:::

:::details {layout=surface}
## On a fill

For a block that stands on its own and must be noticeable.
:::

:::details {layout=lines}
## Between lines

For several questions in a row.
:::

:::details {layout=lines}
## One more question

Lines separate neighbouring blocks.
:::
```
:::

## States

### Open at once

Usually the block is closed. `open=true` opens it when the page loads: do
this only when the explanation is important to see at once but is still
extra.

:::example {label="Open block"}
```markdown
:::details {open=true}
## Open initially

The content is visible at once, and the reader can collapse it if they like.
:::
```
:::

## Accessibility

- The block's label is a button. It gets focus with the Tab key, and Enter
  or Space open and close the block.
- The button tells screen readers whether the block is open or closed
  (`aria-expanded`) and is linked to the content.
- The browser's find-in-page finds text in a closed block too and opens it.
- Do not hide required actions, errors or warnings in the block: every
  reader must see them.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `open` | `true`, `false` | `false` | The block is open right after the page loads. |
| `layout` | `outline`, `surface`, `lines` | `outline` | The look: `outline` is in a thin border, `surface` is on a fill, `lines` is a section between lines. The old name `view` is accepted with a warning. |

<!-- docara:component-parameters:end -->

## See also

- [Backlinks](../backlinks/) — a list of pages that link to this one
- [Alert](../alert/) — for what must not be hidden
- [Tabs](../tabs/) — for equivalent options
