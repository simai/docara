# Goal 1-D project-context correction evidence

Date: 2026-08-03
Status: `in_progress`
Input revision: `65097a45b2a39ec8350c0f4a05f95dc7c9c80590`

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

## Required evidence

- deterministic generator/check command and canonical source digest;
- positive check and negative stale-stage/batch/next/evidence/candidate cases;
- synchronized START/STATUS/ACTIVE/NEXT/RESULT/LEGO plan/context;
- JSON/YAML/link/project-graph/full PHPUnit/Pint/Composer/diff checks;
- exact cross-host and unchanged full/static/single build evidence;
- final candidate, rollback and explicit Goal 2/release nonclaims.
