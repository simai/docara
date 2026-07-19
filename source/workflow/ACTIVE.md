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

The corrected immutable candidate
`4164ba2aa890a711b58a2ea016c4f4fbb77ef865` passed independent exact-archive
tester and complete-diff Human-Centered Simplicity/source/security gates, but
native-Chrome acceptance rejected it at 390 pixels: an outline link placed its
heading at 111.84 pixels while the sticky header ended at 127 pixels.

The bounded test-first correction increases only the mobile heading anchor
reserve from 8rem to 10rem. During full regression, an existing preview test
deterministically deadlocked because Symfony Process output was not drained;
the test harness now drains its child pipes while the production router remains
unchanged. The full suite passes 541 tests with 4,305 assertions and two clean
builds reproduce digest
`502e43119ea2f2fc6ce358042858937060a67c4aa3d4d5ac0295e3d19c8e782f`.

Freeze the commit containing both bounded corrections and repeat the
exact-archive, complete-diff and complete native-Chrome matrices on that same
SHA. Keep Batch 7 served until every gate passes, then publish through staging
with a timestamped rollback and matching source/staging/served digests. Public
release, default-branch migration, Framework owner writes and repository
retirement remain excluded.
