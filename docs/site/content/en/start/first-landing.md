---
description: A 15-minute tutorial — from an empty project to a landing page with a first screen, benefits and a call to action.
tags: [tutorial, landing, hero, first-screen]
profile: tutorial
---

# Tutorial: a first landing page

In this tutorial you build a landing page for a made-up notes service: a
first screen with a heading and a button, three benefits and a call to
action. It takes about 15 minutes.

You will go through six steps:

1. Create the project.
2. Describe the landing page.
3. Add the first screen.
4. Add the benefits.
5. End with a call to action.
6. Build the site and open it in the browser.

You need PHP 8.2+, Composer and Node.js 20+, as in the
[quick start](/en/start/quick-start/).

## 1. Create the project

```bash
mkdir notes-site
cd notes-site
composer require simai/docara:^3.0
php vendor/bin/docara init .
```

A small English site appears, with its home page in `content/en/index.md`.

## 2. Describe the landing page

A landing page is an ordinary page with a different frame: no side menu, and
blocks that span the full window width. A page settings file sets the frame.
Create `content/en/product.page.json`:

```json {title="content/en/product.page.json"}
{
  "schema": "docara.page.v1",
  "title": "Notes service",
  "description": "Landing page of a notes service: what it does and how to start.",
  "preset": "landing",
  "layout": { "container": { "max": 7 } },
  "navigation": { "hidden": true }
}
```

- `"preset": "landing"` picks the landing frame.
- `"navigation": { "hidden": true }` keeps the page out of the documentation
  side menu: people usually reach a landing page through links, not the menu.

## 3. Add the first screen

Create `content/en/product.md` with the first screen:

```markdown {title="content/en/product.md"}
:::hero {layout=centered padding=2xl}
# Notes that find themselves

The service collects notes from mail, chat and documents and shows them
where you work.

[Start for free](/getting-started/first-page/)
:::
```

Inside the [first screen](/ru/components/hero/) (in Russian) go one `#` heading, a description and a link that
becomes a button. `layout=centered` centres everything, `padding=2xl` makes
the screen tall.

## 4. Add the benefits

Append to the same file:

```markdown
## What the service does

:::features
- :icon[search]{size=2 container=square variant=tonal scheme=primary} **Search by meaning.** Finds a note even when you do not remember the exact words.
- :icon[sync]{size=2 container=square variant=tonal scheme=primary} **Sync.** The same notes on your phone and computer.
- :icon[lock]{size=2 container=square variant=tonal scheme=primary} **Privacy.** Notes are stored encrypted.
:::
```

Each list item in a `features` block becomes a card: an icon, a bold title
and text. Icon names are listed on the [icon page](/ru/components/icon/)
(in Russian).

## 5. End with a call to action

Append to the end of the file:

```markdown
## Try it today

The first 30 days are free, no card needed.

:::cta
[Create an account](/getting-started/first-page/)
:::
```

A `cta` block holds exactly one link. The heading and the explanation stand
before the block as ordinary text.

## 6. Build and open

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Open `http://127.0.0.1:8000/product/`. You see the first screen with the
heading "Notes that find themselves" and a button, then three benefit cards
and the "Create an account" button. Switch the theme in the ⋮ menu on the
right of the header and check the page in the light and dark themes.

Stop the preview with `Ctrl+C`.

## If something goes wrong

| Message | What to do |
| --- | --- |
| `MARKDOWN_CTA_LINK_REQUIRED` | A `cta` block must hold exactly one link and nothing else. Move the heading and the text before the block. |
| `MARKDOWN_BLOCK_UNCLOSED` | A `:::` line is missing after a block. Check that every block is closed. |
| `MARKDOWN_HERO_H1_REQUIRED`, `MARKDOWN_HERO_STRUCTURE_INVALID` | The first screen is out of order: first one `#` heading, then text, links and an image. |

Other codes are in the [error reference](/ru/reference/errors/content/) (in
Russian).

## What next

- Add a picture to the first screen with `layout=split` — see the
  [first screen page](/ru/components/hero/) (in Russian).
- See more blocks on [«Блоки лендинга»](/ru/components/landing-blocks/) (in Russian).
- Make a landing page the home page — the [home page of this documentation](/ru/landing/)
  (in Russian) is built that way.
