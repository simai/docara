---
description: How to arrange blocks in a responsive grid of several columns that collapses into one on a phone.
---

# Grid

A grid places self-contained blocks — most often [cards](../card/) — in
columns. On a phone the blocks stack into one column, on a tablet into two,
on a wide screen into the set number.

## Example

:::example {label="Grid"}
```markdown
::::grid {columns=3 gap=2}
:::card
#### Markdown

A clear source format.
:::
:::card
#### JSON

Checkable settings.
:::
:::card
#### HTML

A finished static result.
:::
::::
```
:::

## Markup

```markdown
::::grid {columns=3}
:::card
#### First block

Content.
:::
:::card
#### Second block

Content.
:::
::::
```

- The grid is the outer block, so it has one more colon than the nested
  ones: `::::grid` around `:::card`.
- Inside are Docara blocks, usually cards. The number of blocks need not
  match the number of columns: the extra ones move to the next row.

## Variants

### Columns

`columns` is how many columns there are on a wide screen, from one to four.

:::example {label="Two columns"}
```markdown
::::grid {columns=2 gap=2}
:::card
#### Content

The first self-contained block.
:::
:::card
#### Composition

The second self-contained block.
:::
::::
```
:::

### Gap

`gap` is the space between blocks on the SIMAI Framework spacing scale, from
`0` to `4`. Keep one step for the whole group so that the rhythm is even.

:::example {label="Gap 4"}
```markdown
::::grid {columns=2 gap=4}
:::card
#### Wider

The space between the cards is larger.
:::
:::card
#### Air

Handy for large blocks.
:::
::::
```
:::

## Accessibility

- The grid handles only placement. Readers and screen readers go through
  the blocks in source order: left to right, top to bottom.
- Do not reorder blocks for the sake of looks: on a phone they stack in
  exactly this order.
- The nested blocks set the headings and the meaning, not the grid.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `columns` | `1`, `2`, `3`, `4` | `3` | How many columns there are on a wide screen. On a phone the grid has one column. |
| `gap` | `0`, `1`, `2`, `3`, `4` | `2` | The space between cells on the spacing scale. |

<!-- docara:component-parameters:end -->

## See also

- [Card](../card/)
- [Landing blocks](../landing-blocks/) — ready-made feature grids
- [How components work](/en/start/component-model/) — nested blocks
