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

Current state: `goal_a_ready_for_independent_audit`

Current goal: `docara.goal.unified`

Current stage: `docara.stage.a.shell_contract`

Current batch: `docara.batch.a.shell_contract`

Current next action: `independent_goal_a_reverse_outcome_audit`

Current evidence: `source/workflow/evidence/2026-08-04-docara-content-design-settings-goal-a/INDEX.md`

Current candidate: `8c04160ab50549b060fb933cf80f86193cd92113`

Next roadmap goal: `docara.goal.a.independent_audit`

Next roadmap status: `ready_for_independent_audit`

Next roadmap authorized: `true`

Goal 1 and Goal 2 were independently accepted with `PASS_WITH_NOTES`; Goal 3
was independently accepted with `PASS`. Goal A Shell Contract & Safe
Configuration is executor-complete and awaits independent audit. Goal B and
release review remain unauthorized.

## 3. Read in this order

1. `source/handoff/docara-unified-architecture/STATUS.yaml`;
2. `source/workflow/ACTIVE.md`;
3. `source/workflow/2026-08-04-docara-content-design-settings-track.md`;
4. `graph/graph.json` and its current stage/batch specs;
5. `graph/generated/ai-context/docara-unified.json` as a checked derived view;
6. `source/handoff/docara-unified-architecture/NEXT.md` and `RESULT.md`;
7. `docs/specification/README.md` and its linked contracts.

Canonical current state belongs to `graph/graph.json` plus `graph/specs`.
`graph/generated/ai-context/docara-unified.json` must be regenerated and
checked by the repository command documented in `graph/README.md`; it never
overrides canonical graph or workflow sources.

## 4. Execute only the current goal

Audit Goal A from `source/workflow/2026-08-04-docara-goal-a-shell-contract.md`
and its indexed evidence against exact candidate `8c04160…`. Preserve accepted
Goal 1-3 runtime invariants. Do not implement Goal B or start release review.

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
