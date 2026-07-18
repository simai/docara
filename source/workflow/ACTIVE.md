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

Implement the Batch 3 configuration and rendering contract test-first, using
one source of truth and exact pinned Simai Framework primitives. Verify native
semantics, Unicode/duplicate heading anchors, responsive outline behavior,
active context and previous/next order before exact tester and UX/designer
acceptance. Public release, default-branch migration and repository retirement
remain excluded.
