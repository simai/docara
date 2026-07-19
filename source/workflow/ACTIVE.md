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

Batch 4 successor-candidate acceptance — single-owner mutual exclusion between
reader settings and search on the accepted Batch 3 shell.

## Next Step

Bind the current successor commit to its exact SHA/tree and repeat exact
source/tester gates. Its test-first correction has removed the duplicate shell
listener while retaining the shared physical `Cmd/Ctrl+K` regression. The
native-Chrome keyboard gate remains pending because the external execution
transport reached its explicit usage limit; do not publish or lower the gate.
The served site remains accepted Batch 3. Public release, default-branch
migration and repository retirement remain excluded.
