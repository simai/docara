---
description: "How to show a row of product, partner or member logos: text, links and images in one grid."
---

# Logos and members

The block shows a compact row of products, partners or members. The items
can be text, links or images.

## Example

:::example {label="Logos"}
```markdown
:::logos
- [![SIMAI](../../assets/simai.svg)](https://simai.io/)
- ![Docara](../../assets/docara-mark.svg)
- SIMAI Effects
:::
```
:::

## Markup

```markdown
:::logos
- First member
- [Second member](https://example.com/)
- ![Third member](../../assets/logo.svg)
:::
```

- Inside is one flat bulleted list: without nested lists or other text.
- Two to twelve items. Each item is one short paragraph: text, a link or an
  image.
- The errors of this block are described in the
  [error reference](/en/reference/errors/content/).

## Variants

### Tone

`tone=muted` mutes the logos so that they do not compete with the main
content. The labels stay visible.

:::example {label="Muted row"}
```markdown
:::logos {tone=muted}
- [SIMAI](https://simai.io/)
- [GitHub](https://github.com/)
- [Documentation](../)
:::
```
:::

### Columns

You do not need to set the number of columns: on a phone the logos stand in
two columns, on a tablet in three, on a wide screen in six.

## Accessibility

- The row is a list: screen readers announce the number of items.
- An image must have a description with the product or member name:
  `![SIMAI](…)`. A reader with a screen reader cannot recognise a logo
  without a description.
- One logo per item. Move a long description into the text next to it.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `tone` | `default`, `muted` | `default` | `muted` mutes the logos so that they do not compete with the main content. |

<!-- docara:component-parameters:end -->

## See also

- [Landing blocks](../landing-blocks/)
- [Hero](../hero/)
- [Figure](../figure/)
