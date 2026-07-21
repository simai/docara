# Docara compact header controls

Date: 2026-07-22
Status: implemented and locally verified
Workflow ID: `2026-07-22-docara-compact-header-controls`
Baseline: `853959b`
Process model: raw-owner `docara + ux + sf5 + tester + ops`

## Goal

Bring the search and reader-settings controls in the Docara header to Simai
Framework size `1`, matching the useful density of the legacy reference without
changing search, theme, keyboard or responsive behavior.

## Evidence baseline

- current search trigger: size `2`, 222 x 50 px at 1440 x 900;
- current reader-settings trigger: size `2`, 48 x 48 px;
- legacy search field: size `1`, 272 x 42 px;
- legacy reader-settings trigger: size `1`, 40 x 40 px;
- the search-dialog field already uses `sf-input--size-1` and requires no fix.

## Boundaries

Change only the canonical declarative publisher template and regression tests.
Do not edit the legacy renderer, add page-specific CSS, change behavior, push,
merge, tag, release or claim production readiness. Local ServBay publication
must use staging, backup, atomic replacement and browser acceptance.

Federation route repair is unavailable because of the existing skill symlink
mismatch. The process resolver selected Docara; raw Docara, UX and Simai
Framework owner sources remain authoritative for this batch.

## Result

- the search trigger now uses `sf-button--size-1`;
- the reader-settings trigger now uses `sf-icon-button--size-1`;
- the search-dialog field remains the existing `sf-input--size-1`;
- no page CSS or legacy-renderer duplication was introduced;
- desktop browser geometry changed from 50/48 px to accessible 44/44 px;
- the 390 px header keeps both controls at 44 x 44 px, hides the search label
  and has no horizontal overflow.

## Acceptance

Focused builder test: 1 test / 467 assertions. Full PHPUnit: 619 tests / 5,498
assertions. Exact build and deployed local build: 271 HTML pages / 20,512 local
references / zero broken references. Browser console errors: none.

Local rollback:
`/Users/rim/Sites/docara.test/.docara-backups/compact-header-controls-20260722-021519/build_production.previous`.

Evidence:
`source/workflow/evidence/2026-07-22-docara-compact-header-controls/acceptance.md`.

## Kaizen

Header control density must be expressed through the Framework size contract,
not custom dimensions. The existing 44 px accessibility floor remains the
correct lower bound even when the visual component switches to size `1`.
