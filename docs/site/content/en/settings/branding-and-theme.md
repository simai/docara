---
description: Logo, name, label, favicon and the "Made with Docara" line, site colours from one main colour, the light and dark scheme, blur and rounding.
tags: [logo, favicon, brand, theme, colour, colours]
---

# Brand and theme

The brand is the logo, the visible name, an optional label and the favicon.
These files belong to the project: a Docara update does not replace them. The
header is put together by the `docara.brand` Smart component, so the
settings need no HTML, template path or CSS classes. The theme is the site
colours and the light or dark scheme.

## Brand files

Put the images into the root `assets` folder:

```text
assets/
  logo.svg
  favicon.ico
```

Use relative paths without `..`, backslashes or a leading `/`. A symbolic
link instead of a file is not allowed. Give an SVG a `viewBox` so the image
scales on narrow screens.

SVG, PNG, JPG, WebP and ICO up to 2 MiB are allowed. A symbolic link, a path
into a reserved or output folder, and a dark logo without a main one are
refused before the previous build is cleared. The favicon can be SVG, PNG or
ICO.

## Settings in docara.json

Below is a complete minimal site file with branding:

```json
{
  "schema": "docara.site.v1",
  "title": "Acme Docs",
  "preset": "docs",
  "content_root": "content/en",
  "framework_lock": "simai-framework.lock.json",
  "default_locale": "en",
  "base_url": "/",
  "branding": {
    "title": "Acme",
    "mode": "compact",
    "size": "medium",
    "logo": "assets/logo.svg",
    "favicon": "assets/favicon.ico"
  }
}
```

`branding.title` is the product name and the accessible name of the link to
the home page. It is required even in the logo-only mode. One
`branding.logo` is used in the light and dark themes. Add `logo_dark` only
when the brand really has a separate file for a dark background.

`branding.mode` controls only the arrangement:

| Value | What is visible | When to use |
| --- | --- | --- |
| `compact` | the logo and the name in one line | the recommended option for documentation |
| `full` | the logo, the name and an optional label | when the label carries important meaning |
| `logo` | the logo only | for a recognizable mark; `title` stays available to a screen reader |
| `text` | the name and an optional label without a logo | when there is no graphic mark |

The `logo` mode shows only the logo in the header, without a text name;
`title` stays the accessible name of the home link. The logo is drawn with
`<img>`, so for the dark theme set a separate file in `logo_dark`.

`branding.label` is a short second line, for example "Documentation" or
"API". It is shown only in `full` and `text`; do not add it just to fill
space. `branding.size` takes `small`, `medium` or `large` and changes the
whole brand group by the SIMAI Framework scale: the logo height is 24, 32 or
40 px, and the width of a wide logo follows its proportions. A long name
stays on one line and is shortened with an ellipsis without breaking the
header buttons.

For example, the logo-only option:

```json
{
  "schema": "docara.section.v1",
  "branding": {
    "mode": "logo",
    "size": "medium"
  }
}
```

`header` is a layout region, not a component. By default it holds the system
section `docara.header`, which calls the `docara.brand` Smart component. The
component gets an already checked `branding` object; there is no need to put
logo HTML or a template path into the author's JSON.

## A section's brand

In `content/api/section.json` you can change the label for the folder and its
children:

```json
{
  "schema": "docara.section.v1",
  "branding": {
    "label": "API Reference",
    "mode": "full"
  }
}
```

The other fields are inherited from `docara.json`. If you need an entirely
different set, start the object with `"$reset": true` and set all the needed
fields explicitly (see
[inheritance and reset](/en/settings/levels-and-inheritance/#inheritance-and-reset-examples)).

## The "Made with Docara" line

At the bottom of the left menu of every page with a menu, Docara shows a
small "Made with" line with the Docara logo 14 px high; on Russian pages it
reads «Сделано на» with the same logo. The whole line is one link to
<https://docara.simai.io/>, and it opens in the same tab. Screen readers read
it as "Made with Docara", and the same phrase shows in the tooltip.

The logo is embedded in the page as SVG and takes its colour from the line's
text (`on-surface-variant`) in two grey tones: the ring is more contrasting,
the letters more muted. So one logo suits the light and dark themes, and on
hover the line becomes more contrasting.

The line is fixed at the bottom of the menu panel and separated from it by a
thin rule: the menu scrolls above it, and the line itself is always visible,
however long the navigation is. In the mobile menu the line is fixed at the
bottom of the panel too. Pages without a left menu (a landing page) do not
have the line.

To remove the line from the whole site, set in `docara.json`:

```json
{
  "branding": {"credit": false}
}
```

The line disappears entirely, together with the rule above it. `credit` is
`true` by default. It is a site-wide setting: `section.json` and
`*.page.json` do not accept the key, and the build stops with
`BRANDING_CREDIT_SITE_ONLY`. The reader does not change it, and it is not in
the settings panel.

## Light and dark scheme, blur and rounding

The `settings` branch is inherited and can be overridden in `section.json`
and `<page>.page.json`. Its fields accept only values from the list:

```json
{
  "settings": {"theme": "system", "modal_blur": "none", "ui_radius": "default"}
}
```

- `settings.theme` — `system`, `light` or `dark`: the scheme on the first
  visit. The reader can pick their own in the
  [reader panel](/en/settings/reader-preferences/), and their choice wins.
- `settings.modal_blur` — `none`, `small`, `medium` or `large`: the blur of
  the page behind the search and settings windows. The default is `none`: no
  blur, only the standard dimming of the backdrop.
- `settings.ui_radius` — `default`, `medium` or `large`: the shared Framework
  token `--sf-radius--ui`. `default` keeps the Framework value
  (`--sf-radius-1/3`), `medium` sets `--sf-radius-1/2`, `large`
  `--sf-radius-1`. One token changes, and the components use their own
  variables with a fallback value from it.

Only the author sets blur and rounding: they are not in the reader panel.
Arbitrary CSS from the settings is not accepted — the Framework builds the
styles.

## Site colours from one colour

The `theme` key in `docara.json` sets the main colour of the site, and Docara
derives the other colours from it:

```json
{
  "theme": {"color": "#0a7d4f"}
}
```

At build time Docara passes the colour to the SIMAI Framework theme
generator (`SF.ThemePalette`, the same calculation as the
`sf-theme-generator` component). The generator builds eight tonal palettes:
primary, secondary, tertiary, neutral, error, warning, success and info, 16
tones each — 128 `--sf-<palette>-<tone>` variables — and 98 of their
translucent shades `--sf-<palette>-<tone>--alfa-<n>`, 226 variables in all.
The Framework takes all semantic colours from them (`--sf-primary`,
`--sf-surface`, `--sf-outline` and others), so buttons, links, background and
borders change in the light and dark schemes at once. Examples in frames get
the same colours together with the other page tokens.

- `color` — required, `#rgb` or `#rrggbb`, case does not matter.
- `hue` (0–360) and `chroma` (0–150) — optional, the generator's hue and
  saturation, as in the "Fine tuning" fields of `sf-theme-generator`. When
  set, they win over how the colour was read. If the site keeps a choice from
  `sf-theme-generator`, move all three values into `theme`: `value` as
  `color`, `hue` and `chroma`.
- Without the `theme` key the site uses the Framework's own colours, and the
  build matches a build without this setting byte for byte.

The main palette tone need not match the chosen colour: the generator takes
the hue and saturation of the colour and builds the palette by its own rules.
For `#0a7d4f` the main colour of the light scheme (`--sf-primary-40`) comes
out as `#006d44`.

The translucent shades give container fills, tonal buttons and the hover or
selection background, so they follow the chosen colour too.

The theme is shared by the whole site and is set only in `docara.json`, not
in `section.json` and not on a page. Do not confuse it with
`settings.theme`: that one picks the light or dark scheme, while `theme` sets
the colours themselves.

The build publishes the theme as one file,
`_docara/docara-theme.<sha256>.css`, which every page links after its
first-screen styles. The build records the link to this file and everything
needed for the check in `resolved-page-plans.json` (the `build.theme`
section), and `docara verify-static` checks that the file matches the record,
holds only these variables (the tones and their translucent shades) and is
linked on every page.

Errors:

- `THEME_COLOR_INVALID` — `color` is neither `#rgb` nor `#rrggbb`, for example
  `"green"` or `"#0a7d4"`;
- `THEME_HUE_INVALID`, `THEME_CHROMA_INVALID` — a number out of range;
- `THEME_FRAMEWORK_UNSUPPORTED` — `simai-framework.lock.json` pins a
  Framework release without the theme generator. Replace the lock with
  `vendor/simai/docara/stubs/portable/simai-framework.lock.json`;
- `PORTABLE_INCREMENTAL_THEME_CHANGED` — after a colour change only one page
  is rebuilt (`build --page`). Build the whole site: the other pages link to
  the previous theme.

## Checking

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Check the header, the favicon, the light and dark themes, then stop the
preview with `Ctrl+C`. If a file is missing or lies outside the project, the
build must stop before it replaces the previous output.

## See also

- [Layout regions](/en/settings/layout-and-regions/) — the header as a layout region
- [Reader panel](/en/settings/reader-preferences/) — the reader's choice of scheme
- [The branding, settings and theme fields](/ru/reference/docara-json/#внешний-вид) (in Russian)
