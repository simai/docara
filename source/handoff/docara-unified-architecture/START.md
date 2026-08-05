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

Current state: `goal_c_in_progress`

Current goal: `docara.goal.unified`

Current stage: `docara.stage.c.public_documentation`

Current batch: `docara.batch.c.public_documentation`

Current next action: `execute_goal_c_c1_components`

Current evidence: `source/workflow/evidence/2026-08-05-docara-goal-c-public-documentation/INDEX.md`

Current candidate: `481e34cccade12a0d7f8d2dbf9b4d37933e49419`

Next roadmap goal: `docara.goal.c.public_documentation`

Next roadmap status: `in_progress`

Next roadmap authorized: `true`

Goal 1, Goal 2 and Goal B were independently accepted with `PASS_WITH_NOTES`;
Goal 3 and Goal A were accepted with `PASS`. Goal C is now authorized. C0 keeps
all 104 entry routes canonical and freezes navigation/redirect decisions. The
next work is C1 through C6; release review remains unauthorized.

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

Execute Goal C C1-C6 from the active recovery file. Preserve accepted Goal 1-3,
Goal A and Goal B runtime/support invariants. Stop at
`goal_c_ready_for_independent_audit`; do not self-accept or start release review.

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
