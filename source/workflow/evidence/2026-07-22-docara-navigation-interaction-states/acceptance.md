# Acceptance: Docara navigation interaction states

Date: 2026-07-22
Candidate baseline: `8036a68`
Verdict: PASS WITH UPSTREAM NOTE

## Scope and scenario

Checked the product-owned `docara.navigation` Smart component on
`https://docara.test/ru/migration/`: whole-row pointer hover, disclosure icon
contrast, pointer activation, keyboard focus and toggle, responsive layout and
static output.

## Source and build evidence

- focused acceptance: 5 tests, 525 assertions, PASS;
- exact production build: PASS;
- static verifier: 271 HTML pages, 20,512 local references, 0 broken;
- Smart asset URL changed through its content-derived `docara_v` SHA-256;
- full PHPUnit: 623 tests, 5557 assertions, PASS;
- `git diff --check`: PASS.

## Browser evidence

Desktop before correction:

- row hover background: transparent;
- button hover background: `rgba(118, 119, 124, 0.01)`;
- icon changed from `rgb(227, 226, 231)` to `rgb(144, 144, 149)`;
- pointer click: `:focus-visible=false`, but Framework focus box-shadow remained.

Desktop after correction:

- button hover and label hover both activate the row hover surface;
- icon remains `rgb(227, 226, 231)`;
- pointer click expands the submenu with `box-shadow:none` and
  `:focus-visible=false`;
- keyboard focus has a 3 px solid outline and `:focus-visible=true`;
- Space collapses the submenu, synchronizes `aria-expanded=false`, and retains
  keyboard focus;
- active page and navigation hierarchy remain unchanged.

Mobile `390 x 844`:

- mobile navigation opens;
- disclosure remains Framework-sized at approximately `21 x 22.5 px` under
  the pinned 14 px mobile root;
- no horizontal page overflow.

## Remaining risk

The pinned Framework runtime continues to emit a pre-existing asynchronous
error from `distr/core/js/556.js`: `Cannot set properties of null (setting
'data')`. The inspected navigation interactions remain operational. This
candidate does not change Framework source or claim that upstream error fixed.

## Publication and rollback

- deployed output: `/Users/rim/Sites/docara.test/build_production`;
- rollback copy:
  `/Users/rim/Sites/docara.test/.docara-backups/navigation-states-20260722-062300/build_production.previous`;
- action-gate report:
  `source/output/action-gates/action-gate-report-20260722062300.json`.
