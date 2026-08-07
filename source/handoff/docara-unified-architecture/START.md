# Start here: Docara unified architecture

This is the only required entry point for a fresh task. It routes current work;
M0-M5 and R1/R2 remain historical evidence, not executable instructions.

## 1. Verify the workspace

```bash
cd /Users/rim/Documents/GitHub/docara-unified
git branch --show-current
git rev-parse HEAD
git status --short
```

Expected branch: `codex/docara-unified-architecture`. Preserve any user changes
and stop on an overlapping dirty worktree. Do not use the installed stale
Docara skill and do not work in another checkout or site root.

## 2. Current router

Current state: `local_framework_runtime_ready_for_independent_audit`

Current goal: `docara.goal.unified`

Current stage: `docara.stage.lfr.local_framework_runtime`

Current batch: `docara.batch.lfr.integrated_retest`

Current next action: `independent_local_framework_runtime_audit`

Current evidence: `source/workflow/evidence/2026-08-07-docara-local-framework-runtime/INDEX.md`

Current candidate: `08cf9eb6b9dbd0175b87854dc9ec9652ebccc773`

Next roadmap goal: `docara.stage.lfr.local_framework_runtime`

Next roadmap status: `audit_pending`

Next roadmap authorized: `true`

Goal 1-3 and Goals A-C remain independently accepted. The explicitly
authorized post-roadmap Surface & Hero Media track has independently accepted
Goal S1 at exact product `ac53ea4…` and governance `4feb910…`. Goal S2/S2-C1 is
independently accepted on exact candidate `7eeba4a…`. Goal S3 is complete on
`dd2c0d6…`; the Surface & Hero Media track now waits for an explicit user
decision. SF5 5.4 typography is the accepted parent. The authorized local
Framework runtime correction is complete on `08cf9eb…`; `docara-new.test` is
already switched to that validation build with exact rollback preserved. It
waits only for independent audit. No S4/Goal D, production or release action is
authorized.

## 3. Read in this order

1. `source/handoff/docara-unified-architecture/STATUS.yaml`;
2. `source/workflow/ACTIVE.md`;
3. `source/workflow/2026-08-07-docara-local-framework-runtime.md` and its
   exact evidence index;
4. `graph/graph.json` and its current stage/batch specs;
5. `graph/generated/ai-context/docara-unified.json` as a checked derived view;
6. `source/handoff/docara-unified-architecture/NEXT.md` and `RESULT.md`;
7. `docs/specification/README.md` and its linked contracts.

Canonical current state belongs to `graph/graph.json` plus `graph/specs`.
`graph/generated/ai-context/docara-unified.json` must be regenerated and
checked by the repository command documented in `graph/README.md`; it never
overrides canonical graph or workflow sources.

## 4. Execute only an explicitly authorized next action

Run only an independent read-only reverse-outcome audit of the exact local
runtime candidate and deployed validation outcome. Do not self-accept it,
invent S4/Goal D or begin release review. This handoff does not authorize
merge, push, tag, release or another deployment.

M0/M1/M2/M3/M4/M5/R1/R2 files and release artifacts are historical baselines.
They may support regression analysis but cannot become the current stage,
batch, candidate or next action unless a new canonical graph transition is
explicitly accepted.

## 5. Forbidden shortcuts

- do not move public prose into JSON, PHP projectors or component manifests;
- do not create a second parser, renderer, Gateway or PageBuilder;
- do not delete legacy without parity and rollback evidence;
- do not start release review from this handoff;
- do not merge, push, tag, release, publish or deploy;
- do not write to `docara.test` or change the already verified
  `docara-new.test` validation tree;
- do not claim independent acceptance from executor evidence.
