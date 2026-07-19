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

Batch 4 successor browser acceptance — exact source/HCS and non-browser tester
gates passed for the single-owner correction.

## Next Step

Candidate `d26fa66c6d6a5a36ec113288e6fce29f2f6b1a0e` has exact bounded HCS
and non-browser tester PASS. The native-Chrome keyboard gate is blocked because
the external approval transport reached its explicit usage limit and forbade a
rerun or indirect workaround. Ask the user for explicit approval after this
disclosure, or wait for transport recovery; then run physical `Cmd/Ctrl+K`,
focus, responsive and disabled-storage acceptance. Do not publish or lower the
gate. The served site remains accepted Batch 3. Public release, default-branch
migration and repository retirement remain excluded.
