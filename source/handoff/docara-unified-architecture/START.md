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

Current state: `goal3_ready_for_independent_audit`

Current goal: `docara.goal.unified`

Current stage: `docara.stage.g3.developer_sdk`

Current batch: `docara.batch.g3.developer_sdk`

Current next action: `independent_goal3_reverse_outcome_audit`

Current evidence: `source/workflow/evidence/2026-08-03-docara-goal3-correction/INDEX.md`

Current candidate: `6f547810583a16114ed15a8199f698e1dadb70a9`

Next roadmap goal: `docara.goal.3.independent_audit`

Next roadmap status: `ready_for_independent_audit`

Next roadmap authorized: `true`

Goal 1 and Goal 2 were independently accepted with `PASS_WITH_NOTES`.
Goal 3 Developer/AI SDK implementation is complete and stops at
`ready_for_independent_audit`; the only current action is its independent
reverse-outcome audit. Release review remains unauthorized.

## 3. Read in this order

1. `source/handoff/docara-unified-architecture/STATUS.yaml`;
2. `source/workflow/ACTIVE.md`;
3. `source/workflow/2026-08-02-docara-extensible-lego-architecture-plan.md`;
4. `graph/graph.json` and its current stage/batch specs;
5. `graph/generated/ai-context/docara-unified.json` as a checked derived view;
6. `source/handoff/docara-unified-architecture/NEXT.md` and `RESULT.md`;
7. `docs/specification/README.md` and its linked contracts.

Canonical current state belongs to `graph/graph.json` plus `graph/specs`.
`graph/generated/ai-context/docara-unified.json` must be regenerated and
checked by the repository command documented in `graph/README.md`; it never
overrides canonical graph or workflow sources.

## 4. Execute only the current goal

Independently audit the completed Goal 3 correction against
`source/workflow/2026-08-03-docara-goal3-security-diagnostics-visual-correction.md`
and the current evidence index. Preserve accepted Goal 1/2 runtime invariants.
Do not implement Goal 4 and do not start release review.

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
