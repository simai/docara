# Workflow: Docara product completion

Date: 2026-07-19
Status: in-progress
Workflow ID: `2026-07-19-docara-product-completion`
Process model: `general_delivery`
Current state: `implementation_ready_pending_exact_acceptance`
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

Batch 5 — landing recipe, responsive demonstration and combined directive
budget correction.

## Next Step

Batch 4 candidate `d26fa66c6d6a5a36ec113288e6fce29f2f6b1a0e` remains
accepted and served locally. The first Batch 5 candidate
`68fba097d6d629ad77937a09a6c16b25ea709850` remains rejected after
`B5-HCS-P2-001` found an unbounded cross-family parser path. The shared
64-marker preflight correction now passes mutable verification. Create a new
immutable candidate and require independent exact tester, native-Chrome
UX/design and source/HCS/security PASS before local publication. Public
release, default-branch migration and repository retirement remain excluded.
