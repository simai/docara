# Goal 1-D — project context freshness correction

Date: 2026-08-03
Status: `in_progress`
Repository: `/Users/rim/Documents/GitHub/docara-unified`
Branch: `codex/docara-unified-architecture`
Input revision: `65097a45b2a39ec8350c0f4a05f95dc7c9c80590`
Parent roadmap: `source/workflow/2026-08-02-docara-extensible-lego-architecture-plan.md`
Evidence: `source/workflow/evidence/2026-08-03-docara-goal1d-project-context-correction/INDEX.md`

## Goal

Make the handoff and derived project context a deterministic, unambiguous
router for the current LEGO roadmap without changing the accepted Goal 1
runtime or public site sources.

## Done When

- canonical graph state, generated AI context and handoff agree on Goal 1
  `ready_for_independent_audit` and the independent audit next action;
- Goal 2 is explicit, unstarted and unauthorized;
- R2/release/deploy facts are historical only and cannot become an active
  instruction;
- one repository-owned deterministic command generates and checks the context;
- a permanent positive/negative regression detects stale stage, batch, next
  action, evidence or candidate state;
- source QA, exact cross-host and unchanged build/static/single evidence pass;
- worktree is clean at a dedicated correction candidate.

## Source Boundaries

Canonical:

- `graph/graph.json` and referenced `graph/specs`;
- `source/workflow/2026-08-02-docara-extensible-lego-architecture-plan.md` for
  the accepted sequential Goal 1 -> Goal 2 -> Goal 3 roadmap.

Derived/router surfaces:

- `graph/generated/ai-context/docara-unified.json`;
- `source/handoff/docara-unified-architecture/{START,STATUS,NEXT,RESULT}`;
- `source/workflow/ACTIVE.md`.

The generated packet is never a canonical source and is not edited as an
independent decision record.

## Batches

| Batch | Work | Verification | Status |
| --- | --- | --- | --- |
| G1D-C1 | record stale R2/M1 router and canonical ownership | before semantic diff, action gates | pass |
| G1D-C2 | deterministic project-context generator and synchronized handoff | positive/negative freshness tests | in progress |
| G1D-C3 | full source/build/static/single/cross-host retest | exact commands and hashes | pending |
| G1D-C4 | evidence, graph/handoff final state and clean commit | reverse-outcome review | pending |

## Constraints And Stop Conditions

- no runtime, public content, Smart ABI/provider or browser asset change;
- no Goal 2/Goal 3 implementation;
- no external repository/site write, release, tag, merge or deploy;
- stop if canonical inputs cannot determine one context, or if synchronization
  requires making R2 deploy current.

## Rollback

Revert the dedicated correction commits in reverse order. Historical R2
evidence is preserved; no history rewrite or deletion is required.
