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

Batch 7 — one generated live component catalogue, generic detail page and the
typed `docara.columns` layout recipe.

## Next Step

Batch 7 mutable implementation is verified and ready to become one immutable
candidate. It derives 17 records, 12 supported examples, one generated index
and 12 generated detail pages from the accepted
EffectiveComponentCatalog. Two clean production builds are byte-identical:
70 files, 60 HTML pages, 6477 checked local references and digest
`dc6e2a997314a2497da29af3e696937d7f46aee2a832ed3166e2862cdd963675`.
The complete sequential suite passes with 534 tests and 3923 assertions.

Create the Batch 7 candidate commit, then require independent exact-archive
tester, complete-diff Human-Centered Simplicity/source/security and
native-Chrome UX/design verdicts for that exact SHA. Publish only after all
three gates pass, using staging, a timestamped rollback and matching
source/staging/served digests. Then continue directly to Batch 8. Public
release, default-branch migration, Framework owner writes and repository
retirement remain excluded.
