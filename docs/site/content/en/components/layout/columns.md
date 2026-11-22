---
description: How to put text and blocks into columns with set shares and how to make a block narrower than the page column.
---

# Columns and width

Columns place several parts of a page side by side: text and code, questions
and reference, a short description and the details. The `:::container` width
block makes one part narrower than the page column — for example, a call to
action in the centre.

Both blocks look at their own width, not the window width. So the same markup
works in the documentation column between the menu and the outline, on a
full-width landing page and inside another block.

## Example

:::example {label="Columns 1:2"}
```markdown
:::columns {ratio=1/2 gap=4 align=start}
### Version 1.10

What changed in the release.

---

- A new "Status" column
- An entry opens next to the list
- Faster on large lists
:::
```
:::

## Markup

```markdown
:::columns {ratio=2/1}
The first column: any Markdown.

---

The second column.
:::
```

- Columns are separated by a `---` line with blank lines before and after it.
- A block has two to four columns. Inside is any Markdown, as well as cards,
  alerts, steps and collapsible blocks. The outer block needs one more colon:
  `::::columns` around `:::details`.
- When the block does not have enough width, the columns stack one under
  another in the same order.

## Variants

### Shares

`ratio` is the width shares from left to right: `2/1` makes the left column
twice as wide as the right one, `1/1/1` gives three equal ones. The number of
shares equals the number of columns. Without the parameter the columns are
equal.

:::example {label="Text and a note 3:1"}
```markdown
:::columns {ratio=3/1 align=center}
The default width of the entry panel.

---

**52 characters**
:::
```
:::

### Blocks inside columns

You can put [collapsible blocks](/en/components/blocks/details/),
a [card](/en/components/layout/card/), an [alert](/en/components/blocks/alert/),
a [figure](/en/components/images-and-files/figure/) or [steps](/en/components/blocks/steps/) into a column.
Then the outer block has one more colon.

:::example {label="Questions and reference side by side"}
```markdown
::::columns {ratio=2/1}
### Frequently asked questions

:::details {layout=lines}
### Is Node.js needed on the server?

No, only for the build. The finished site is static.
:::

:::details {layout=lines}
### Can I make a landing page?

Yes, the `landing` preset removes the documentation menu.
:::

---

:::card
### In short

Docara 3, Apache 2.0 licence.
:::
::::
```
:::

### When to fold

`fold` is the block width below which the columns stack one under another:
`sm` — below 32rem, `md` — below 44rem, `lg` — below 60rem, `none` — never.
Without the parameter two columns fold at `md`, three and four at `lg`.

:::example {label="Three columns that do not fold"}
```markdown
:::columns {ratio=1/1/1 fold=none gap=1}
**Markdown**

---

**JSON**

---

**HTML**
:::
```
:::

### Gap and alignment

`gap` is the space between columns on the SIMAI Framework spacing scale, from
`0` to `8`, by default `2`. `align` is how the columns stand vertically:
`start` — at the top, `center` — in the middle, `end` — at the bottom,
`stretch` — the full height of the row.

## Block width

`:::container` makes a block narrower than the page column. `width` is the
share of the column: `1/4`, `1/3`, `1/2`, `2/3`, `3/4` or `full`. `align` is
where the block stands: `start`, `center` or `end`. The `1/3`, `1/2` and
`2/3` shares can be centred. When the block does not have enough width
(below 44rem), it takes the whole column.

:::example {label="Half in the centre"}
```markdown
:::container {width=1/2 align=center}
**Start with one command.** Docara does the rest.
:::
```
:::

:::example {label="Two thirds from the start"}
```markdown
:::container {width=2/3}
A section caption or a note that should not run the full width.
:::
```
:::

A width in characters (`width=44`, `width=64`) will come when SIMAI Framework
releases text width steps.

## Accessibility

- Columns and width handle only placement. Readers and screen readers go
  through the columns in source order, left to right.
- Do not reorder the parts for the sake of looks: on a narrow screen they
  stack in exactly this order.
- A heading inside a column is an ordinary page heading. Keep the levels in
  order.

## Parameters

<!-- docara:component-parameters:start -->

### `:::columns`

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `ratio` | `1/1`, `2/1`, `1/2`, `3/1`, `1/3`, `3/2`, `2/3`, `1/1/1`, `2/1/1`, `1/2/1`, `1/1/2`, `1/1/1/1` | — | The column width shares on a wide screen, from left to right: `2/1` makes the left one twice as wide as the right one. The number of shares equals the number of columns. Without the parameter the columns are equal; on a phone they stack one under another. |
| `gap` | `0`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8` | `2` | The space between columns on the SIMAI Framework spacing scale, from `0` to `8`. |
| `fold` | `sm`, `md`, `lg`, `none` | — | When the columns stack one under another, by the width of the block itself: `sm` — below 32rem, `md` — below 44rem, `lg` — below 60rem, `none` — never. Without the parameter two columns fold at `md`, three and four at `lg`. |
| `align` | `start`, `center`, `end`, `stretch` | — | The vertical alignment of the columns: `start` — at the top, `center` — in the middle, `end` — at the bottom, `stretch` — stretched to the full height of the row. |

### `:::container`

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `width` (required) | `1/4`, `1/3`, `1/2`, `2/3`, `3/4`, `full` | — | The block width as a share of the page column: `1/4`, `1/3`, `1/2`, `2/3`, `3/4` or `full` for the full width. Below 44rem the block takes the full width. |
| `align` | `start`, `center`, `end` | `start` | Where the block stands: `start` — at the start of the line, `center` — in the centre (for `1/3`, `1/2`, `2/3`), `end` — at the end of the line. |

<!-- docara:component-parameters:end -->

## See also

- [Grid](../grid/) — identical cards in a row
- [Layout](../../layout/) — the `space` parameter for the space below a block
- [Landing blocks](../landing-blocks/)
