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

Batch 3 — breadcrumbs, current-page heading outline and previous/next page
navigation derived from the canonical Docara content and navigation plans.

## Next Step

Create the immutable Batch 3 candidate from the verified worktree. Run
independent exact-archive tester and complete-diff HCS acceptance against the
same SHA, then publish it to `docara.test` only through staging, backup,
verification and matching-digest gates. After Batch 3 closure continue with
Batch 4 reading settings. Public release, default-branch migration and
repository retirement remain excluded.
