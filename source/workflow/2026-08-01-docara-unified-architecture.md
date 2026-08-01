# Docara unified architecture workflow

Date: 2026-08-01
State: architecture-contract checkpoint passed; M1 ready
Goal: `docara.goal.unified`
Exact baseline: `a3ba9a4d04429f1f2046b8415764fe7bc89962c7`

## Why this workflow exists

Docara accumulated useful product capabilities and visual work, but also
parallel sources of page content, several rendering paths and generated
projections that obscure ownership. Long task history became an unreliable way
to reconstruct the intended architecture.

This workflow makes the repository itself sufficient for a fresh task. The
human specification describes the product and architecture; the graph records
state, requirements, decisions, risks, gates and implementation mappings; the
handoff limits the next executable batch.

## Outcome

Docara becomes a small modular static-site generator with four separate layers:

1. physical Markdown owns public content;
2. JSON owns site, section and page composition;
3. Smart components own reusable presentation and behavior;
4. one engine pipeline derives typed IR and HTML.

The accepted outcome is defined in `docs/specification/DOCARA-TZ.md` and
`graph/specs/goals/unified-docara.json`.

## Current executable scope

Only `docara.batch.m0.mapping` is ready:

- inventory current content owners and route producers;
- map current classes/functions to target modules;
- reproduce tests and deterministic build baseline;
- capture one badge-page baseline;
- update exact code/test/evidence references in implementation mappings;
- propose the smallest M1/M2 implementation batch.

This batch is read-only with respect to product runtime. Documentation, graph,
evidence and mapping files may be updated.

## Routing note

The active federation route identifies the Docara domain, but the installed
Docara skill describes a retired architecture and is intentionally excluded.
Until it is replaced, use `teamlead + graph + dev`; connect `content`, `ux` and
`tester` only when the corresponding contract is exercised. This graph gap
does not authorize using chat history as source of truth.

## Safety and action-gate evidence

The adjacent clean worktree was created from the accepted consolidation
revision after a passing action-gate preflight. Evidence:

- `/Users/rim/Documents/GitHub/larena-workspace/source/worktrees/docara-consolidation/source/output/action-gates/action-gate-report-20260801064157.json`;
- `/Users/rim/Documents/GitHub/larena-workspace/source/worktrees/docara-consolidation/source/output/action-gates/pretooluse-command-approvals/command-approval-20260801064157-7108b2cd2863.json`.

These paths document the creation boundary; new git-history, release or deploy
actions require their own current gate where applicable.

## Historical evidence

The current long-running task and the files under `source/workflow/` remain
historical evidence for product intent and visual decisions. A fresh task may
consult them only to resolve a gap not covered by the accepted specification.
Any recovered decision must be promoted into `docs/specification` and
`graph/specs/decisions` before it governs implementation.

## Completion rule

The architecture track is complete only when `docs/specification/ACCEPTANCE.md`
and `graph/specs/gates/architecture-acceptance.json` pass with immutable
evidence. Activity, screenshots or a successful build alone are not completion.

## M0 progress

- exact code, test, gap and deletion-gate references are recorded in all six
  implementation mappings;
- the 103-route inventory, dependency tuple, deterministic 321-file build
  manifest and badge trace are preserved under
  `source/workflow/evidence/2026-08-01-docara-unified-architecture/`;
- two inherited documentation-count tests and the formatter baseline are red;
- `DOC-ADR-015` resolves the gate-order divergence: badge-source readiness
  precedes M2 and global source ownership remains open through M3;
- `DOC-ADR-016` records library/consumer Composer lock ownership;
- the target now has one `content/<locale>/lang.json`, in-memory typed IR and
  one PageBuilder pipeline selected only by route set;
- `docara.gate.m0_baseline` is accepted and `STATUS.yaml` advances to M1 after
  green checkpoint verification;
- next bounded proposal:
  `source/workflow/2026-08-01-docara-m1-m2-bounded-plan.md`.
