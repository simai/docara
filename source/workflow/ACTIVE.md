# Workflow: Docara product completion

Date: 2026-07-19
Status: in-progress
Workflow ID: `2026-07-19-docara-product-completion`
Process model: `general_delivery`
Current state: `planned`
Target state: `launch_record_ready`

## Goal

Complete the remaining local Docara product stages: clear multi-level menu and
active trail, search and reading context, landing, full typed-component
catalogue, complete documentation and exact independent acceptance.

## Source Of Truth

- workflow: `source/workflow/2026-07-19-docara-product-completion.md`;
- launch record: `source/workflow/2026-07-19-docara-product-completion.launch.yaml`;
- evidence: `source/workflow/evidence/2026-07-19-docara-product-completion/`.

## Current Batch

Batch 2 — deterministic local full-text search derived at build time from the
published content and presented with pinned Simai Framework primitives.

## Next Step

Commit the corrected Batch 2 candidate, re-run independent exact-archive
tester, HCS/source and UX/designer browser acceptance against that exact tree,
then publish only the accepted tree to `docara.test` through staging and
rollback. Continue with Batch 3 afterwards. Public release, default-branch
migration and repository retirement remain excluded.
