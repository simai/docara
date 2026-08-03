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

Current state: `goal2_ready_for_independent_audit`

Current goal: `docara.goal.unified`

Current stage: `docara.stage.g2.design_registry_preview`

Current batch: `docara.batch.g2.design_registry_preview`

Current next action: `independent_goal2_reverse_outcome_audit`

Current evidence: `source/workflow/evidence/2026-08-03-docara-goal2-design-registry-preview/INDEX.md`

Current candidate: `33a377758f12d02a34e50c2f4f6d2aa760cf678b`

Next roadmap goal: `docara.goal.3.developer_sdk`

Next roadmap status: `unstarted`

Next roadmap authorized: `false`

Goal 1 was independently accepted with `PASS_WITH_NOTES`. Goal 2 Design
Registry/Preview implementation is complete and awaits independent audit.
Goal 3 SDK/MCP is not authorized until that audit accepts the exact candidate.

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

Audit G2.0-G2.6 against
`source/workflow/2026-08-03-docara-goal2-design-registry-preview.md` and the
current evidence index. Preserve the accepted Goal 1
Gateway/provider/PageBuilder/runtime invariants. Do not accept Goal 2 from
executor evidence and do not start Goal 3.

M0/M1/M2/M3/M4/M5/R1/R2 files and release artifacts are historical baselines.
They may support regression analysis but cannot become the current stage,
batch, candidate or next action unless a new canonical graph transition is
explicitly accepted.

## 5. Forbidden shortcuts

- do not move public prose into JSON, PHP projectors or component manifests;
- do not create a second parser, renderer, Gateway or PageBuilder;
- do not delete legacy without parity and rollback evidence;
- do not start Goal 3 from this handoff;
- do not merge, push, tag, release, publish or deploy;
- do not write to `docara.test` or `docara-new.test`;
- do not claim independent acceptance from executor evidence.
