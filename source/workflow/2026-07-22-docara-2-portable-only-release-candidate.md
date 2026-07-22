# Workflow: Docara 2 portable-only release candidate

Date: 2026-07-22
Status: in progress
Workflow ID: `2026-07-22-docara-2-portable-only-release-candidate`
Process model: `release`
Current state: `repository_prepared`
Target state: `review_ready`
Parent track: `docara-consolidation`
Owner: `docara`
Coordinator: `teamlead`
Implementation owner: `dev`
Gatekeeper: `tester`
Recovery source: this file
Baseline HEAD: `9b1290bf547a8c87651704a9554be0acc881aebf`
Remote-only input: `a913dce60b7246d24f9afaf6e9f259a5b7c097e0`
Source audit: `source/workflow/2026-07-22-docara-pre-release-legacy-simplicity-audit.md`
Fix handoff: `output/qa-runs/2026-07-22_docara_pre-release-simplicity/fix-handoff.md`

## Current Goal

Produce a clean, simple and verifiable Docara 2 candidate with one portable
Markdown/JSON product model, one CLI, one builder and one starter. Remove the
old Jigsaw/Mix runtime and transitional implementation while preserving the
accepted declarative architecture, Simai Framework integration, multilingual
routing, deterministic builds, update safety and static verification.

## Done When

- the remote Windows path-safety fix is integrated without losing local work;
- the exact combined baseline passes PHPUnit, Pint, Composer validation, PHP
  lint, documentation build, static verification and deterministic rebuild;
- `docara init` creates the current portable project without a legacy flag;
- the public CLI contains only supported Docara 2 commands and terminology;
- legacy Jigsaw/Mix source, starter, tests, stubs and old-only dependencies are
  absent from the Docara 2 candidate;
- the new builder boots without legacy providers or configuration files;
- temporary renderer/publisher selection and template-mirror machinery are
  removed after parity evidence;
- generated production output contains compact receipts, not development
  previews or full resolved-plan dumps by default;
- source and Composer-dist archives both pass clean init/build/verify smoke;
- user/developer documentation and the canonical Docara skill describe the
  same current product model;
- an exact archive, not the executor worktree, receives a tester acceptance
  verdict with a release-readiness matrix;
- no tag, public release, push, production deploy or production-readiness claim
  is performed inside this workflow.

## Protected Invariants

- portable update preservation and explicit overwrite rules;
- path confinement on POSIX and Windows;
- atomic publication with rollback-safe staging;
- byte-deterministic production output;
- arbitrary BCP 47 locale registry and symmetric locale routing;
- Simai Framework revision pinning and immutable framework admission;
- registered-template and Smart-prop validation;
- accessible navigation, search and responsive output;
- static local-reference verification.

## Safety And Rollback

- Legacy removal is a destructive repository operation and requires the
  federation preflight action gate before deletion.
- Git history is the rollback source: changes are committed in bounded batches,
  and no rebase, force-push, tag rewrite or branch deletion is allowed.
- The accepted 1.x history and tags remain untouched.
- Generated documentation and disposable build directories are evidence only;
  they are not committed as product source.
- The installed/public Docara runtime is not changed before an exact candidate
  passes source and dist acceptance.

## Stages

1. Repository preparation and combined green baseline.
2. Portable-only product boundary and legacy removal.
3. Transitional-layer and production-output simplification.
4. Documentation, skill and package alignment.
5. Exact-archive acceptance and release-ready handoff.

## Batches

| Batch | Outcome | Exit gate | Status |
| --- | --- | --- | --- |
| B0 | Integrate Windows fix and restore a green combined baseline | exact SHA; full checks green | completed |
| B1 | Make portable CLI/builder/starter the only product path | clean init/build/update/verify smoke | completed |
| B2 | Remove Jigsaw/Mix source, tests, stubs and dependencies | no legacy runtime references; full tests green | in progress |
| B3 | Remove transitional renderer/publisher/mirror and output bloat | parity, deterministic build, compact output | planned |
| B4 | Simplify CI, repository surface, docs and Docara skill | source/dist docs and contract agree | planned |
| B5 | Exact-archive independent acceptance and handoff | tester verdict and readiness matrix | planned |

## Scope

- Docara source, CLI, tests, fixtures, starter, docs and CI in this worktree;
- Composer dependency and package metadata changes justified by remaining code;
- canonical Docara skill source in its owner repository, after its owner-write
  and Skill Sync gates;
- local ignored QA/workflow evidence required for acceptance.

## Explicit Non-Goals

- public GitHub push, merge to `main`, release tag or package publication;
- public or production deployment;
- deletion or archival of separate external repositories;
- changing Simai Framework generated distributions;
- moving product-owned `docara.*` Smart components into `ui-smart`.

## Verification Matrix

| Layer | Required evidence |
| --- | --- |
| repository | clean tracked worktree, diff check, secret/hygiene checks |
| static | Composer validate, PHP lint, Pint |
| unit/integration | full PHPUnit plus focused negative path/update tests |
| product | fresh init, build, update preservation, verify-static |
| determinism | two independent production builds byte-identical |
| package | source checkout and Composer-dist archive smoke |
| browser | representative desktop/mobile documentation smoke |
| release | exact archive verdict, changelog, verification log, release notes |

## Evidence Plan

Evidence is written under
`source/workflow/evidence/2026-07-22-docara-2-portable-only-release-candidate/`
with one directory per batch. B0 records the merge input, exact candidate and
green baseline. Later batches record inventories, protected-invariant tests,
source/dist smoke, deterministic hashes, browser evidence, simplicity review
and the final tester verdict.

## Track Linkage

- Track: `docara-consolidation`.
- Previous accepted goal: symmetric locale routing.
- Current goal: portable-only Docara 2 release candidate.
- A public release remains a separate future goal after this candidate is
  accepted.

## Stop Conditions

- any protected invariant loses executable coverage;
- legacy removal is attempted before the destructive preflight gate passes;
- a needed behavior exists only in a legacy path without a complete replacement;
- source checkout passes but the distributable package cannot initialize and
  build without repository-only files;
- a release, push or deployment would be required to continue.

## Current Next Step

Delete the now-unreachable Jigsaw/Mix runtime, tests and starter after the
portable-only CLI boundary has passed its focused init and preview checks.

## Gate Evidence

- Initial preflight correctly failed before rollback evidence was recorded:
  `source/output/action-gates/action-gate-report-20260722173505.json`.
- Reversible local cleanup preflight completed with warnings and no blockers:
  `source/output/action-gates/action-gate-report-20260722173527.json`.
- Recovery point: Git commit
  `9b1290bf547a8c87651704a9554be0acc881aebf`.
- Rollback method: revert only the bounded cleanup commit; history rewrite is
  forbidden.
