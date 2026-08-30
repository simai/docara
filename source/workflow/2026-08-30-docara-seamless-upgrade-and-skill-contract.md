# Workflow: seamless Docara upgrade and adaptive AI contract

Date: 2026-08-30
Status: complete

## Goal

Add a project-local, transactional `docara upgrade` workflow and a generated
machine-readable capabilities contract so compatible Docara 2.x projects can be
updated safely while one canonical Docara skill adapts to the exact installed
package.

## Done When

- `capabilities --json` reports the exact package version, actual CLI options,
  schemas, registries, receipts, update/rollback support, provenance and a
  deterministic contract hash through CLI and MCP.
- `upgrade --check`, dry-run/apply, automatic same-major upgrade and rollback
  have deterministic schemas, stale-input protection and offline rollback.
- Candidate validation covers doctor, project validation, engine staging,
  production build and static verification before current runtime promotion.
- Project-owned content and the last verified build remain unchanged on every
  failed path.
- Project-local and legacy-runtime fixtures, interruption recovery, exact
  version constraints and major refusal are tested.
- Public documentation, graph/specs and canonical Docara skill describe the
  same contract; Skill Sync Gate is green.
- No commit, push, tag, package publication or deploy is performed.

## Context

- Approved target architecture is the user-provided implementation plan.
- The Docara checkout already contains a known unfinished performance and
  responsive-images diff; it is preserved as baseline.
- Native View Transition graph/workflow files are unrelated user work and are
  forbidden for this workflow.
- The canonical skill checkout is clean at workflow start.
- The local `ai-codex` checkout is divergent and dirty; no direct writes are
  allowed there. Federation compatibility is verified against the installed
  control plane and represented by canonical skill release-contract evidence.

## Constraints And Risks

- No project-owned Markdown, examples, assets, settings, Framework locks or
  translation state may be edited by upgrade.
- Only stable exact patch/minor targets inside the current major may apply.
- Builds and ordinary Docara commands remain offline; only explicit `upgrade`
  may use Composer/network.
- Both dependency states must be available before promotion; rollback must not
  require network.
- Canonical skill writes require action-gate evidence and the complete Skill
  Sync Gate.

## Batch Plan

| Batch | Goal | Verification | Status |
| --- | --- | --- | --- |
| 1 | Baseline, workflow and contract model | status, route/action gate, schema design review | complete |
| 2 | Capabilities CLI/Application/MCP | unit, integration, schema and golden outputs | complete |
| 3 | Transactional upgrade and rollback | fixture matrix, stale/interruption/security tests | complete |
| 4 | Docs, graph and canonical skill | docs build, graph checks, skill validator | complete |
| 5 | Cross-repository acceptance | full PHPUnit, static verify, upgrade matrix, Skill Sync Gate | complete |

## Progress

### Batch 1

- Status: complete.
- Done: repository baseline recorded; canonical skill preflight completed with
  success and non-blocking release/naming warnings; optional schemas and
  ownership boundaries frozen.
- Verification: action-gate receipt under the canonical skill checkout.

### Batch 2

- Status: complete.
- Done: generated `docara.capabilities.v1`, CLI command and CLI/MCP Application
  service parity; actual commands, parameters and schemas are discovered from
  runtime registries rather than duplicated.
- Verification: capability unit/MCP tests; live contract reports AI contract
  1.1.0, 21 commands and 49 schemas.

### Batch 3

- Status: complete.
- Done: same-major exact upgrade planning, isolated Composer candidate,
  candidate doctor/validation/engine/build/static verification, hash-bound
  apply, compensation and offline rollback; project-local init compatibility.
- Verification: upgrade fixture tests cover apply/rollback, no-update, major
  refusal, stale inputs, tampered plan/build, stale rollback, symbolic links,
  failed promotion and legacy bootstrap guidance.

### Batch 4

- Status: complete.
- Done: public specification, Russian documentation, graph feature/mapping and
  canonical Docara skill contract updated. The divergent dirty `ai-codex`
  checkout was not changed; the existing installed Federation control plane
  remains the physical installer.
- Verification: canonical skill validator and smoke pass; graph sync success.

### Batch 5

- Status: complete.
- Done: schema parse, formatting, deterministic CLI inspection, documentation
  production build/static verification and desktop/mobile browser smoke.
- Verification: full PHPUnit reached 565 tests / 16,557 assertions with two
  golden documentation mismatches and one environment skip; both mismatches
  were corrected and the affected plus new upgrade matrix passed 24/24 with
  1,925 assertions. Documentation built 128 source pages / 263 HTML pages;
  static verifier checked 35,556 local references with zero broken. Federation
  verify completed with no blockers and route-check passed 353/353.

## Final Result

- Result: project-local seamless upgrade, generated AI capabilities and the
  compatible canonical skill contract are implemented and verified.
- Verification: PHPUnit evidence, production/static build, browser smoke,
  Skill Sync Gate and deterministic contract output are recorded above.
- Remaining: no implementation work in the approved scope.
- Follow-up: commit, push, skill release-lock update, Docara package release and
  publication remain separate authorization boundaries.
