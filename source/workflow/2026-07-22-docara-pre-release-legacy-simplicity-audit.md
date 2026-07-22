# Workflow: Docara pre-release legacy and simplicity audit

Date: 2026-07-22
Status: completed
Mode: productization, read-only audit

## Goal

Determine whether the new Docara candidate is free from obsolete Docara/Jigsaw/
Mix/template-era implementation, repository debris, speculative abstractions
and unnecessary complexity, then provide a fix-ready cleanup order that
preserves the working portable install, declarative renderer, Simai Framework
integration, multilingual routing, build verification and rollback paths.

## Done When

- the exact candidate and legacy comparison baselines are recorded;
- tracked and generated surfaces are inventoried by purpose and owner;
- legacy signatures, dependencies, commands, templates and documentation are
  classified as active, compatibility-only, historical evidence or removable;
- every retained major layer is linked to a user outcome or protective
  invariant, and a simpler complete alternative is considered;
- clean install/build/update and release-hygiene gaps are checked without
  mutating runtime or deleting files;
- findings have stable IDs, evidence, do-not-break constraints, safe change
  boundaries and retest steps;
- a phased cleanup plan and bounded readiness verdict are written.

## Context And Assumptions

- Candidate: branch `codex/docara-consolidation`, initial audit HEAD
  `9b1290bf547a8c87651704a9554be0acc881aebf`.
- Local legacy Git reference: `origin/main` at
  `01ce6cc1454171ef813c0eb889760f7087de60eb` (not fetched in this audit).
- Working site is a disposable local acceptance surface, not the source of
  truth.
- The request authorizes the audit, not deletion, refactoring, release, merge,
  tag, push or public publication.
- Federation route repair reported symlink mismatch; action-gate routing then
  selected `tester` with `teamlead` and `docara`. Raw owner sources are used
  for the missing graph/process selection and human-centered simplicity.

## Constraints And Risks

- Do not turn discovery into opportunistic fixes.
- Do not delete or archive `docara-mix`, `docara-template`, legacy branches or
  tracked evidence during this workflow.
- Do not call historical evidence runtime code merely because it contains
  legacy names; classify ownership and publication impact first.
- Do not simplify away validation, deterministic build evidence, update safety,
  localization, accessibility, error handling or rollback.
- Existing local `origin/main` may be stale; claims comparing history are
  explicitly limited to the recorded reference.

## Ownership

| Owner | Role | Scope |
| --- | --- | --- |
| `docara` | product owner | expected project, CLI, authoring and build model |
| `teamlead` | coordinator | scope, architecture, ordering and handoff |
| `dev` | consulted | engineering necessity and repository simplicity |
| `tester` | gatekeeper | evidence, findings and release-readiness verdict |

## Batch Plan

| Batch | Goal | Verification | Status |
| --- | --- | --- | --- |
| 1 | Freeze baseline and QA plan | Git identity, action gate, matrices | completed |
| 2 | Inventory legacy and repository debris | tracked-file census, dependency and signature scans | completed |
| 3 | Audit architecture and complexity | layer map, necessity map, coupling/duplication metrics | completed |
| 4 | Audit install/update/build/docs | disposable install/build, docs/commands consistency | completed |
| 5 | Produce findings and cleanup sequence | fix-ready report and protected invariants | completed |
| 6 | Reverse-audit evidence and issue verdict | criterion-to-evidence matrix, not-run list | completed |

## Verification Paths

- QA control: `source/qa/2026-07-22-docara-pre-release-simplicity/`
- Generated report: `output/qa-runs/2026-07-22_docara_pre-release-simplicity/`
- Action gate:
  `source/output/action-gates/action-gate-report-20260722154042.json`

## Final Status

- Audit completion: PASS.
- Release readiness: `CORRECTION_REQUIRED`.
- Confirmed findings: 13 (`DCR-001` through `DCR-013`).
- Product/runtime writes: none.
- Generated documentation output was rebuilt and verified locally; it remains
  ignored and is not a source change.
- Disposable successful install/build/verify target:
  `/tmp/docara-audit.ZTFlnf`; retained because the audit boundary prohibited
  deletion.
- Detailed report:
  `output/qa-runs/2026-07-22_docara_pre-release-simplicity/qa-report.md`.
- Fix handoff:
  `output/qa-runs/2026-07-22_docara_pre-release-simplicity/fix-handoff.md`.
