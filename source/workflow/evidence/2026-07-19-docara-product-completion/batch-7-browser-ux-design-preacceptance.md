# Batch 7 — mutable browser UX/design pre-acceptance

Date: 2026-07-19
Status: `PASS_MUTABLE_ONLY`
Target: `http://127.0.0.1:8127`
Build digest:
`dc6e2a997314a2497da29af3e696937d7f46aee2a832ed3166e2862cdd963675`

## Scenarios

### Catalogue index, 1440 by 900

- current left-navigation item is unambiguous;
- grouped two-column cards are compact and scan in a predictable order;
- headings and right TOC make all three supported families plus unavailable
  records discoverable;
- 12 supported full-surface links and five unavailable disclosures are present;
- root scroll width remains inside the viewport.

### `ui.alert` detail, 1440 by 900

- title, technical identifier, family and purpose establish a clear hierarchy;
- the live Framework example is visible before call syntax;
- call, parameters, states and provenance use one reusable sequence;
- previous/next and right TOC are retained;
- the parameter table has a local `overflow-x: auto` wrapper.

### Catalogue and `ui.alert`, 390 by 844

- the desktop sidebars collapse into the existing mobile navigation and TOC
  disclosures;
- breadcrumbs, title, purpose and live example retain their reading order;
- cards become one column without horizontal page overflow;
- root and body scroll widths remain 378 CSS pixels inside the 390 pixel
  viewport;
- the parameter wrapper is 278 pixels wide and contains its 1855 pixel table
  without widening the page.

## Visual verdict

`PASS_MUTABLE_ONLY`. The surface is simple, readable and consistent with the
accepted Docara shell and pinned Simai Framework. No new decorative system or
Docara-only layout primitive was introduced. Exact candidate keyboard,
light/dark and native-Chrome checks remain mandatory.
