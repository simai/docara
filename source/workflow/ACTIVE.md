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

Batch 6 candidate `68a960ff1debde48664aa8541413dbef208612ee`
passed independent exact tester, native-Chrome UX/design and
Human-Centered Simplicity/source/security gates. Its exact build is served
locally with matching source/staging/served digest
`16bbdd52e2dc0e0c058c02dfbc61e3dd824fa2e59be23b45848f587d83a3fc50`;
the accepted Batch 5 tree is the timestamped rollback.

Implement Batch 7 test-first from the accepted EffectiveComponentCatalog:
generate one compact catalogue and one generic detail template, add exact
example fixtures and close the required `docara.columns` recipe without a
second registry or Framework owner write. Keep the accepted Batch 6 build
served until one immutable Batch 7 candidate passes exact tester,
complete-diff HCS/source/security and native-Chrome UX/design gates. Public
release, default-branch migration and repository retirement remain excluded.
