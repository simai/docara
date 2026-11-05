---
description: How to place equivalent options on tabs that the reader switches between with the mouse or the keyboard.
---

# Tabs

Tabs put several equivalent options into one block: commands for different
systems, installation methods, code in different languages. The reader
switches between them without leaving the page.

## Example

:::example {label="Tabs"}
````markdown
:::tabs {sync=install default="Archive"}
### Composer

```bash
composer create-project simai/docara my-docs
```

### Archive

Download the archive and unpack it into an empty folder.

### Result

The finished site appears in the build folder.
:::
````
:::

## Markup

```markdown
:::tabs
### First tab label

The content of the first tab.

### Second tab label

The content of the second tab.
:::
```

- Each tab starts with a `###` heading: it becomes the tab label. Keep the
  labels short, one or two words.
- You need at least two tabs, and each must have content: ordinary
  Markdown — text, lists, links, code.

The block is built on SIMAI Framework tabs (`sf-tabs`, the underlined look).

## Which tab is open

By default the first tab is open. The `default` parameter opens another
one — give its label or its name (the label in lowercase, with spaces and
punctuation replaced by hyphens):

```markdown
:::tabs {default=linux}
### macOS

brew install docara

### Linux

apt install docara
:::
```

If there is no tab with that name, the build stops with
`MARKDOWN_TABS_DEFAULT_NOT_FOUND` and lists the names of the block's tabs.

## Linked tabs

The `sync` parameter gives the block a name. Blocks with the same name
switch together, and the reader's choice is kept for the following pages:
having chosen Linux at the start of a guide, the reader keeps seeing Linux
later on.

```markdown
:::tabs {sync=os}
### macOS

brew install docara

### Linux

apt install docara
:::
```

- The name is lowercase Latin letters, digits and hyphens, up to 32
  characters.
- Tabs are matched by name, not by order: a block can list a different set
  of tabs, and the matching ones switch together.
- The choice is stored in the reader's browser (`localStorage`). Without
  scripts and without a saved choice, the tab from the markup is open.

## Variants

By default the tabs are underlined (`variant=underline`). `variant=pills` is
the rounded SIMAI Framework switch: on a home page or a landing page it
chooses which live example to show.

A tab may hold an example, code, a figure or an alert. Then the tabs block
opens with four colons `::::tabs` and closes with `::::`, so that the `:::`
of a nested block does not close it. An example on a hidden tab loads when
the tab is opened.

::::tabs {variant=pills}
### Button

:::example {label=Button}
```html
<button type="button" class="sf-button sf-button--default sf-button--primary sf-button--size-1"><span class="sf-button-text-container">Save</span></button>
```
:::

### Text

:::example {label=Text}
```markdown
**Done.** The site is built and checked.
```
:::
::::

The source of this block:

~~~markdown
::::tabs {variant=pills}
### Button

:::example {label=Button}
```html
<button type="button" class="sf-button sf-button--default sf-button--primary sf-button--size-1"><span class="sf-button-text-container">Save</span></button>
```
:::

### Text

:::example {label=Text}
```markdown
**Done.** The site is built and checked.
```
:::
::::
~~~

## States

### Selected tab

When the page opens, the first tab or the tab from `default` is selected,
and in linked tabs the reader's saved choice. The content of the selected
tab is visible before the scripts load too, so put the important one first.

## Accessibility

- Tabs are a tab list (`tablist`): Tab moves focus to the selected tab, the
  left and right arrows switch tabs, Home and End select the first and the
  last one.
- Each tab is linked to its content, and screen readers hear which tab is
  selected.
- Do not put the steps of one process on tabs: the reader would see only
  one step. For a sequence use [steps](../steps/).

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `sync` | string | — | The group name: blocks with the same name switch together, and the reader's choice is kept for the following pages. |
| `default` | string | — | Which tab is open on load: the tab label or its name (the label in lowercase, punctuation replaced by hyphens). |
| `variant` | `underline`, `pills` | `underline` | The tabs style: underlined tabs or the rounded switch. |

<!-- docara:component-parameters:end -->

## See also

- [Steps](../steps/) — for sequential actions
- [Code](../code/) — code blocks inside tabs
- [Interactive example](../example/) — the result and the source code side by side
