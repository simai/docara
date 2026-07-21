# Active workflow: Docara adaptive mobile contents

Date: 2026-07-22
Status: implemented and locally verified
Workflow ID: `2026-07-22-docara-adaptive-mobile-toc`
Process model: raw-owner `docara + sf5 + ux + tester + ops`
Current state: `review_ready`
Target state: `review_ready`

## Current Goal

Use the clear localized label “Содержание” and show the mobile contents control
only where it materially helps navigation, without weakening the declarative
publisher or Smart-component contract.

## Result So Far

Desktop keeps its outline. Mobile `auto` hides the trigger on short flat pages
and shows it for four or more entries or nested H3-H6 headings; authors may
override this with `always` or `never`. The mobile sheet has one visible
“Содержание” heading rather than a duplicate.

## Completion Guard

Full PHPUnit passed at 619 tests / 5,481 assertions. The exact build passed
static verification for 271 HTML pages and 20,512 local references, and is
served at local `docara.test` with rollback backup. Evidence:
`source/workflow/evidence/2026-07-22-docara-adaptive-mobile-toc/`.

Public push, merge, tag, package release and production readiness remain
excluded.
