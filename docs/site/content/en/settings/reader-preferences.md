---
description: The "Settings" menu under the ⋮ button — theme, font size, page width, reading mode, which settings docara.json shows and how the browser keeps the choice.
tags: [reader-panel, reading-settings, theme, font, reading-mode]
---

# Reader panel

The ⋮ button on the right of the header opens the "Settings" menu. It is
built by the `docara.preferences` product Smart component: the menu itself
is a SIMAI Framework context menu (`sf-context-menu`) that opens under the
button over the page.

Out of the box the menu has three rows: "Theme", "Font size" and "Page
width". The current value is shown on the right of a row. A row opens a
submenu with the values: next to the menu on a wide screen, under the row on
a phone. The selected value has a check mark. A change applies at once,
nothing needs saving. The "Reset settings" item at the bottom returns all
values to the site's values.

From the keyboard the menu opens with the ⋮ button, and focus lands on the
first row. The up and down arrows move between rows, the right arrow, Enter
or Space open a submenu, and the left arrow closes it and returns to the row.
With a mouse a submenu also opens on hover. Escape, Tab, another press on ⋮
or a click outside the menu close it and return focus to the button.

## Settings view

`reader_preferences.view` picks the view:

| Value | What it looks like |
| --- | --- |
| `menu` | A context menu under the ⋮ button · **Default** |
| `side-panel` | A full-height "Settings" side panel on the right, with the page dimmed |

In the side panel each setting is a label and a segmented switch from a
SIMAI Framework button group, and `sf-modal` provides the window behaviour,
focus and closing with `Escape`.

## Theme

The theme has three values:

- **Use system setting** — the theme follows the device mode;
- **Light** — the light theme is always used;
- **Dark** — the dark theme is always used.

## Font size

**Small**, **Normal** and **Large** change the size of the whole
documentation interface: the header, left menu, page outline, breadcrumbs and
text. The 16 px body text becomes 14 px or 18 px, and the menus, spacing and
controls change with it.

Docara sets the root font size to 87.5% or 112.5%. SIMAI Framework
typography, spacing, controls and container widths are in `rem`, so the
whole interface scales consistently, as when the text size is changed in the
browser. Percentages keep the reader's own browser base setting. Examples in
isolated frames get the page's root size when they are prepared.

## Page width

**Normal** uses the grid width the author set with `layout.container.max`.
**Wide** takes the widest SIMAI Framework container step — the same as
`layout.container.max: 8` (`112rem`) — for the header, the documentation grid
and the footer, and lifts the line-length limit of paragraphs and lists. The
width does not grow forever: on a large screen or at a reduced browser zoom
it stops at that step. If the site already uses `max: 8`, the wide format
changes only the line length.

## Contents and reading mode

To the right of the breadcrumbs there are two buttons: "Reading mode" first,
then the contents button, closest to the column it controls.

- **Hide page contents** (the `dock_to_left` icon) removes the right
  "Contents" column, and the text takes the freed space. Another press brings
  the column back. The button is visible only where the column is shown: on
  narrow screens the contents open with a separate button above the text.
- **Reading mode** leaves only the page text on screen: the header, left
  menu, contents and footer are hidden. A round exit button
  (`radius-rounded` from the Framework) appears in the top right corner of
  the window, and focus moves to it; you can also leave with `Escape`, after
  which focus returns to the reading mode button.

The contents button appears only on pages that have contents items: a page
without headings does not have it. The 404 page has no "Reading mode" button
either: reading mode would hide the header with search, which is needed most
there. If the reader arrives there in reading mode, the exit button stays.

Both buttons have a constant label and an `aria-pressed` state. The state is
kept for the whole site and applies on other pages, after a reload and in
other tabs.

## Default value

The author sets the site theme with the `settings.theme` field, which is
inherited and can be overridden in `section.json` and `<page>.page.json`
(see
[Brand and theme](/en/settings/branding-and-theme/#light-and-dark-scheme-blur-and-rounding)).
The reader's local choice wins. The "Reset settings" button removes all of
the reader's overrides and applies the current page's values again. For font
size and width the site value is "Normal".

`modal_blur` and `ui_radius` stay author settings: they apply before the
page is first shown, but they are not in the reader settings.

## Which fields to show

The set of settings is set only at the site level in `docara.json`:

```json
{
  "reader_preferences": {
    "enabled": true,
    "view": "menu",
    "groups": [
      {
        "id": "appearance",
        "fields": ["appearance.theme", "appearance.font_size", "appearance.content_width"]
      }
    ]
  }
}
```

Without the `reader_preferences` branch the same set is used. Theme, font
size and page width are built-in parameters: they are always shown, even if
the site lists the fields itself. Listed fields keep the group and order the
author gave, and a built-in parameter missing from the list is added at the
end of its group. To hide a built-in parameter, list it in `hidden_fields`:

```json
{
  "reader_preferences": {
    "enabled": true,
    "view": "menu",
    "hidden_fields": ["appearance.content_width"],
    "groups": [{"id": "appearance", "fields": ["appearance.theme"]}]
  }
}
```

A field cannot be listed both in a group and in `hidden_fields`: such a
configuration stops the build.

`enabled: false` removes the button, the Smart component and its assets
entirely. An unknown field or group, a repeat, an unsupported view or going
past the schema limits stops the build. The exception is the retired fields
`appearance.modal_blur` and `appearance.ui_radius`: if they remain in an old
configuration, Docara skips them. The branch is not allowed in
`section.json` and page sidecars: one site should not change the set of
reader settings from page to page.

The panel supports the keyboard, keeps focus inside itself and returns it to
the button after closing, closes with `Escape` and respects the reduced
motion mode.

New settings are added through a registry of definitions. A definition gives
the identifier, the group, the allowed values, the kind of control, a safe
effect, when it applies and the keys of the localized text. `docara.json`
only picks which registered fields to show and holds no executable code.

## Storage and privacy

Docara keeps one versioned JSON document in `localStorage`:

```json
{
  "schema": 1,
  "values": {
    "appearance.theme": "dark",
    "appearance.font_size": "large",
    "appearance.content_width": "wide"
  }
}
```

The key is isolated by the site's `base_url` and looks like
`docara.preferences.<site-hash>.v1`. Only differences from the author's
values are kept. Values of fields the site no longer offers are removed from
the document the next time a page opens. The state of the contents and
reading mode buttons is kept next to it, under the
`docara.reading.<site-hash>.v1` key. No account, server, analytics or cookies
are used.

The theme, font size, width and button state are restored by a script in
`<head>` before the page is first shown, so the page does not flash a wrong
layout. If persistent storage is not available, the choice works until a
reload, and the menu says so honestly. A change in another tab is synced
through the browser's `storage` event.

The reader's choice is kept only in the browser: it does not change the
source files or the build receipts and is not a source of project settings.

## See also

- [Brand and theme](/en/settings/branding-and-theme/) — the site's default theme
- [Reading context](/en/settings/reading/) — the page outline and breadcrumbs
- [The reader_preferences field](/ru/reference/docara-json/#меню-чтение-и-поиск) (in Russian)
