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

Current state: `goal_s1_ready_for_independent_audit`

Current goal: `docara.goal.unified`

Current stage: `docara.stage.s1.surface_runtime`

Current batch: `docara.batch.s1.pipeline_container_correction`

Current next action: `independent_goal_s1_reverse_outcome_audit`

Current evidence: `source/workflow/evidence/2026-08-06-docara-surface-hero/INDEX.md`

Current candidate: `80b8102632c922ec44d16947456babeab6d15e25`

Next roadmap goal: `docara.goal.s1_c1`

Next roadmap status: `audit_pending`

Next roadmap authorized: `false`

Goal 1-3 and Goals A-C remain independently accepted. The explicitly
authorized post-roadmap Surface & Hero Media track is now active only at Goal
S1. Independent audit rejected candidate `45276f6…`; correction candidate
`80b8102…` now owns nested IR, container-contract, source-location and fresh
evidence proofs. It awaits independent audit. S2 and all release/live actions
remain unauthorized.

## 3. Read in this order

1. `source/handoff/docara-unified-architecture/STATUS.yaml`;
2. `source/workflow/ACTIVE.md`;
3. `source/workflow/2026-08-06-docara-surface-hero-track.md` and the current S1 workflow;
4. `graph/graph.json` and its current stage/batch specs;
5. `graph/generated/ai-context/docara-unified.json` as a checked derived view;
6. `source/handoff/docara-unified-architecture/NEXT.md` and `RESULT.md`;
7. `docs/specification/README.md` and its linked contracts.

Canonical current state belongs to `graph/graph.json` plus `graph/specs`.
`graph/generated/ai-context/docara-unified.json` must be regenerated and
checked by the repository command documented in `graph/README.md`; it never
overrides canonical graph or workflow sources.

## 4. Execute only an explicitly authorized next action

Execute only an independent read-only Goal S1 audit. Do not self-accept or
begin S2. This handoff does not authorize release review, merge, tag or
deployment.

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
- do not write to `docara.test` or `docara-new.test`;
- do not claim independent acceptance from executor evidence.
