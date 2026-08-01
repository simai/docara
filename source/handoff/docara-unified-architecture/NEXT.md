# Next executable batch: bounded M3 route group

Batch ID: `docara.batch.m3.migrate`

## Required work

1. Select the smallest independently reversible group of generated routes.
2. Add one physical Markdown source per selected locale route.
3. Extend the accepted IR/registry/gateway/PageBuilder only for node types
   required by that group.
4. Preserve exact URLs, HTML, assets, content and full/single build parity.
5. Reduce the explicit legacy allowlist only after route-level evidence; keep
   the global source-ownership gate open until all 44 generated routes move.

## Allowed writes

- a separately accepted bounded M3 production/test plan;
- graph implementation mappings and state supported by evidence;
- `source/workflow/evidence/2026-08-01-docara-unified-architecture/`;
- this handoff's `RESULT.md` and, after a passing gate, `STATUS.yaml`;
- a bounded follow-up assignment under `source/workflow/`.

## Forbidden writes

- unrelated templates, assets, public content redesign and public build output;
- legacy deletion outside the accepted and proven route group;
- dependency/lock updates;
- default branch, tag, release or deployment changes.

## Done when

- the selected route group has one Markdown owner per locale route;
- full and single-page results preserve the M0 baseline for moved routes;
- unknown nodes/components fail closed with source locations;
- the allowlist and implementation mappings shrink without hidden fallback;
- group evidence is committed before selecting the next group.

Completed M1/M2 evidence:
`source/workflow/evidence/2026-08-01-docara-unified-architecture/M1A-EVIDENCE.md`,
`M1B-EVIDENCE.md` and `M2-EVIDENCE.md` in the same directory.
