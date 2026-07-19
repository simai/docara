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

Batch 4 correction — real-keyboard mutual exclusion between reader settings
and search on the accepted Batch 3 shell.

## Next Step

Add the physical `Cmd+K` regression first, then ensure opening search closes an
open reader-settings dialog before search becomes open. Preserve the accepted
storage compatibility behavior and repeat exact keyboard/browser/tester gates
on a new immutable candidate. The served site has already been rolled back to
accepted Batch 3. Public release, default-branch migration and repository
retirement remain excluded.
