# Workflow: Docara consolidation and local documentation

Date: 2026-07-18
Status: local_publication_accepted_release_retirement_pending
Workflow ID: `2026-07-18-docara-consolidation-and-local-docs`
Process model: `general_delivery`
Current state: `evidence_recorded`

## Final Outcome

Docara is the canonical owner of its generator, starter, schemas, examples,
Markdown authoring contract, and generated template mirror. Maintained
consumers have verified Vite migration candidates, the portable route remains
PHP-only, and the new self-hosted documentation is accepted at
`https://docara.test/` with backup and rollback evidence.

## Current Goal

Preserve the accepted exact Docara candidate and local publication. Prepare the
next separately gated release/default-branch integration batch without
claiming retirement, release readiness, production readiness, or ecosystem
readiness.

## Source Of Truth

- workflow: `source/workflow/2026-07-18-docara-consolidation-and-local-docs.md`;
- launch record: `source/workflow/2026-07-18-docara-consolidation-and-local-docs.launch.yaml`;
- evidence: `source/workflow/evidence/2026-07-18-docara-consolidation-and-local-docs/`.

## Next Step

Create an exact release plan for `4a312c1…`, generated mirror publication,
consumer lock updates, and acceptance. `docara-mix` remains **NOT READY** for
archive until active default branches have zero references and an independent
retirement verdict passes.
