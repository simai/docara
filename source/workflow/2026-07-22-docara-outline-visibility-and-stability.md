# Workflow: Docara outline visibility and stability

Date: 2026-07-22
Status: review ready
Baseline: `8003455`
Primary owner: `docara`
Companions: `sf5`, `ux`, `tester`, `ops`

## User outcome

- The active contents marker is visibly blue on the physical left divider.
- A short contents list does not visibly travel upward before becoming sticky.
- The contents scrollbar remains on the physical right and appears only when
  the list actually overflows.

## Confirmed causes

- The previous active marker had correct computed coordinates but extended
  outside the `overflow:auto` section and was clipped.
- A short list had `0 px` internal overflow, but its sticky top was `19 px`
  above its natural starting position, so the entire contents block moved
  upward during page scrolling.

## Implementation

- Draw the full-height divider as an inset rail shadow.
- Start the scroll section at the rail edge and move the existing content
  spacing inside it; the marker now stays inside the clipping boundary.
- Align the sticky position with the documentation header using the same
  `4.5rem` offset already used for heading scroll margins.
- Preserve the Framework tokens and native right-side scrollbar.

## Readiness

- focused tests: PASS, `37 tests`, `810 assertions`;
- full tests: PASS, `623 tests`, `5567 assertions`;
- exact build and static verification: PASS, `271` HTML pages and `20512`
  local references with no broken references;
- visible marker pixels and contrast: PASS;
- top/scroll geometry for a short contents list: PASS;
- overflowing contents and mobile regression: PASS;
- local publication and rollback: PASS.

## Measured result

- Short contents internal overflow: `0 px`.
- Contents top before page scroll: `72 px`.
- Contents top after `650 px` page scroll: `72 px`.
- Active marker width and height: `2 x 36 px`.
- Active marker displacement from divider: `0 px`.
- Active marker color in the dark theme: `rgb(172, 199, 255)`.
- Long contents overflow: `180 px`; its native `14 px` scrollbar remains on
  the physical right.
- Mobile horizontal overflow: `0 px`; console errors: none.

The verified build is served at `https://docara.test/`. Rollback copy:
`/Users/rim/Sites/docara.test/.docara-backups/outline-visibility-20260722-134305/build_production.previous`.

## Exclusions

- no content, navigation hierarchy, URL or locale changes;
- no upstream Framework source changes;
- no public push, merge, tag, release or production-readiness claim.
