# Next executable batch: M1 source boundaries

Batch ID: `docara.batch.m1.boundaries`

## Required work

1. Implement M1A typed physical Markdown source locator and fail-closed route
   mapping without changing output.
2. Implement M1B target source-boundary guards and the finite zero-growth
   legacy allowlist.
3. Prove `content/<locale>/lang.json` ownership and isolation of package-owned
   CLI/build messages; reject target `resources/i18n` and `site.json` inputs.
4. Run full and single-page builds after each bounded stage.
5. Promote only `docara.gate.badge_source_ready`; keep global source ownership
   open until M3.

## Allowed writes

- bounded M1 production/tests listed in the accepted plan;
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

- authored routes resolve one-to-one with typed diagnostics;
- target public i18n and config boundaries fail closed;
- legacy generated-route/prose inventory cannot grow;
- full and single-page output remain at the M0 baseline;
- `graph/specs/gates/badge-source-ready.json` passes before M2.
