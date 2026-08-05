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

Current state: `ready_for_user_release_decision`

Current goal: `docara.goal.unified`

Current stage: `docara.stage.c.public_documentation`

Current batch: `docara.batch.c.public_documentation`

Current next action: `explicit_user_release_decision`

Current evidence: `source/workflow/evidence/2026-08-05-docara-goal-c-c1-truthfulness-correction/INDEX.md`

Current candidate: `eb35f5c6f18e5eb9be69e91887b09486f5703136`

Next roadmap goal: `docara.decision.release_review`

Next roadmap status: `awaiting_explicit_user_decision`

Next roadmap authorized: `false`

Goal 1, Goal 2 and Goal B were independently accepted with `PASS_WITH_NOTES`;
Goal 3, Goal A and Goal C were accepted with `PASS`. Goal C is frozen on exact
product candidate `eb35f5c6…`; all 132 routes remain physical Markdown owners
and the content/design/settings product track is complete. Release review and
all live actions remain unauthorized until an explicit user decision.

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

## 4. Execute only an explicitly authorized next action

There is no active implementation goal. Preserve the accepted product
candidate and its Goal 1-3/A/B/C invariants. The next action is only the user's
explicit decision whether to open a separate release-review/merge/tag/deploy
workflow; this handoff does not authorize any of those actions.

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
