---
description: How to show a tree of the project's folders and files with explicit nesting and collapsible folders.
---

# File tree

A tree shows the structure of folders and files with explicit nesting.
Choose it when the reader needs to understand where a file is and what is
next to it.

## Example

:::example {label="File tree"}
```markdown
:::tree
- content
  - en
    - index.md
    - section.json
  - assets
    - logo.svg
- docara.json
:::
```
:::

## Markup

```markdown
:::tree
- folder
  - nested file
- file
:::
```

- Inside the block is one unordered Markdown list. Nesting is set by an
  indent of two spaces.
- An item with a nested list becomes a folder, an item without one a file.
  You can use code in a label: `` `index.md` ``.

The block is built on the SIMAI Framework tree (`sf-tree`).

## Variants

### Collapsing folders

By default folders can be collapsed. `interactive=false` makes the tree
static: a plain nested list where every folder stays open and nothing can
be pressed. Each row shows a folder or file icon before the name, and a
nested level starts exactly under its parent's name.

:::example {label="Static tree"}
```markdown
:::tree {interactive=false}
- content
  - index.md
- docara.json
:::
```
:::

## States

### Expanded folders

When the page opens, all folders are expanded, so the whole structure is
visible without scripts too. A collapsed folder shows a closed icon.

## Accessibility

- The tree is a `tree`: Tab moves focus into it, the up and down arrows
  move between rows, the right arrow expands a folder, the left arrow
  collapses it, Home and End go to the first and last row.
- You can also collapse a folder by clicking its icon.
- A static tree is a nested list: it takes no focus, and a screen reader
  reads its levels as list levels.
- The tree explains structure. Do not use it as a navigation menu: the side
  menu is there for links across the site.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `interactive` | `true`, `false` | `true` | Folders can be collapsed with the mouse and the keyboard. With `false` the tree is always expanded. |

<!-- docara:component-parameters:end -->

## See also

- [Project files](/en/start/project-files/) — the Docara project tree
- [Lists and quotes](/en/authoring/markdown/#lists-and-quotes)
- [Code](../code/)
