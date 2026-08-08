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

Expected branch: `main` after the accepted convergence. Preserve any user changes
and stop on an overlapping dirty worktree. Do not use the installed stale
Docara skill and do not work in another checkout or site root.

## 2. Current router

Current state: `docara_main_converged_ui_doc_migration_next`

Current goal: `docara.goal.unified`

Current stage: `docara.stage.lfr.local_framework_runtime`

Current batch: `docara.batch.lfr.integrated_retest`

Current next action: `migrate_ui_doc_content_onto_docara_v2_then_converge_ui_doc_main`

Current evidence: `source/workflow/evidence/2026-08-08-docara-main-convergence/INDEX.md`

Current candidate: `d5e9ecbb1b65904b4015c4a8b8db3aa66d7fe30f`

Next roadmap goal: `ui_doc.content_migration`

Next roadmap status: `authorized_in_progress`

Next roadmap authorized: `true`

Goal 1-3 and Goals A-C remain independently accepted. The explicitly
authorized post-roadmap Surface & Hero Media track has independently accepted
Goal S1 at exact product `ac53ea4…` and governance `4feb910…`. Goal S2/S2-C1 is
independently accepted on exact candidate `7eeba4a…`. Goal S3 is complete on
`dd2c0d6…`; the Surface & Hero Media track now waits for an explicit user
decision. SF5 5.4 typography is the accepted parent. The authorized local
Framework runtime correction including all documented icon families is
complete. Candidate `d5e9ecb…` preserves the component-catalogue information
architecture, completes the local Outlined font, restores the Success Alert
icon color and aligns the Alert guide with the Badge reference sequence;
`docara-new.test` is switched to its exact build with rollback preserved. It
is independently accepted with `PASS_WITH_NOTES`. Framework and Docara history
convergence are now accepted on their respective `main` targets. The next action
is the separately authorized migration of useful legacy ui-doc prose into the
v2 Markdown ownership model, followed by ui-doc `main` convergence. No S4/Goal
D, tag, release, deployment or production action is authorized.

## 3. Read in this order

1. `source/handoff/docara-unified-architecture/STATUS.yaml`;
2. `source/workflow/ACTIVE.md`;
3. `source/workflow/2026-08-07-docara-alert-page-correction.md`
   and its exact evidence index, then the component information architecture
   and parent local Framework runtime;
4. `graph/graph.json` and its current stage/batch specs;
5. `graph/generated/ai-context/docara-unified.json` as a checked derived view;
6. `source/handoff/docara-unified-architecture/NEXT.md` and `RESULT.md`;
7. `docs/specification/README.md` and its linked contracts.

Canonical current state belongs to `graph/graph.json` plus `graph/specs`.
`graph/generated/ai-context/docara-unified.json` must be regenerated and
checked by the repository command documented in `graph/README.md`; it never
overrides canonical graph or workflow sources.

## 4. Execute only the authorized ui-doc migration action

Converge the accepted Framework work into Framework `main`, bind Docara to that
exact immutable owner identity, then run the bounded Docara `main` integration
and its complete checks. Follow the external coordination workflow; do not
invent S4/Goal D or begin tag, release or deployment work.

M0/M1/M2/M3/M4/M5/R1/R2 files and release artifacts are historical baselines.
They may support regression analysis but cannot become the current stage,
batch, candidate or next action unless a new canonical graph transition is
explicitly accepted.

## 5. Forbidden shortcuts

- do not move public prose into JSON, PHP projectors or component manifests;
- do not create a second parser, renderer, Gateway or PageBuilder;
- do not delete legacy without parity and rollback evidence;
- do not merge or push outside the exact accepted convergence candidates;
- do not tag, release, publish or deploy;
- do not write to `docara.test` or change the already verified
  `docara-new.test` validation tree;
- do not weaken the independent evidence boundary for later candidates.
