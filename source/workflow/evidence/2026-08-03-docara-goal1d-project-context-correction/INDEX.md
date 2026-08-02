# Goal 1-D project-context correction evidence

Date: 2026-08-03
Status: `ready_for_independent_audit`
Input revision: `65097a45b2a39ec8350c0f4a05f95dc7c9c80590`
Router implementation revision: `facafaf`

## Reproduced contradiction

At the input revision:

- canonical `graph/graph.json` selected
  `docara.stage.g1.portable_smart_runtime` and
  `independent_goal1_reverse_outcome_audit`;
- `STATUS.yaml` and `ACTIVE.md` agreed with canonical state;
- `graph/generated/ai-context/docara-unified.json` selected completed R2,
  `docara.batch.r2.prepare_deployment`, candidate `be0ba2d...` and a live
  deploy decision;
- `START.md` still instructed a new executor to run the retired M1A/M1B plan.

The previous project-graph validator checked graph structure but did not compare
the project-specific generated router or handoff semantics.

## Integrated evidence

- [G1D-CONTEXT-INTEGRATED-RETEST.md](G1D-CONTEXT-INTEGRATED-RETEST.md) —
  before/after semantics, deterministic generation, permanent regression,
  exact QA/build/static/single results, browser rebind and rollback;
- [cross-host-report.json](cross-host-report.json) — unchanged exact SF5/Docara
  ABI regression, 1 test / 45 assertions, byte-identical HTML.

## Required evidence — result

- deterministic generator/check command and canonical source digest;
- positive check and negative stale-stage/batch/next/evidence/candidate cases;
- synchronized START/STATUS/ACTIVE/NEXT/RESULT/LEGO plan/context;
- JSON/YAML/link/project-graph/full PHPUnit/Pint/Composer/diff checks;
- exact cross-host and unchanged full/static/single build evidence;
- final candidate, rollback and explicit Goal 2/release nonclaims.

All required evidence is present. Goal 1 remains executor-owned
`ready_for_independent_audit`; this contour does not record independent
acceptance.
