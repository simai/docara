---
description: How to write a math formula in TeX inline or as a separate block, with a label for screen readers.
---

# Math formula

The block shows a formula written in TeX. Choose it for short expressions
and standalone formulas; split long derivations into several blocks with
explanations between them.

## Example

:::example {label="Formula"}
```markdown
:::math {label="Pythagorean theorem"}
a^2 + b^2 = c^2
:::
```
:::

## Markup

```markdown
:::math {label="Area of a circle"}
S = \pi r^2
:::
```

- Inside is only the TeX formula, without surrounding `$`.
- An empty block stops the build with the error
  `MARKDOWN_MATH_SOURCE_REQUIRED`.

The build does not parse TeX: it keeps the formula's source text in the
page. In the browser the KaTeX library draws the formula. It is part of the
Docara package and loads only on pages with formulas, so no network is
needed.

## Variants

### Display

`display=block` shows the formula on a separate line, `display=inline`
compactly, as part of the text.

:::example {label="Inline and block formulas"}
```markdown
:::math {display=inline label="Pythagorean theorem"}
a^2 + b^2 = c^2
:::

:::math {display=block label="Integral from zero to infinity"}
\int_0^\infty e^{-x}\,dx = 1
:::
```
:::

## States

### Without JavaScript

If scripts are turned off, the reader sees the TeX source text in a
monospaced font.

## Accessibility

- A formula is an element with the `math` role and a label from `label`: a
  screen reader reads the label.
- The label names the meaning of the expression: "Pythagorean theorem", not
  "a squared plus b squared".
- Without `label` the label is generic: "Mathematical formula" on an
  English page, "Математическая формула" on a Russian page. Give each
  formula its own label.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `display` | `inline`, `block` | `block` | `block` puts the formula on a separate line, `inline` inside the text. |
| `label` | string | `Mathematical formula` | The formula description for screen readers. |

<!-- docara:component-parameters:end -->

## See also

- [Diagram](../diagram/)
- [Code](../code/)
