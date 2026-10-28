---
description: What Docara does for accessibility by itself, what is left to the author — image descriptions, links, headings, tables — and how to check it.
tags: [accessibility, a11y, screen-reader, keyboard]
---

# Accessibility

A Docara site must work for every reader: people who use only the keyboard, a
screen reader or a large font, or who turned animation off. Docara takes care
of most of it. The rest depends on the text the author writes.

## What Docara does by itself

- **Page structure.** The header, menu, main text, page outline and footer are
  `header`, `nav`, `main`, `aside` and `footer` elements, and each menu has its
  own name. A screen reader can jump between these parts.
- **Skip to content.** The first link of the page is "Skip to content": it
  appears on Tab and moves focus straight to the text, past the header and the
  menu.
- **Current page.** The menu item and the breadcrumbs of the current page are
  marked for screen readers (`aria-current`).
- **Language.** The page states its language and writing direction (`lang`,
  `dir`), and the screen reader picks its pronunciation from them.
- **Keyboard.** Menus, tabs, expandable blocks, reader settings and copy
  buttons work from the keyboard, and the focus ring is visible. The details
  are on the component pages, in the "Accessibility" section.
- **Reduced motion.** If the reader's system asks for reduced motion, Docara
  turns off smooth scrolling and animations, and the background effect of the
  first screen shows a still frame.
- **Font size and theme.** The reader can enlarge the font and pick a light
  or dark theme in the [reader settings](/en/settings/reader-preferences/).
  The size changes in percent, so it respects the browser's own
  setting too.

## What is left to the author

| What | How to do it right |
| --- | --- |
| Image description | Say in the square brackets what is shown and why: `![Build scheme: Markdown, check, finished site](scheme.webp)`. Leave a decorative image with an empty description `![](…)`. |
| Link text | Say where the link goes: "Open the quick start", not "here" or "more". |
| Headings | One `#` heading per page, then in order: `##`, `###`, without skipping levels. Screen readers build an outline from them. |
| Tables | The first row holds the column names. Do not use a table to lay text out in columns. |
| Colour | Do not carry meaning by colour alone: "the red button" does not help someone who cannot tell colours apart. Add a word or an icon. |
| Video and sound | Provide captions or a text transcript next to it. |
| Component labels | Fill `label` for examples and embeds and `title` for diagrams: it names the frame for a screen reader. |

## How to check

**The quality report.** `docara qa report` checks what can be found in the
text automatically: images without a description, links like "here", heading
order, tables without a header row and the page language. The finding codes
are in the [«Доступность»](/ru/reference/errors/quality/#доступность) part
of the error reference (in Russian).

```bash
php vendor/bin/docara build production
php vendor/bin/docara qa report --env=production
```

**By hand.** Open the page and go through it with the keyboard only: Tab,
Shift+Tab, Enter, Space, the arrows in tabs and menus. Check that focus is
always visible and no part of the page stays out of reach. Turn on a screen
reader — VoiceOver on macOS and iOS, NVDA on Windows, TalkBack on Android —
and listen to the start of the page. Look at the page in the light and dark
themes and with a large font.

An automatic check does not replace a manual one: it cannot tell whether an
image description is clear or the reading order is right.

## See also

- [Documentation quality](/en/authoring/documentation-quality/)
- [Reader panel](/en/settings/reader-preferences/)
- [Figure](/ru/components/figure/) (in Russian) — image description and caption
- [Quality report codes](/ru/reference/errors/quality/) (in Russian)
