# Docara block spacing contract

Date: 2026-07-29
Status: complete
Workflow ID: `2026-07-29-docara-block-spacing-contract`

## Goal

Every standalone block component owns one standard bottom margin through the
SIMAI Framework utility `m-bottom-1`. Internal component parts do not create an
additional external margin. A card nested directly in `grid` uses
`m-bottom-0`; the grid owns the external `m-bottom-1` rhythm.

## Implementation

- added `m-bottom-1` to all Docara typed block roots;
- added the same contract to native fenced code and table wrappers;
- added stable `data-docara-block` identity to `card` and `steps`;
- normalized nested grid cards to `m-bottom-0` to prevent double spacing;
- updated executable catalog and renderer expectations.

## Verification

- focused renderer/catalog suites: 64 tests, 1706 assertions, PASS;
- production build: 100 pages, PASS;
- static verification: 200 HTML files, 17,730 references, 0 broken;
- generated HTML audit: 17 typed block kinds, 0 spacing violations;
- browser computed styles:
  - alert: `margin-bottom: 16px`;
  - code: `margin-bottom: 16px`;
  - figure: `margin-bottom: 16px`, other margins remain zero;
  - grid: `margin-bottom: 16px`;
  - nested grid card: `margin-bottom: 0px`;
- HTTP smoke: alert, code, grid and figure pages return 200.

## Local publication

- served target: `/Users/rim/Sites/docara.test/build_production`;
- rollback copy:
  `/Users/rim/Sites/docara.test/.docara-backups/block-spacing-20260729-094121/build_production`.

## Boundary

No merge, tag, public release or production deployment is claimed by this
batch.
