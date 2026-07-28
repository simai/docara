# Docara header navigation acceptance

Date: 2026-07-24
Workflow: `2026-07-24-docara-header-navigation`
Verdict: `PASS`

## Accepted outcome

Docara now supports an optional inherited `header_navigation` configuration
whose labels, order and item count may differ for every locale. The existing
product-owned Smart component `docara.navigation` renders the horizontal
desktop view and the compact mobile projection. Mobile primary links and the
documentation tree share one trigger and one dialog.

## Contract evidence

- descriptors: site, section and page schemas reference the shared strict
  `header_navigation` definition;
- enabled menus require 1–8 items;
- item identifiers, localized labels and safe internal, fragment or HTTPS
  links are validated;
- unknown properties, unsafe URLs, invalid identifiers, empty enabled menus
  and duplicate IDs fail closed;
- absent or disabled configuration emits no header navigation wrapper or
  primary-navigation trigger on pages without another navigation surface;
- lists replace as a whole through the existing inheritance model.

## Automated evidence

- full suite: `326 tests`, `5022 assertions`, `PASS`;
- post-format focused suite: `6 tests`, `246 assertions`, `PASS`;
- multilingual documentation fixture:
  - RU: 2 primary items;
  - EN: 3 primary items;
  - AR: 1 primary item with `dir="rtl"`;
- Pint on affected PHP surfaces: `PASS`;
- `git diff --check`: `PASS`.

## Build evidence

Source build:

- pages built: `90`;
- static HTML pages: `198`;
- local references checked: `11263`;
- broken references: `0`.

Published local target:

- URL: `https://docara.test/ru/`;
- directory: `/Users/rim/Sites/docara.test/build_production`;
- deployed verification: `PASS`, same `198 / 11263 / 0` result;
- rollback copy:
  `/Users/rim/Sites/docara.test/.docara-backups/header-navigation-20260724-172500`.

## Browser evidence

### Wide desktop — 1440 × 900

- horizontal header navigation is visible between brand and actions;
- four configured RU items are present;
- `Главная` has `aria-current="page"`;
- mobile trigger is hidden;
- no horizontal overflow.

### Narrow desktop — 1024 × 768

- horizontal primary navigation is hidden at the deterministic breakpoint;
- one primary mobile trigger is rendered;
- documentation sidebar remains available;
- no horizontal overflow.

### Mobile — 390 × 844

- exactly one `docara-mobile-navigation` dialog and one corresponding trigger;
- docs page contains `Главное` and `Документация` sections in that dialog;
- active primary and documentation links both resolve to `Быстрый старт`;
- `Escape` closes the dialog, resets `aria-expanded` and returns focus to the
  trigger;
- no horizontal overflow.

### RTL

- isolated AR fixture built and passed static verification:
  `85 HTML pages`, `1707 references`, `0 broken`;
- root markup uses `lang="ar"` and `dir="rtl"`;
- three independent Arabic primary items render;
- the logical mobile drawer opens from the right edge;
- active state and no-overflow checks pass.

### Runtime

- browser console warnings/errors: none.

## Boundaries

This accepts the local Docara implementation and local test-site publication.
It does not claim a public release, package publication, merge, tag or
production readiness.

## Follow-up: border refinement

The permanent one-pixel inline separators on Framework menu elements were
traced to two layers of the component output:

- independent directional border-width custom properties;
- duplicate physical `1px` declarations in the published Framework
  `menu.css`, which keep the computed inline borders visible even when every
  custom property resolves to `0rem`.

The Docara navigation view now sets every directional property and a
logical-axis fallback to `var(--sf-0)`. This keeps active and hover backgrounds
plus the explicit keyboard focus outline while removing persistent item
frames. The fallback is isolated to the product-owned navigation Smart
component and is removable when the Framework generator is corrected.

Browser interaction review also found that the established focus rule covered
the tree link class but not the new header link class. Both views now share the
same `focus-visible` rule using Framework width and offset tokens.

Follow-up verification:

- focused PHP suite: `35 tests`, `851 assertions`, `PASS`;
- final CSS-focused suite: `34 tests`, `787 assertions`, `PASS`;
- Pint and `git diff --check`: `PASS`;
- source and deployed static builds: `198 HTML pages`, `11263 local
  references`, `0 broken`;
- light and dark themes: every header item computes to `0px` on all four border
  sides;
- active item background and hover background remain visible;
- keyboard focus computes to a `4px` Framework outline with a `2px` tokenized
  offset;
- desktop horizontal overflow: `0`;
- rollback copy:
  `/Users/rim/Sites/docara.test/.docara-backups/header-border-refinement-20260724-175232`.

## Follow-up: unified header height

The header previously mixed three Framework sizes:

- `docara.brand` medium mark: `32px`;
- active primary navigation item: `48px`;
- settings control: `40px`.

Browser inspection also proved that the outline search button rendered at
`42px`, despite declaring Framework button size `1`, because its two one-pixel
borders increased the resulting box. The correction uses the existing
Framework scale instead of pixel constants:

- branding configuration selects `large`, whose Docara Smart contract maps the
  mark to `var(--sf-d0)`;
- the header view of `docara.navigation` uses `min-block-size: var(--sf-d0)`
  and Framework utility `h-d0`;
- the search Smart component keeps size `1` and receives `h-d0` through its
  supported `root-class`;
- settings and the mobile navigation trigger use icon-button size `1`.

Final verification:

- full PHP suite: `326 tests`, `5037 assertions`, `PASS`;
- Pint and `git diff --check`: `PASS`;
- source and deployed static builds: `198 HTML pages`, `11263 local
  references`, `0 broken`;
- browser computed height: brand mark `40px`, brand link `40px`, active menu
  item `40px`, search `40px`, settings `40px`;
- all four measured desktop controls share top `16px`, bottom `56px` and
  vertical center `36px`;
- active menu background remains visible;
- no horizontal overflow;
- rollback copy:
  `/Users/rim/Sites/docara.test/.docara-backups/header-height-unification-20260724-181608`.

## Follow-up: search outline and shortcut hierarchy

The search control now uses the quieter `outline-variant` token through the
button component's supported `--sf-button--border-color` contract. A generic
border utility was deliberately not used because the Framework component
layer owns the final border declaration.

The keyboard shortcut remains semantic `<kbd>`, but no longer looks like a
second button:

- its independent border, radius and padding are removed;
- `text-1` matches the Framework `size="1"` button label;
- `color-on-surface-variant` keeps the hint visually secondary;
- `m-inline-start-1/2` supplies a logical, RTL-safe Framework spacing token.

Verification:

- full PHP suite: `326 tests`, `5047 assertions`, `PASS`;
- Pint and `git diff --check`: `PASS`;
- source and deployed static builds: `198 HTML pages`, `11263 local
  references`, `0 broken`;
- browser computed search height: `40px`;
- button border: `1px rgba(118, 119, 124, 0.24)`, equal to
  `--sf-outline-variant`;
- dark-theme border changes with the same token contract to
  `rgba(215, 215, 220, 0.24)`;
- label and shortcut typography: both `16px / 24px`;
- shortcut border and padding: `0px`;
- logical shortcut margin and measured visual gap: `12px`;
- horizontal overflow: `0`;
- rollback copy:
  `/Users/rim/Sites/docara.test/.docara-backups/search-button-refinement-20260724-193745`.
