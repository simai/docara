---
description: Backlinks show the pages of the build that link to the current page. The list is built automatically from internal links.
---

# Backlinks

Backlinks show the pages of the same build that lead to the current page.
The list builds itself from internal links, so you do not maintain it by
hand. Place the block at the end of the article, after the main text.

## Example

:::example {label="Backlinks"}
```markdown
:::backlinks
:::
```
:::

## Markup

```markdown
:::backlinks {limit=10}
:::
```

- The block is empty: the build collects its content.
- Only internal links from pages of the same build count. External links
  and links from the menu do not.

## Variants

### List length

`limit` caps the number of links, from `1` to `50`. If there are more
incoming links, the first ones in a stable order remain, so the list does not
change from build to build.

:::example {label="No more than two links"}
```markdown
:::backlinks {limit=2}
:::
```
:::

## States

### No links

If no page links to this one yet, the block shows a short message saying
so instead of an empty space.

## Accessibility

- The block is a section with its own heading, "Links to this page", and a
  plain list of links: screen readers jump to it by the heading.
- The name of each link is the title of the page it leads to.
- The block adds to the article's navigation but does not replace it: put
  important links in the text or in "See also".

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `limit` | integer | `5` | How many incoming links to show. |

<!-- docara:component-parameters:end -->

## See also

- [Links and images](/en/authoring/markdown/#links-and-images)
- [Menu and sections](/en/authoring/navigation/)
