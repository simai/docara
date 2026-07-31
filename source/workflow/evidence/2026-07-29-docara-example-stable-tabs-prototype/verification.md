# Docara Example component verification

Date: 2026-07-29
Result: PASS

## Implementation

- The portable renderer emits one product-owned Example surface without the
  old `sf-tabs` and button-style overlap.
- Tabs use Framework typography and spacing tokens.
- The copy action uses the official adaptive size-1 icon-button contract.
- One shared logical-direction indicator is positioned from real tab geometry
  after fonts load, after selection changes and after viewport resize.
- Preview and source panels share one grid cell, preserving stable component
  height between states.

## Automated checks

- PHP syntax: PASS.
- JavaScript syntax: PASS.
- Focused PHPUnit: 53 tests, 267 assertions, PASS.
- Full PHPUnit: 336 tests, 6701 assertions, PASS.
- Production build repeated twice: byte-identical, PASS.
- Static verification: 200 HTML pages, 17730 local references, 0 broken.
- `git diff --check`: PASS.

## Browser acceptance

URL: `https://docara.test/ru/components/badge/`

| Viewport | Tab text | Copy control | Copy glyph | Indicator |
| --- | ---: | ---: | ---: | --- |
| Desktop 1440 px | 16 px | 40 x 40 px | 24 x 24 px | exact tab edges |
| Mobile 390 px | 14 px | 36 x 36 px | 20 x 20 px | exact tab edges |

- Example and Markdown labels are vertically centred.
- Both tabs have equal Framework `space-1` inline padding.
- The copy action is hidden for Example and visible for source.
- Arrow Left returns focus and selection from Markdown to Example.
- Light theme uses `surface-0` `rgb(255, 255, 255)` for the surface and header.
- Dark theme uses `surface-0` `rgb(15, 17, 21)` for the surface and header.
- Console and page errors: 0.

Screenshots:

- `desktop-served.png`;
- `mobile-served.png`;
- `light-theme.png`;
- `dark-theme.png`.

## Local publication and rollback

- Served tree: `/Users/rim/Sites/docara.test/build_production`.
- Exact candidate diff against served tree: 0.
- Pre-correction backup:
  `/Users/rim/Sites/docara.test/.docara-backups/example-stable-tabs-20260729-155252/build_production.previous`.
- Pre-icon-correction backup:
  `/Users/rim/Sites/docara.test/.docara-backups/example-icon-correction-20260729-155601/build_production.previous`.

No merge, tag, package release or public deployment was performed.
