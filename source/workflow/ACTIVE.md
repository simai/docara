# Active workflow: Docara compact header controls

Date: 2026-07-22
Status: implemented and locally verified
Workflow ID: `2026-07-22-docara-compact-header-controls`
Process model: raw-owner `docara + ux + sf5 + tester + ops`
Current state: `review_ready`
Target state: `review_ready`

## Current Goal

Use Simai Framework size `1` for the Docara search and reader-settings controls
so the header has the useful density of the legacy reference without losing
accessibility or behavior.

## Result So Far

The canonical declarative publisher uses `sf-button--size-1` for search and
`sf-icon-button--size-1` for reader settings. The search dialog already used
`sf-input--size-1`. Desktop controls are now 44 x 44 px high, and mobile keeps
both controls at 44 x 44 px with the label hidden and no overflow.

## Completion Guard

Full PHPUnit passed at 619 tests / 5,498 assertions. The exact build passed
static verification for 271 HTML pages and 20,512 local references and is
served at local `docara.test` with rollback backup. Evidence:
`source/workflow/evidence/2026-07-22-docara-compact-header-controls/`.

Public push, merge, tag, package release and production readiness remain
excluded.
