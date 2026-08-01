# Next executable batch: M0 implementation mapping

Batch ID: `docara.batch.m0.mapping`

## Required work

1. Produce an inventory of physical Markdown, public routes, language-pack
   content, catalog/projector sources, parser/renderer paths and build entry
   points.
2. Re-run the repository's existing unit, static and deterministic build checks
   from the exact candidate tree; record commands, revisions and outputs.
3. Capture the current `/ru/components/badge/` source, intermediate ownership,
   HTML and asset baseline.
4. Complete every `graph/specs/implementation-mappings/*.json` with exact code
   paths, symbols, tests, current behavior, gaps and deletion gates.
5. Identify which claims from the 2026-07-30 workflow are demonstrably present
   and which remain plans.
6. Propose one bounded M1/M2 implementation batch with acceptance and rollback.

## Allowed writes

- graph implementation mappings and state supported by evidence;
- `source/workflow/evidence/2026-08-01-docara-unified-architecture/`;
- this handoff's `RESULT.md` and, after a passing gate, `STATUS.yaml`;
- a bounded follow-up assignment under `source/workflow/`.

## Forbidden writes

- runtime, templates, assets, content, configs and public build output;
- deletion or migration of legacy paths;
- dependency/lock updates;
- default branch, tag, release or deployment changes.

## Done when

- inventory is repeatable from recorded commands;
- baseline checks and artifacts are immutable and attributable;
- every target module has exact current-code/test/gap/deletion-gate mapping;
- contradictions are explicit rather than silently resolved;
- the next batch is small enough to implement and review as one vertical slice;
- `graph/specs/gates/m0-baseline.json` passes.
