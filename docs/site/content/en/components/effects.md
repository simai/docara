---
description: "Background effects for the hero: the effect catalogue in docara.json, choosing an effect, theme and settings, the pause button, reduced motion, Content-Security-Policy requirements and build errors."
tags: [animation, animations, effect, effects, background]
---

# Background effects

A background effect is an animation under the text of the
[hero](/en/components/hero/): a starry sky, a grid, waves and others. The
effect replaces a background image, so nothing needs to be redrawn for the
site's other languages. The text over the effect stays ordinary page text:
it can be selected, translated and found by search.

SIMAI Framework draws the effects and takes them from the SIMAI effect
catalogue. Docara only records in the Hero which effect is needed and checks
the settings at build time.

## How to add an effect

### 1. Connect the catalogue

The catalogue is set once for the whole site in `docara.json`:

```json
{
  "effects": {
    "catalogue": "https://cdn.jsdelivr.net/gh/simai/ui-effects@ad508268dbf322ecaaf2820a06da8dc8285d4ec1/"
  }
}
```

`catalogue` is the catalogue address. Pin it to a specific commit, not to a
branch (`@main`): otherwise the effects on your site may change without your
knowledge. This site connects the catalogue at the address above.

Two more fields are optional:

| Field | Sets |
| --- | --- |
| `index` | The path to the effect list inside the catalogue. The default is `catalogue/index.json`. |
| `index_sha256` | The SHA-256 checksum of that list. If it is set, the browser does not run effects from a changed list. |

### 2. Choose an effect

All the catalogue's effects, how they look and their settings are collected
on [effects.simai.io](https://effects.simai.io/). You can also tune the
settings there and copy them. Docara does not download the catalogue during
the build, so the full list of effects is kept on that site, not here.

Note the effect's identifier: lowercase Latin letters, digits and hyphens,
for example `constellation-net` or `aurora-curtain`.

### 3. Set the effect in the Hero

:::example {label="Constellations"}
```markdown
:::hero {layout=centered effect=constellation-net}
# Documentation that is easy to read

The effect is drawn under the text and does not get in the way of reading.
:::
```
:::

The [home page](/en/) of this site opens with another effect, "Chladni
figures" (`chladni`), and its Hero has a large `padding=2xl`. An effect works
with any Hero variant, but not with a background image: set either `effect`
or `media=background`.

## Parameters

| Parameter | Value |
| --- | --- |
| `effect` | An effect identifier from the catalogue, 2 to 41 characters, or the `https` address of its `effect.json` file inside the catalogue. |
| `effect_theme` | The light or dark version of the effect: `auto` follows the page (the default), `dark` or `light`. |
| `effect_settings` | The effect settings — a JSON object in single quotes, for example `'{"speed": 80}'`. effects.simai.io shows which settings an effect has. |

```markdown
:::hero {layout=centered effect=aurora-curtain effect_theme=dark effect_settings='{"speed": 80}'}
# A dark hero

The effect is always drawn in its dark version, and the text becomes light.
:::
```

### Effect theme

With `effect_theme=auto` the effect follows the page theme: when the reader
switches the theme, the effect switches too. With `dark` or `light` the
effect always stays in one version, and the Hero text adapts to it. While
the effect loads, the Hero already has the background and text colour of
that theme, so the text is readable at once.

If the effect lacks the needed version, for example it only has a dark one
and the page is light, the effect is not shown, and the Hero looks as it
would without an effect.

## More effects

Below are three more effects from this site's catalogue. Each has a light
and a dark version: switch the site theme and the effect changes with it.
An effect moves only while it is visible on the screen and is not drawn at
all in a hidden tab.

:::example {label="Light conduits"}
```markdown
:::hero {layout=compact effect=hex-conduits}
# Light conduits

Light pulses run across a hexagonal grid.
:::
```
:::

:::example {label="Glyph tide"}
```markdown
:::hero {layout=compact effect=glyph-tide effect_settings='{"charset": "binary"}'}
# Glyph tide

A wave of characters. The `charset` setting picks binary characters instead
of hexadecimal ones.
:::
```
:::

:::example {label="Contour lines"}
```markdown
:::hero {layout=compact effect=contour-map}
# Contour lines

Elevation lines, as on a topographic map.
:::
```
:::

All the catalogue's effects and their settings are shown on
[effects.simai.io](https://effects.simai.io/).

## Pause and reduced motion

While the effect moves, the bottom right corner of the Hero (the left one in
right-to-left languages) has a "Pause background animation" button. It does
not cover the text or the image, even on a phone. Pressing it again starts
the effect again. The choice is remembered in the reader's browser for the
whole site.

The button fits into the Hero's bottom padding. With `padding=2xl` the Hero
height does not change. With `lg` and `xl` the bottom padding of a Hero with
an effect becomes slightly larger: 52 px on a narrow screen and 56 px from
960 px.

If the reader's system has reduced motion turned on, the effect shows a
single still frame, and the button does not appear.

The button labels come from the `effects.pause` and `effects.play` strings
of the `lang.json` file. They are built in for English and Russian; for
another language set them as described in the
[lang.json interface labels](/en/reference/lang-json/) reference.

## Where the effect will not be drawn

A site with an effect stays static, but the effect loads from the catalogue
in the reader's browser. The effect does not appear, and the Hero looks as
it would without it, if:

- the browser cannot download the catalogue files, for example the reader
  is offline;
- the effect file does not match the checksum in the catalogue list;
- the effect has no version for the needed theme;
- the site is built with a SIMAI Framework version without effect support
  (earlier than `ui-08c07b3e938a`). Docara ships with a suitable version.

Why the effect was not drawn is visible in the browser's developer tools:
the Hero section's `data-sf-effect-state` attribute is `refused` or
`failed`, and `data-sf-effect-reason` holds the reason, for example
`fetch-failed` or `integrity`.

## Content-Security-Policy

Docara does not set a Content-Security-Policy header. If your hosting adds
one, allow in it what the effect needs:

| Directive | What to allow | Why |
| --- | --- | --- |
| `connect-src` | the catalogue address, for example `https://cdn.jsdelivr.net` | the Framework downloads the effect list and the effect files and checks their checksums |
| `script-src` | `blob:` | the verified effect file runs from the browser's memory instead of being downloaded a second time |
| `script-src` | the page's inline scripts (`'unsafe-inline'` or their hashes) | a short `<script data-docara-effects>` script passes the catalogue address to the Framework |

Without these permissions the browser blocks the effect, and the Hero stays
without it.

## Build errors

The build stops if an effect is set incorrectly. The cause and the fix for
each error are in the reference:
[page errors](/en/reference/errors/content/) and
[site settings errors](/en/reference/errors/site/).

| Code | When it occurs |
| --- | --- |
| `MARKDOWN_HERO_EFFECT_INVALID` | `effect` is neither an effect identifier nor an `effect.json` address. |
| `MARKDOWN_HERO_EFFECT_REQUIRED` | `effect_theme` or `effect_settings` are set without `effect`. |
| `MARKDOWN_HERO_EFFECT_SETTINGS_INVALID` | `effect_settings` is not a JSON object. |
| `MARKDOWN_HERO_EFFECT_MEDIA_CONFLICT` | One Hero has both an effect and a background image. |
| `MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID` | `effect_theme` has a value other than `auto`, `dark` or `light`. |
| `EFFECTS_CATALOGUE_REQUIRED` | A page uses an effect, but `docara.json` has no `effects.catalogue`. |
| `EFFECTS_OUTSIDE_CATALOGUE` | The effect is given by an address outside the connected catalogue. Docara builds only the catalogue's effects. |
| `EFFECTS_CATALOGUE_INVALID` | The catalogue address is not `https://`, does not end with `/` or contains invalid characters. |
| `EFFECTS_INDEX_INVALID`, `EFFECTS_INDEX_SHA256_INVALID` | `index` is not a path to a `.json` file, or `index_sha256` is not 64 hexadecimal characters. |
