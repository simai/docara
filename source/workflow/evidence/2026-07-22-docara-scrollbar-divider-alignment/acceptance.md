# Acceptance: Docara scrollbar divider alignment

Date: 2026-07-22
Baseline: `c2882ecfc2d7ef505df0b0d791bb57a1b739045b`
Verdict: PASS

## Scope and scenario

Checked the desktop navigation rail and page-outline rail of the declarative
Docara shell. Both independently scrollable regions must keep the native
scrollbar beside their inner column divider through the Framework `b2` token,
without changing menu, outline or mobile behavior.

## Source and build evidence

- outer generic `p-1` and `p-2` classes were removed from the rail elements;
- content spacing remains inside each scroll container through existing
  `var(--sf-space-1)` and `var(--sf-space-2)` values;
- divider-to-scroll-container spacing uses `var(--sf-b2)`; no arbitrary pixel
  value or new utility class was introduced;
- the outline scroll container uses the opposite scrollbar direction while
  restoring the document direction for its child content;
- focused PHPUnit: 37 tests, 809 assertions, PASS;
- full PHPUnit: 623 tests, 5,565 assertions, PASS;
- production build: PASS;
- static verifier: 271 HTML pages, 20,512 local references, 0 broken;
- `git diff --check`: PASS.

## Browser evidence

Desktop `1440 x 900`, `/ru/migration/`:

- Framework `--sf-b2`: `0.75rem`, computed to `12 px`;
- left divider-to-scroll-container gap: `13 px` including the `1 px` divider;
- right divider-to-scroll-container gap: `13 px` including the `1 px` divider;
- outline scroll container direction: `rtl`, child content direction: `ltr`;
- horizontal overflow: `0`.

Visible overflow checks at `1440 x 600`:

- `/ru/migration/`: left navigation overflowed and scrolled to `276 px`; its
  scrollbar remained beside the right divider;
- `/ru/examples/`: right contents overflowed and scrolled to `204 px`; its
  scrollbar remained beside the left divider.

Mobile `390 x 844`, `/ru/migration/`:

- desktop sidebar and outline rail both resolve to `display: none`;
- horizontal overflow: `0`;
- browser console errors: none.

## Publication and rollback

- deployed output: `/Users/rim/Sites/docara.test/build_production`;
- rollback copy:
  `/Users/rim/Sites/docara.test/.docara-backups/scrollbar-divider-20260722-105642/build_production.previous`;
- action-gate report:
  `source/output/action-gates/action-gate-report-20260722075631.json`.

## Verdict

PASS. The rail composition now separates divider spacing from content padding,
uses only existing Framework tokens and keeps the scrollbar on the logical
divider side for both LTR and RTL documents.
