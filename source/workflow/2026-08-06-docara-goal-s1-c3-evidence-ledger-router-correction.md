# Workflow: Goal S1-C3 evidence ledger and router hygiene correction

Date: 2026-08-06
Status: completed
Track: `docara.track.surface-hero-media`
Goal: `docara.goal.s1_c3`
Current batch: `docara.batch.s1.evidence_ledger_router_correction`
Frozen product candidate: `ac53ea4d372a47dc8278b595accca9e7b85c66a3`
Entry governance HEAD: `7e350d38feb5f692ce516ca4f72981f835f659e5`

## Goal

Correct only Goal S1 evidence and current routing so the canonical public-build
ledger is reproducible from the frozen exact candidate and every current
workflow/graph/handoff projection points to one S1-C3 audit-pending state.

## Done When

- two fresh full builds, a representative single-page rebuild and a clean
  no-local clone produce 393 files and canonical tree digest
  `650a678ccddcfac806e1c0c3b2d5327565a01ae06d0b77b5ed8a501c3118d10e`;
- both full roots pass static verification at 266 HTML / 35,581 references /
  `broken=[]`;
- `90bf6378...` is labeled rejected/unreproducible and is nowhere a current
  exact-candidate claim;
- current workflow, graph, generated context and handoff expose only S1-C3 as
  audit-pending, with S2 unauthorized;
- product/runtime/public-documentation trees remain byte-identical to
  `ac53ea4...`;
- project-context, graph, JSON/YAML, focused documentation/context and diff
  checks pass, the tracked worktree is clean, and Goal S1 remains
  `ready_for_independent_audit` without self-acceptance.

## Launch Record

- owner: `teamlead` with `dev` repository execution fallback;
- route note: the federation selected the disabled stale Docara skill; the
  assignment explicitly forbids it, so repository workflow/specification/graph
  remain authoritative;
- allowed writes: `source/workflow`,
  `source/handoff/docara-unified-architecture`, `docs/specification`, `graph`;
- forbidden writes: `src`, `resources`, `stubs`, `docs/site`, `tests`,
  `scripts`, `docara`, `composer.json`, `phpunit.xml`, external repositories
  and all site roots;
- evidence path:
  `source/workflow/evidence/2026-08-06-docara-surface-hero/S1-C3-EVIDENCE-LEDGER-ROUTER-CORRECTION.md`;
- rollback: revert the final governance-only commit; the frozen product commit
  remains unchanged;
- stop conditions: canonical ledger mismatch in a clean clone, any required
  product change, or overlapping user changes.

## Canonical Ledger Contract

The implementation in `scripts/atomic-static-cutover.php::treeDigest()` is the
authority: hash every regular file, key records by normalized relative path,
sort those paths with bytewise `SORT_STRING`, join
`<file-sha256>  <relative-path>` records with one newline and hash the joined
stream including its final newline.

## Batch Plan

| Batch | Work | Verification | Status |
| --- | --- | --- | --- |
| C3.0 | Freeze boundary and record launch contract | branch/HEAD/status, action gate, immutable path list | completed |
| C3.1 | Reproduce exact ledger | full/full/single, clean clone, static verifier | completed |
| C3.2 | Correct evidence/router/graph/handoff | negative scans, generated context freshness | completed |
| C3.3 | Integrated governance checks and commit | focused tests, graph/context, JSON/YAML, diff, clean status | completed |

## Current Status

- completed: source/branch/HEAD/clean boundary, action-gate preflight, exact
  path-sorted ledger reproduction in full/full/single and clean clone, static
  checks, evidence correction, router/graph/handoff synchronization and
  governance verification;
- verification: all four roots contain 393 files and share
  `650a678c...`; both full roots and the clone report 266 HTML / 35,581
  references / broken=0; focused documentation/context is 30 tests / 2,513
  assertions; project-context and project graph pass with zero issues,
  warnings or blockers;
- remaining: independent read-only Goal S1 audit only;
- next: `independent_goal_s1_reverse_outcome_audit`.

## Nonclaims

No S2 implementation, Hero background mode, product/runtime/public-guide
change, release review, merge, push, tag, release, deploy or site write is
authorized or claimed.
