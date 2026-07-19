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

Batch 6 — one deterministic effective component catalogue contract, typed
component definitions and corrected capability projection.

## Next Step

Batch 5 successor `918919046a2863a67a306678ad225dbda4549666`
passed independent exact tester, native-Chrome UX/design and
Human-Centered Simplicity/source/security gates and is served locally with
matching source/staging/served digest
`c0d38e6badc833eaa29cf0f0482d4306c10aca943e993e79ffa629497a5b3060`.
The accepted Batch 4 tree remains available as the timestamped rollback.

Cut the immutable Batch 6 candidate from the verified implementation, then run
independent exact-archive, complete-diff HCS/source/security and bounded
native-Chrome acceptance for that same revision. Keep the accepted Batch 5
build served until every Batch 6 gate passes. Public release, default-branch
migration and repository retirement remain excluded.
