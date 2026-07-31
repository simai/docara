# Docara example header alignment

Date: 2026-07-29
Status: complete
Workflow ID: `2026-07-29-docara-example-header-alignment`

## Goal

Keep every example tab on one baseline and render the copy action as a normal
SIMAI Framework size `1` control without product-owned geometry overrides.

## Implementation contract

- neutralize the document-flow margin on every tab and header action with the
  Framework utility `m-0`;
- retain the Framework size `1` tab and button contract;
- use the official Framework tab-button structure with
  `sf-button-text-container`;
- render copy as the Framework `sf-icon-button` size `1`; its icon inherits
  the component size instead of overriding it locally, so the action occupies
  the expected 40 by 40 px control geometry;
- do not add custom height, padding, transform, or absolute positioning CSS.

### Follow-up: header breathing room and copy icon

- set the public Framework tab-container padding variables to
  `var(--sf-space-1\/3)`; a generic `p-y-1/3` utility is not used because the
  component layer intentionally owns and overrides that padding;
- render the copy glyph through the official `<sf-icon>` Smart element instead
  of product-owned icon markup;
- verify that the underline remains visible and correctly aligned after the
  vertical padding is applied;
- do not introduce Docara CSS for the spacing, icon, or underline.

### Follow-up: divider and optical edge alignment

- align the Framework tab list to the cross-axis end with
  `self-cross-end`, so its active indicator reaches the shared header
  divider despite the vertical container padding;
- compensate the public tab-list margin by the divider width and
  `space-1/3`, without transforms or product-owned positioning CSS;
- apply `space-1/3` only at the logical `inline-end` of the header: the
  Framework tabs and icon button already provide their own inner insets;
- preserve logical-direction behavior for RTL.

## Verification

- focused renderer and catalog tests;
- production build and static verification;
- browser-computed geometry for both tabs and the copy action;
- live browser geometry before and after changing the active tab;
- HTTP smoke of the component page.

## Result

- both tabs have identical top, bottom, height, padding and zero margins;
- after Framework initialization each tab is 42 px high including its 2 px
  underline, while the size `1` copy action is centered at 40 by 40 px;
- the shared header has 8 px (`space-1/3`) padding above and below;
- after switching to `Markdown`, the selected tab keeps its 2 px underline;
- the copy action is 40 by 40 px and its loaded Framework icon is 24 by 24 px;
- the selected underline meets the header divider with a measured `0 px`
  gap for both `Пример` and `Markdown`;
- the measured optical inset is symmetrical: `16 px` from the left edge to
  `Пример` and `16 px` from the copy glyph to the right edge;
- the final header keeps `8 px` vertical padding, `0 px` inline-start
  padding and `8 px` inline-end padding; component-owned inner insets produce
  the equal visible edges;
- `tightness` is intentionally not used because it would make the control
  smaller than size `1`;
- focused PHPUnit: 66 tests, 1,880 assertions;
- full PHPUnit: 336 tests, 6,700 assertions;
- production build: 100 pages;
- static verification: 200 HTML pages, 17,730 references, 0 broken;
- local rollback snapshot:
  `/Users/rim/Sites/docara.test/.docara-backups/example-header-spacing-20260729-113122/build_production.previous`;
- local smoke: `https://docara.test/ru/components/badge/` returns HTTP 200.
- follow-up focused PHPUnit: 53 tests, 266 assertions;
- follow-up static verification: 200 HTML pages, 17,730 references, 0 broken;
- follow-up rollback snapshot:
  `/Users/rim/Sites/docara.test/.docara-backups/example-header-edge-alignment-20260729-122322/build_production.previous`.

## Boundary

Local Docara implementation and local test-site publication only. No merge,
tag, package publication, public deployment, or production-readiness claim.
