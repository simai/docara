# Docara shell resource preload correction evidence

Date: 2026-08-27
Result: PASS

## Baseline and scope

- Docara HEAD at intake: `8ab5bc48c251283c00f1de23ab4f04384a1021a3`.
- Pre-correction tracked diff SHA-256:
  `18c3419fc46da5a291ff6e29dafe6daf194683420855d9bad7e494243e58a7b0`.
- Existing dirty work was preserved.
- ui-doc remained at HEAD `de9b5619d0a4ee91e692eb47d7c97fd6d5baee3f`.
- ui-doc `composer.lock` remained
  `3c183032fb9dd67f096a5b5d88f0e948690cfb0281eb1ae64706b4752ca9649a`.
- ui-doc `simai-framework.lock.json` remained
  `0d0112c747824efa890759951161d5ef0acff2fcf4ab8d09cd7f4b1a2a99bff4`.

## Automated verification

- Full PHPUnit: 537 tests, 11,911 assertions, PASS.
- Focused planner/renderer verification: 70 tests, 703 assertions, PASS.
- Post-correction publisher/static regression: 74 tests, 3,789 assertions,
  PASS.
- Docara documentation: 127 source pages; static verification checked 261
  HTML pages and 34,634 local references; broken references: 0.
- ui-doc: 912 source pages; static verification checked 1,665 HTML pages and
  368,128 local references; broken references: 0.
- Translation tracking remained report-only and did not alter source content.

## Browser acceptance

- Desktop header: 73 CSS px at first and final sample.
- Mobile header: 65 CSS px at first and final sample.
- Sidebar width and main-column offset remained constant.
- No layout-shift source belonged to header, sidebar, outline or main layout.
- Warm navigation CLS: 0 across the checked routes.
- Ten independent live cold runs on the most sensitive page measured maximum
  CLS `0.00321972085774561`, below the `0.01` acceptance limit.
- No failed request, console error, duplicate exact Framework resource URL or
  duplicate custom-element registration was observed.
- Desktop/mobile, light/dark, standard/large/alternative typography and browser
  back/forward navigation were covered.

## Local site cutover

- Final active tree digest:
  `9812351a4b72fc48c4ad84f6599dab2ffdef08f2a270d8d1c8f7ffc8a26a3cb7`.
- `ui-doc.test` continued to resolve to the existing
  `/Users/rim/Documents/GitHub/ui-doc/build_production` target.
- Required routes returned HTTP 200 after the atomic cutover.
- Browser smoke passed after cutover.
- Both operation-local rollback directories were moved to the system Trash
  after green smoke; no permanent rollback was left in the project or site
  path.
- Generated project context was regenerated and checked with `issues: []`.

## Publication boundary

No commit, push, tag, release or public deployment was performed.
