---
description: "How to show a trusted HTML fragment in an isolated frame: without access to the documentation page and without scripts."
---

# Isolated HTML

The block shows an HTML fragment in an isolated frame. The markup cannot see
the documentation page and runs no scripts. Choose it only when what you
need cannot be written in Markdown.

## Example

:::example {label="Isolated HTML"}
````markdown
:::html
```html
<article>
  <h2>A self-contained fragment</h2>
  <p>The markup is displayed separately from the documentation interface.</p>
</article>
```
:::
````
:::

## Markup

~~~markdown
:::html
```html
<p>A trusted static fragment.</p>
```
:::
~~~

- Inside is exactly one code block with the `html` language. If there is
  none or there are several, the build stops with the error
  `MARKDOWN_HTML_FENCE_REQUIRED`.
- The site styles do not get inside: the fragment looks as it was written.

## Variants

The block has no styling parameters.

## States

### Isolation

Docara places the markup in a separate document inside an `iframe` with an
empty `sandbox` attribute. The browser applies all restrictions to such a
frame at once:

- scripts do not run, forms are not submitted;
- the markup cannot see the documentation page, its styles or the reader's
  data;
- the frame cannot open a window or go to another page.

The frame loads only when the reader reaches it.

## Accessibility

- The frame is called "HTML example" on an English page and "Пример HTML"
  on a Russian page. If the fragment matters for understanding, describe it
  in the text next to it.
- The usual HTML rules work inside the frame: write headings, image
  descriptions and field labels in the fragment itself.
- For text, links, images and components choose Markdown: search, the
  outline and screen readers read it.

## Parameters

<!-- docara:component-parameters:start -->

No parameters.

<!-- docara:component-parameters:end -->

## See also

- [Embed](../embed/)
- [Interactive example](../example/)
- [Security](/en/reference/security-and-errors/)
