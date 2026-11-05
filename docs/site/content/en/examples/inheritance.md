---
description: "How to set shared settings for a whole section in section.json and change one of them for a single page."
---

# Shared section settings with a page exception

The API reference section reads in a narrow column without a page outline. One
page with a wide table needs a wider column. The page changes only the
width and takes everything else from the section.

::::example {src="/en/demonstrator-results/inheritance/page/" label="Result"}
### Section section.json

:::code {src="../demonstrator-results/inheritance/section.json" lang=json highlight="4-10"}
:::

### Page page.json

:::code {src="../demonstrator-results/inheritance/page.page.json" lang=json highlight="5-7"}
:::

### Page Markdown

:::code {src="../demonstrator-results/inheritance/page.md" lang=markdown}
:::
::::

## How it works

1. `section.json` sits in the section folder. Every page in this folder and
   its subfolders gets its settings.
2. The section makes the column narrow (`container.max: 4`) and turns off the page outline and
   the "Previous / Next" links.
3. The `page.json` file named after the page changes one setting: the width.
   The page still has no outline and no previous/next links: the page did not
   touch them.

The order is: the site's `docara.json`, then the sections' `section.json` files from
outer to inner, then the page's `page.json`. Each next file changes
only what it contains.

## What you can change

| Parameter | What it changes |
|---|---|
| `layout.container.max` | Text column width, from 1 to 8 |
| `reading.toc` | The page outline |
| `reading.previous_next` | Links to neighbouring pages at the bottom |
| `reading.breadcrumbs` | The "where am I" line above the title |

## See also

- [Settings on three levels](../composition-inheritance/)
- [Site, section and page](/en/settings/levels-and-inheritance/)
