---
description: Cards, grids, the first screen and other blocks a page or a landing page is built from.
---

# Layout

Cards, grids, the first screen and other blocks a page or a landing page is built from.

| Component | Purpose |
| --- | --- |
| [Card](/en/components/layout/card/) | A short self-contained block with a heading |
| [Grid](/en/components/layout/grid/) | Several cards in a row that stack into a column on a phone |
| [Columns and width](/en/components/layout/columns/) | Page parts side by side with set shares, and a block narrower than the page column |
| [Surface](/en/components/layout/surface/) | A styled full-width band around part of the page |
| [Hero](/en/components/layout/hero/) | The start of a landing page: heading, description, actions and an image |
| [Background effects](/en/components/layout/effects/) | An animation under the hero text instead of an image |
| [Landing blocks](/en/components/layout/landing-blocks/) | Feature cards, a result showcase, columns and a call to action |

## How to choose

A landing page starts with the first screen: a heading, a description, actions and a
picture or a background effect. Cards in a grid, columns and landing blocks follow.
A surface band sets part of the page apart with a full-width background. Every
layout block turns into a single column on a phone by itself, so no separate
narrow-screen markup is needed.

## Space below a block

Docara leaves a small space below every block, and a section heading adds
its own, larger space above itself. The `space` parameter sets the space
below a block explicitly — as a step of the SIMAI Framework spacing scale
from `0` to `8`, by default `1`. Any block accepts it: the hero, the grid,
columns, a surface, an alert and others.

If `space` is written, the distance to the next block or heading is exactly
that: a heading after such a block adds no space of its own above. If it is
not written, everything stays as before. So one place decides the
distance — the block above.

```markdown
:::hero {layout=split padding=2xl space=6}
# Documentation from files

The site builds itself.
:::

## Why it is handy

::::grid {columns=3 space=6}
:::card
#### Fast

A build with one command.
:::
::::
```

On a landing page it is handy to keep one step for all sections, for
example `space=6`.
