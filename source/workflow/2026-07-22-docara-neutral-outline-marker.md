# Workflow: Docara neutral outline marker

Date: 2026-07-22
Status: review ready
Baseline: `3f8cabc`
Primary owner: `docara`
Companions: `sf5`, `ux`, `tester`, `ops`

## User outcome

The active contents marker remains clearly visible but no longer competes with
links, focus or primary actions through the brand color.

## Decision

- Keep the divider on the quiet `--sf-outline-variant` token.
- Render the active marker with the neutral, stronger `--sf-outline` token.
- Keep the `2 px` marker geometry and bold active label, so color is not the
  only state cue.
- Do not introduce a Docara color, opacity, custom class or runtime behavior.

## Done when

- light and dark themes show a neutral marker stronger than the divider;
- the marker remains on the divider and follows the active heading;
- the short contents list remains stationary during document scrolling;
- focused tests, full tests, build, static verification and browser checks pass;
- the verified build is published to `https://docara.test/` with rollback.

## Result

- focused PHPUnit: PASS, `37 tests`, `812 assertions`;
- full PHPUnit: PASS, `623 tests`, `5569 assertions`;
- exact build and static verification: PASS, `271` HTML pages and `20512`
  local references with no broken references;
- light theme marker: `rgb(118, 119, 124)` / `#76777c`;
- dark theme marker: `rgb(144, 144, 149)` / `#909095`;
- marker geometry: `2 x 36 px`, `0 px` displacement from the divider;
- active label weight: `700`; horizontal overflow: `0 px`;
- the asset URL carries the new content SHA-256 and the deployed file matches
  the exact build byte-for-byte.

Verified URL: `https://docara.test/ru/migration/legacy/`.

Rollback:
`/Users/rim/Sites/docara.test/.docara-backups/neutral-outline-marker-20260722-141043/build_production.previous`.

## Exclusions

- no Framework source, layout geometry, navigation hierarchy or content change;
- no public push, merge, tag, release or production-readiness claim.
