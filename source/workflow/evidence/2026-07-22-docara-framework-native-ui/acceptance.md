# Acceptance: Framework-native Docara UI

Date: 2026-07-22
Candidate baseline: `2eea071`
Status: Docara PASS; upstream Framework correction required

## What changed

- removed private `44px` and `36px` floors from Framework buttons,
  icon-buttons, breadcrumbs, radio options, code Copy and outline links;
- removed private `h1`-`h3` size, weight, line-height and tracking from the
  documentation surface;
- removed duplicate color, padding and line-height utilities from Framework
  CTA buttons;
- moved the brand label and search shortcut to Framework typography,
  border, radius and spacing utilities;
- retained only structural documentation layout, behavior hooks and explicit
  product Smart adapters;
- added a static guard that rejects future geometry and typography overrides
  on Framework-owned components.

The native catalogue select remains one documented bridge because the pinned
Framework revision does not ship a select component. Its block size is derived
from Framework UI tokens, not from a raw pixel value.

## Automated evidence

- Framework-native and Markdown focused tests: `43 tests, 208 assertions`;
- portable builder: `36 tests, 740 assertions`;
- landing/CTA focused tests: `2 tests, 498 assertions`;
- full suite: `621 tests, 5545 assertions`, PASS;
- exact build: `/Applications/ServBay/bin/php ../../docara build production`;
- static verifier: 271 HTML pages, 20,512 local references, 0 broken;
- source/generated shell CSS SHA-256:
  `0e0bdcc94372202f215519674b8cf607f166c49f022a54c505722639066c461b`.

## Browser evidence

URL: `https://docara.test/ru/migration/`

Desktop viewport `1296x713`:

| Surface | Computed result |
| --- | --- |
| root | 16 px |
| size-1 icon button | 40 px |
| size-1 outline search button | 42 px |
| native `h1` | 36/52 px |
| outline link | 36 px |
| document overflow | 1282/1282 px, none |

Mobile viewport `390x844`:

| Surface | Computed result |
| --- | --- |
| root | 14 px |
| size-1 icon button | 35 px |
| size-1 outline search button | 33.5 px |
| size-2 mobile menu button | 42 px |
| native `h1` | 21/31.5 px |
| document overflow | 378/378 px, none |

Interaction checks:

- search dialog opened, query `макет` returned 19 results, then closed;
- reader settings opened, changed dark to light, restored dark, then closed;
- mobile sections dialog opened with the current page and hierarchy visible,
  then closed;
- no browser warning/error logs were recorded.

## Framework producer gaps

The nominal contract defines size `1` as 36 px mobile / 40 px desktop and size
`2` as 44 px mobile / 48 px desktop. The pinned runtime cannot meet this
contract even after all Docara overrides are removed:

1. compiled mobile CSS assigns 14 px to `:root`, shrinking every rem-based
   primitive by 12.5%;
2. the outline button adds its one-pixel top and bottom borders after
   calculating the content and padding formula, so its outer block size is
   2 px larger than the nominal desktop height.

The root defect and recommended fix were already confirmed independently in
`/Users/rim/Documents/GitHub/ui-control/source/workflow/2026-07-19-adaptive-sizing-system-audit.md`.
The producer correction must keep `html` at the browser default, apply adaptive
body typography at the body/app-shell layer, and add a computed-height matrix
covering borders. Docara must update only to an immutable revision that passes
that matrix.

## Local publication and rollback

- deployed output: `/Users/rim/Sites/docara.test/build_production`;
- staged output:
  `/Users/rim/Sites/docara.test/.docara-staging/framework-native-ui-20260722-024852`;
- rollback copy:
  `/Users/rim/Sites/docara.test/.docara-backups/framework-native-ui-20260722-024852/build_production.previous`;
- action-gate report:
  `source/output/action-gates/action-gate-report-20260721234842.json`.

No public push, merge, release, tag or production-readiness claim was made.

## Navigation disclosure correction

Source checks:

- the template retains `sf-icon-button--size-1/3`;
- product CSS no longer assigns disclosure min-size, fixed flex basis or
  negative margins;
- generated Smart CSS and JavaScript URLs carry a content-derived 64-character
  SHA-256 `docara_v` query;
- focused acceptance: 4 tests, 520 assertions, PASS;
- full regression suite: 622 tests, 5,552 assertions, PASS;
- static verifier: 271 HTML pages, 20,512 local references, 0 broken.

Browser acceptance at `https://docara.test/ru/migration/`:

| State | Result |
| --- | --- |
| desktop expanded disclosure | 24 x 24 px; 8 px gap; no overlap |
| desktop collapsed | submenu hidden; `aria-expanded=false`; active link retained |
| desktop expanded again | submenu visible; `aria-expanded=true`; active link retained |
| mobile 390 x 844 | 21 x 22.5 px at the pinned Framework mobile root; 7 px gap; no overlap |
| responsive page | no horizontal overflow at desktop or mobile |
| published stylesheet | content-versioned `navigation.css?docara_v=9ee27b...` |

The observed mobile dimensions inherit the already recorded upstream 14 px
root defect. Docara does not add a compensating private size.

Local publication:

- deployed output: `/Users/rim/Sites/docara.test/build_production`;
- rollback copy:
  `/Users/rim/Sites/docara.test/.docara-backups/menu-disclosure-20260722-055828/build_production.previous`;
- action-gate report:
  `source/output/action-gates/action-gate-report-20260722055828.json`.
