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

Current state: `goal1_ready_for_independent_audit`

Current stage: `docara.stage.g1.portable_smart_runtime`

Current batch: `docara.batch.g1.portable_smart_runtime`

Current next action: `independent_goal1_reverse_outcome_audit`

Current evidence: `source/workflow/evidence/2026-08-02-docara-goal1d-generic-smart-view-correction/INDEX.md`

Goal 2 status: `unstarted`

Goal 1 is implementation-complete and awaits an independent reverse-outcome
audit. It is not accepted by executor evidence. Goal 2 Design Registry/Preview
and Goal 3 SDK/MCP are not authorized until their preceding independent gate.

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

## 4. Execute only the current gate

The only current action is an independent Goal 1 reverse-outcome audit using
the exact candidate and evidence above. Do not continue to Goal 2 after an
executor-only PASS. A separate independent acceptance must first update the
canonical graph and regenerate the router.

M0/M1/M2/M3/M4/M5/R1/R2 files and release artifacts are historical baselines.
They may support regression analysis but cannot become the current stage,
batch, candidate or next action unless a new canonical graph transition is
explicitly accepted.

## 5. Forbidden shortcuts

- do not move public prose into JSON, PHP projectors or component manifests;
- do not create a second parser, renderer, Gateway or PageBuilder;
- do not delete legacy without parity and rollback evidence;
- do not start Goal 2 or Goal 3 from this handoff;
- do not merge, push, tag, release, publish or deploy;
- do not write to `docara.test` or `docara-new.test`;
- do not claim independent acceptance from executor evidence.
