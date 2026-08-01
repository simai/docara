# Next executable batch: M2 badge vertical slice

Batch ID: `docara.batch.m2.badge`

## Required work

1. Compile `content/ru/components/badge.md` into typed in-memory Document IR
   with physical file/line/column provenance.
2. Route all 16 badge calls through one alias registry and Smart gateway.
3. Use one PageBuilder for full and single-page selection without introducing
   a second parallel engine.
4. Preserve exact badge HTML/assets and the 103-route full build.
5. Keep legacy code available for rollback; do not promote the global
   source-ownership gate.

## Allowed writes

- bounded M2 production/tests listed in the accepted plan;
- graph implementation mappings and state supported by evidence;
- `source/workflow/evidence/2026-08-01-docara-unified-architecture/`;
- this handoff's `RESULT.md` and, after a passing gate, `STATUS.yaml`;
- a bounded follow-up assignment under `source/workflow/`.

## Forbidden writes

- templates, assets, public content redesign and public build output;
- deletion or migration of legacy paths;
- dependency/lock updates;
- default branch, tag, release or deployment changes.

## Done when

- badge IR contains the accepted node types and source locations;
- badge aliases/props/slots fail closed through the common gateway;
- the old inline badge method is inactive for the badge route;
- full and single-page output remain at the M0 baseline;
- `graph/specs/gates/vertical-slice.json` passes with browser evidence.

M1 evidence:
`source/workflow/evidence/2026-08-01-docara-unified-architecture/M1A-EVIDENCE.md`
and `M1B-EVIDENCE.md` in the same directory.
