# Active workflow: Docara Framework surface cleanup

Date: 2026-07-22
Status: implemented and locally verified
Workflow ID: `2026-07-22-docara-framework-surface-cleanup`
Process model: raw-owner `docara + sf5 + ux + dev + tester`
Current state: `review_ready`
Target state: `review_ready`

## Current Goal

Make deep Docara breadcrumbs usable through the pinned Simai Framework
contract, then remove only proven custom-class duplicates from the canonical
publisher without weakening product behavior, portability or accessibility.

## Result So Far

The five-level path now renders as root, localized ellipsis and current page,
expands on demand, preserves all server-rendered links without JavaScript and
does not create page-level overflow. A bounded publisher audit removed dead
classes and moved atomic layout properties to Framework utilities. The exact
verified build is served at local `docara.test` with rollback backup.

## Completion Guard

Final full PHPUnit passed at 618 tests and 5,459 assertions. Evidence:
`source/workflow/evidence/2026-07-22-docara-framework-surface-cleanup/`.

Public push, merge, tag, package release and production readiness remain
excluded.
