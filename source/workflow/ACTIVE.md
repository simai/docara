# Workflow: Docara consolidation and local documentation

Date: 2026-07-18
Status: exact_candidate_verification_and_local_publication_active
Workflow ID: `2026-07-18-docara-consolidation-and-local-docs`
Process model: `general_delivery`
Current state: `tests_recorded`

## Final Outcome

Docara is the canonical owner of its generator, starter, schemas, examples,
Markdown authoring contract, and generated template mirror. Maintained
consumers have verified Vite migration candidates, the portable route remains
PHP-only, and the new self-hosted documentation is accepted at
`https://docara.test/` with backup and rollback evidence.

## Current Goal

Freeze and independently accept the exact Docara candidate, then perform the
gated local backup/swap and browser acceptance. Do not claim a release, publish
the external mirror, merge consumer branches, or archive `docara-mix` in this
batch.

## Source Of Truth

- workflow: `source/workflow/2026-07-18-docara-consolidation-and-local-docs.md`;
- launch record: `source/workflow/2026-07-18-docara-consolidation-and-local-docs.launch.yaml`;
- evidence: `source/workflow/evidence/2026-07-18-docara-consolidation-and-local-docs/`.

## Next Step

Complete final exact-candidate gates and commits, obtain independent tester and
Human-Centered Simplicity verdicts, then publish only the verified static output
to the local ServBay site with a timestamped backup.
