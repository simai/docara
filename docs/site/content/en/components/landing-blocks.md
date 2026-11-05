---
description: The features, showcase, promo, columns and cta landing blocks — feature cards, a result showcase, a final call to action, columns and an action button — with live examples and markup rules.
tags: [landing, landing page, features, showcase, promo, columns, cta]
---

# Landing blocks

You build a product page from these blocks: this site's [home page](/en/)
is made of them, a hero and collapsible questions. The blocks are written
in plain Markdown between `:::name` and `:::`. The content defines the look,
and SIMAI Framework sets the width, spacing and theme.

| Block | Purpose |
| --- | --- |
| [`features`](#features-feature-cards) | Two to six cards: an icon or an image, a heading and a short text |
| [`showcase`](#showcase-show-the-result) | A heading, an explanation, an action and a large image of the result |
| [`promo`](#promo-final-call-to-action) | The final block of the page with one action |
| [`columns`](#columns-columns) | Two to four columns of text side by side |
| [`cta`](#cta-action-button) | One prominent button link |

## Example

:::example {label="Feature cards"}
```markdown
:::features
- :icon[description]{size=2 container=square variant=tonal scheme=primary} **Markdown.** Pages are written as plain text.
- :icon[tune]{size=2 container=square variant=tonal scheme=primary} **JSON.** Settings are checked by schemas.
- :icon[public]{size=2 container=square variant=tonal scheme=primary} **HTML.** The finished site opens without a server.
:::
```
:::

## Markup

- A block starts with a `:::name` line without indentation and ends with a
  `:::` line.
- Blocks do not nest in each other or in other Docara blocks. Inside
  `columns` you can write plain Markdown.
- Links and images are only `https://` or paths inside the site.
- Breaking the rules stops the build with a clear code, for example
  `MARKDOWN_FEATURES_ITEM_COUNT_INVALID` or `MARKDOWN_CTA_LINK_REQUIRED`.
  All codes with causes and fixes are in the
  [page error](/en/reference/errors/content/) reference.

The hero is described on the [Hero](../hero/) page, collapsible questions
on the [Details](../details/) page. How the home page is built is told on
the [How the home page is built](/en/landing/) page.

### Features: feature cards

The block turns a bulleted list into a grid of cards. The list has two to
six items, each with one paragraph. An item can start with an `:icon[...]`
icon or an image: they go above the card text. Bold text at the start of an
item becomes the card heading.

:::example {label="Cards with icons"}
```markdown
:::features
- :icon[bolt]{size=2 container=square variant=tonal scheme=primary} **Fast.** The build outputs finished HTML, CSS and JavaScript.
- :icon[search]{size=2 container=square variant=tonal scheme=primary} **Search.** The index works in the reader's browser without a server.
- :icon[translate]{size=2 container=square variant=tonal scheme=primary} **Languages.** Each language is its own folder of Markdown.
:::
```
:::

An icon that is not written first stays an ordinary icon inside the text.
Instead of an icon you can put an image:
`- ![](../../assets/feature-layout.webp) **JSON.** Text.`
The image in a card is decorative, so its alt can stay empty.

:::example {label="Cards with images"}
```markdown
:::features
- ![](../../assets/feature-content.webp) **Markdown.** The content stays readable source text.
- ![](../../assets/feature-layout.webp) **JSON.** Settings are inherited and checked by schemas.
- ![](../../assets/feature-build.webp) **PHP.** The build does not need Node.js.
:::
```
:::

### Showcase: show the result

The block shows the result large: a second-level heading, one or two
paragraphs of description, an optional action link and one image on the
last line. The image must have a description (alt) because it carries
meaning. On a wide screen the text stands next to the image, on a narrow
one above it.

:::example {label="Result showcase"}
```markdown
:::showcase
## See the result before publishing

Docara builds the documentation interface from Markdown, JSON and checked
components.

[Open the quick start](/en/start/quick-start/)

![A documentation page in the light and dark themes](../../assets/showcase.webp)
:::
```
:::

### Promo: final call to action

The block closes the page: a second-level heading, one or two paragraphs
and exactly one action link. An image on the last line is optional. The
block stands out with a muted background. On this site's home page, promo
stands at the very end and leads to the quick start.

:::example {label="Final call to action"}
```markdown
:::promo
## Build your first site

The quick start takes you from an empty folder to a finished page in the browser.

[Go to the quick start](/en/start/quick-start/)
:::
```
:::

:::example {label="Call to action with an image"}
```markdown
:::promo
## One source for the site and the documentation

Markdown and JSON turn into a checked static site.

[How it works](/en/start/component-model/)

![](../../assets/promo.webp)
:::
```
:::

### Columns: columns

The block places two to four parts of text side by side. The parts are
separated by a separate `---` line with blank lines before and after it. On
a narrow screen the columns stack in the same order.

:::example {label="Three columns"}
```markdown
:::columns
### Markdown

The content stays readable source text.

---

### JSON

Settings are inherited and checked by schemas.

---

### PHP

The build does not need Node.js.
:::
```
:::

Inside a column you can write plain Markdown: paragraphs, lists, links. An
empty column stops the build.

### CTA: action button

The block shows one link as a prominent button. It holds exactly one link
with text.

:::example {label="Action button"}
```markdown
:::cta
[Open the component catalogue](/en/components/)
:::
```
:::

If there are several actions, use [button links](/en/components/button/)
in a paragraph or a showcase with links.

## Accessibility

- `features` cards and `columns` go in source order: on a phone they stack
  in the same order in which they are written.
- The image in `showcase` carries meaning, so it must have a description.
  Images in `features` and `promo` are decoration, their description can
  stay empty `![](…)`.
- Action links in `showcase`, `promo` and `cta` are plain links: Tab and
  Enter. Write the action in them: "Go to the quick start".
- Block headings (`##` in `showcase` and `promo`, `###` in `columns`) are
  part of the page structure: keep their levels in order.

## Parameters

<!-- docara:component-parameters:start -->

### `:::columns`

No parameters.

### `:::cta`

No parameters.

### `:::features`

No parameters.

### `:::promo`

No parameters.

### `:::showcase`

No parameters.

<!-- docara:component-parameters:end -->

## See also

- [Hero](../hero/)
- [Grid](../grid/) and [card](../card/) — your own card grids
- [Button link](../button/)
- [How the home page is built](/en/landing/)
