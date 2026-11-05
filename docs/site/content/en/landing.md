---
noindex: true
---

# How the home page is built

The Docara home page is both a product landing page and the entry to the
documentation. It is built from plain Markdown, typed Docara components and
SIMAI Framework utilities.

:::cta
[Open the home page](/)
:::

## Blocks used

:::features
- :icon[web]{size=2 container=square variant=tonal scheme=primary} **Hero and actions.** The centred first screen says who Docara is for and leads to the quick start. Instead of a screenshot, a background effect is drawn behind the text, so other languages need no new image.
- :icon[code]{size=2 container=square variant=tonal scheme=primary} **Code and features.** A short command block shows the first site, and cards with Material Symbols icons explain how Docara differs and what it gives out of the box.
- :icon[help]{size=2 container=square variant=tonal scheme=primary} **Details and Promo.** Expandable questions answer objections, and the final block leaves one action.
:::

The home page has no images with text: the icon of a features card is the
`:icon[...]` component written first in the list item, for example
`- :icon[bolt]{size=2 container=square variant=tonal scheme=primary} **Title.** Text.`
Such an icon sits above the card title, like an image.

The Markdown source of the home page is in `content/en/index.md` (and
`content/ru/index.md` for Russian). The first screen and the expandable
questions are described on the [Hero](/components/hero/) and
[Details](/components/details/) pages, the background animation on the
[Background effects](/components/effects/) page. The `features`, `showcase`,
`promo`, `columns` and `cta` blocks with live examples are described on the
[Landing blocks](/components/landing-blocks/) page. The other components are
listed in the [component reference](/components/).
