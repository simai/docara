# Browser acceptance: documentation shell vertical

Date: 2026-07-18
URL:
`https://docara.test/authoring/layout-and-navigation/hierarchy/four-level/`
Browser: Chrome controlled through the Codex browser session
Candidate: pending the implementation commit created after these checks

## Clean-load result

A fresh QA tab loaded the published candidate and reported an empty browser
error/warning log. The page exposed:

- the branded `Docara / Документация` home link and visible logo;
- the Framework theme action;
- a desktop complementary navigation region;
- the active fourth-level page and three expanded ancestors;
- the expected main heading and article content.

The pinned `<sf-icon>` integration rendered the theme glyph at 20 px and menu
disclosure glyphs at 16 px. A redundant Docara icon-attribute update found
during QA was removed; a second fresh tab then completed every interaction
below with `logs: []`.

## Desktop interaction

Default viewport: 1296 x 657 during the visual inspection.

- Desktop sidebar: visible and sticky.
- Active link:
  `/authoring/layout-and-navigation/hierarchy/four-level/`.
- Open ancestor depths: 1, 2, and 3.
- Collapse depth 3: `aria-expanded=false`, `open=false`, glyph
  `expand_more`, active child not visible.
- Expand depth 3: `aria-expanded=true`, `open=true`, glyph `expand_less`,
  active child visible.
- Theme action changed `theme-dark` to `theme-light`, updated the accessible
  label, and produced no runtime log entry.
- Pressing Enter on the native `Макеты и навигация` link opened
  `/authoring/layout-and-navigation/`; it did not toggle the branch.
- The document had no horizontal overflow.

Both light and dark themes were visually inspected. The logo, border/surface
tokens, menu states, article typography, and Framework icons remained legible
in both themes.

## Mobile interaction

Explicit acceptance viewport: 390 x 844, reset after verification.

- Desktop sidebar computed display: `none`.
- Mobile `Разделы` disclosure computed display: `block`.
- Opening it exposed the active page and a maximum rendered depth of 4.
- The active page retained `aria-current=page`.
- Horizontal overflow: none.
- Pressing Escape from the active link closed the navigation and moved focus
  back to the native `SUMMARY` element with text `Разделы`.
- Browser log after open, close, theme and menu interactions: `[]`.

## Accessibility and scope verdict

PASS for the first product vertical:

- direct link and disclosure semantics are separate;
- active trail and expanded state are programmatically exposed;
- the mobile disclosure has Escape/focus return;
- keyboard Enter follows a page link;
- focus styles exist for skip link, brand, links, disclosure controls, theme,
  mobile summary, and rendered Framework buttons;
- reduced-motion CSS is present;
- there is no unsupported ARIA tree claim.

This is not acceptance of search, right TOC, breadcrumbs, previous/next,
reading settings, or the final landing/component product surface.
