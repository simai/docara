# Full QA Plan: Docara pre-release legacy and simplicity

Date: 2026-07-22
Owner skill: `tester`
Status: completed

## 1. Purpose

Prove what belongs to the new Docara product, what is legacy or debris, what
complexity protects required behavior, and what can be removed or merged before
release without breaking the simplest complete author workflow.

## 2. Intake And Approval

- Target: new Docara generator and bundled documentation candidate.
- Repository: current worktree of `simai/docara`.
- Candidate: `9b1290bf547a8c87651704a9554be0acc881aebf`.
- Environment: local macOS/ServBay, read-only source audit plus disposable
  build/install checks.
- Primary outcome: a developer can install, author, build and update Docara
  through one understandable model without knowledge of the retired product.
- Legacy reference: local `origin/main`; old repositories are evidence only.
- Safety: no code fixes, deletes, runtime writes, push, merge, release or tag.
- Test data: not applicable; disposable temp directories only.
- Cleanup: disposable directories were retained under `/tmp` because the
  approved audit boundary explicitly prohibited deletion; they do not affect
  repository or runtime state.
- Fix policy: findings first; fixes require a separate owner batch.
- Approval: original request explicitly asks to conduct the detailed audit;
  plan and execution may proceed without an extra pause.

## 3. Acceptance Criteria

| ID | Criterion | Evidence |
| --- | --- | --- |
| AC-1 | Runtime and public package contain no active dependency on Jigsaw, Laravel Mix or retired Docara repositories | dependency graph, symbol and package scans |
| AC-2 | One canonical install/build/update path is discoverable and reproducible | README/docs/CLI matrix and disposable run |
| AC-3 | Every major retained layer has an outcome or protective-invariant link | engineering necessity map |
| AC-4 | Repository does not publish working history, generated debris or local artifacts as product payload | tracked census, package/export boundary |
| AC-5 | Compatibility and migration behavior is explicit, bounded and removable | compatibility inventory and deprecation map |
| AC-6 | Documentation describes only supported current concepts and commands | documentation legacy/staleness scan |
| AC-7 | Findings are fix-ready and preserve known working invariants | findings register and retest matrix |

## 4. Coverage

| Layer | Applies | Planned checks |
| --- | --- | --- |
| L0 environment | yes | Git/runtime/tool availability and clean baseline |
| L1 repo/static | yes | tracked census, ignored/local artifacts, dependencies, naming leakage |
| L2 install/update | yes | portable init, build, verifier, update/migration contract |
| L3 route/output inventory | yes | generated page/asset/reference inventory |
| L4 browser | limited | representative current output only if static evidence is insufficient |
| L5 functional | yes | CLI author/build scenario in disposable target |
| L6 roles | N-A | static generator has no role model |
| L7 source of truth | yes | config/Markdown/registry/build ownership map |
| L8 reference-adaptive | yes | legacy concepts as comparison, not parity target |
| L9 UX/a11y | limited | current protected invariants from accepted evidence |
| L10 SEO | limited | only generated/public contract drift relevant to cleanup |
| L11 security | limited | secret/artifact exposure and unsafe generated output |
| L12 performance | limited | complexity/size/build-time signals, no load testing |
| L13 cross-solution | yes | Framework and future ui-doc consumer boundaries |
| L14 fix/retest | planned later | no fixes in this run |

## 5. Scenario Matrix

| ID | Scenario | Write level | Expected |
| --- | --- | --- | --- |
| S-001 | inspect exact candidate and compare local legacy ref | read-only | boundaries are reproducible |
| S-002 | create portable project in a disposable directory | disposable safe-write | no legacy toolchain or repository required |
| S-003 | build bundled documentation and verify references | local generated output | deterministic valid site |
| S-004 | trace one page from Markdown/JSON through renderer to HTML | read-only | one understandable canonical chain |
| S-005 | inspect update/migration/compatibility branches | read-only | legacy support is explicit and bounded |
| S-006 | inspect package/export contents | read-only | local workflow/evidence and retired assets are excluded |

## 6. Test Waves

1. Baseline, repository census and legacy signatures.
2. Dependencies, command surface and architecture necessity.
3. Disposable install/build/update and generated package boundary.
4. Documentation and public naming consistency.
5. Findings, safe cleanup batches and final readiness matrix.

## 7. Exit Gate

The audit closes only when all acceptance criteria have evidence or an explicit
`BLOCKED`/`NOT RUN` reason, every confirmed issue has a stable ID and the final
verdict distinguishes "audit complete" from "release ready".

## 8. Final Criterion Matrix

| ID | Result | Evidence |
| --- | --- | --- |
| AC-1 | FAIL | Old runtime, starter and dependencies remain active and exported |
| AC-2 | FAIL | Portable flow works, but normal CLI still defaults to legacy and README pins a stale SHA |
| AC-3 | FAIL | Useful declarative layers are justified; fallback publisher, template mirror and always-on previews are not release-essential |
| AC-4 | FAIL | Workflow evidence is export-ignored, but old starter/docs/release-only tools and full diagnostics remain in product/public payloads |
| AC-5 | FAIL | Compatibility is active by default and has no enforced removal boundary |
| AC-6 | FAIL | Public docs mix modes and the installed Docara skill still teaches Jigsaw |
| AC-7 | PASS | Findings `DCR-001`–`DCR-013`, protected invariants and retest batches are recorded |

Audit completion: PASS. Release verdict: `CORRECTION_REQUIRED`.
