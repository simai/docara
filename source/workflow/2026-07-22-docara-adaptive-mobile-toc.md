# Docara adaptive mobile table of contents

Date: 2026-07-22
Status: implemented and locally verified
Workflow ID: `2026-07-22-docara-adaptive-mobile-toc`
Baseline: `2732d37a`
Process model: raw-owner `docara + ux + sf5 + tester`

## Goal

Use the clear localized label “Содержание” for the page outline and keep its
mobile trigger only when it materially helps navigation.

## UX contract

- desktop keeps the always-visible outline whenever `reading.toc` is enabled;
- mobile mode is `auto`, `always` or `never`, inherited through the existing
  reading configuration branch;
- `auto` shows the trigger for four or more outline entries or for any nested
  H3-H6 entry;
- short flat pages do not spend first-viewport space on a redundant control;
- long or structurally nested pages retain the discoverable mobile sheet;
- `always` and `never` provide explicit author control;
- mobile touch targets, focus behavior and the current-location state remain
  unchanged.

## Boundaries

The decision belongs to the publisher chrome and resolved page configuration.
The `docara.toc` Smart component continues to own outline rendering and
scroll-aware state. No legacy renderer edit, public push, merge, tag, release
or production-readiness claim is included. Federation routing remains
unavailable because of the existing skill symlink mismatch; raw owner skills
are authoritative for this batch.

## Result

- the localized outline label is “Содержание” in Russian and has corresponding
  concise labels in every bundled language pack;
- `reading.mobile_toc` is a validated inheritable `auto | always | never`
  setting, with `auto` as the starter and runtime default;
- the publisher exposes a strict `shown | auto-hidden | disabled | unavailable`
  output state so verification can distinguish intentional omission from
  broken rendering;
- the mobile sheet hides the duplicate Smart-component heading while keeping
  the visible dialog title and desktop heading;
- the documentation, schema reference and starter configuration describe the
  behavior.

## Acceptance

Full PHPUnit: 619 tests / 5,481 assertions. Exact production build: 271 HTML
pages / 20,512 local references / zero broken references. Browser acceptance:
desktop 1440x900 and mobile 390x844, short and long pages, no horizontal
overflow or console errors. The verified build is served at local
`https://docara.test/`; rollback is preserved under
`/Users/rim/Sites/docara.test/.docara-backups/adaptive-mobile-toc-20260722-014125/build_production.previous`.

Evidence: `source/workflow/evidence/2026-07-22-docara-adaptive-mobile-toc/acceptance.md`.

## Kaizen

Adaptive omission must remain explicit in generated markup and verifiers.
Inferring intent only from a missing element makes a genuine rendering defect
indistinguishable from a deliberate responsive decision.
