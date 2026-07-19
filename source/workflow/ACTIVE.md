# Workflow: Docara product completion

Date: 2026-07-19
Status: in-progress
Workflow ID: `2026-07-19-docara-product-completion`
Process model: `general_delivery`
Current state: `tests_recorded`
Target state: `evidence_recorded`

## Goal

Complete the remaining local Docara product stages: clear multi-level menu and
active trail, search and reading context, landing, full typed-component
catalogue, complete documentation and exact independent acceptance.

## Source Of Truth

- workflow: `source/workflow/2026-07-19-docara-product-completion.md`;
- launch record: `source/workflow/2026-07-19-docara-product-completion.launch.yaml`;
- evidence: `source/workflow/evidence/2026-07-19-docara-product-completion/`.

## Current Batch

Batch 9 — freeze the complete Batch 8 product candidate and run unified
exact-archive, complete-diff HCS/source/security, native-Chrome UX/design and
local-publication acceptance for the entire Goal.

## Next Step

Batch 7 candidate `a5cc0e7ddd3a4ef218381e3e4129825eedf6d671`
passed independent exact-archive tester, complete-diff Human-Centered
Simplicity/source/security and native-Chrome UX/design gates. Its exact build
is served at `docara.test` with digest
`dc6e2a997314a2497da29af3e696937d7f46aee2a832ed3166e2862cdd963675`;
the exact accepted Batch 6 tree is retained at
`.docara-backups/product-completion-live-catalog-a5cc0e7-20260719-182708`.

Batch 8 mutable implementation passes 541 tests with 4,285 assertions, Pint,
Composer validation, JSON and diff checks. Two clean builds reproduce digest
`69e1fb2a341b43806ad8e00e14158b2234b0e79b5021efe1b047293d981bcc9e`
with 43 authored pages, 56 HTML pages, 55 search documents, 13 generated
catalogue surfaces, no old manual component routes and 5,793 verified local
references with zero broken.

Create one local immutable candidate. Accept it only after independent
exact-archive full tests/build/static verification, a complete inventory from
Goal baseline `31f468be85d015b962fccc2b4c089204aab1410b`, and native-Chrome checks at
1440, 768 and 390 pixels for menu, search, breadcrumbs/TOC/previous-next,
settings, landing, catalogue/details and the beginner/author/migration/
maintainer/extension documentation paths. Keep Batch 7 served until every gate
passes, then publish through staging with a timestamped rollback and matching
digests. Public release, default-branch migration, Framework owner writes and
repository retirement remain excluded.
