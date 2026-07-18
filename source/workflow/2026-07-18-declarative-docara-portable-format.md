# Workflow: Portable declarative Docara format

Date: 2026-07-18
Status: in-progress
Process model: `general_delivery`
Current state: `implementation_written`
Target state: `implementation_written`

## Track

Track ID: `docara-portable-format`

Establish one portable Docara project format that can be built by standalone
Docara and imported by Larena Docara without creating a second Markdown or
layout language.

## Final Outcome

One backward-compatible, portable and explainable Docara content/layout
contract is shared by standalone Docara and Larena Docara, while standalone
Docara remains a small static-site generator rather than becoming Larena.

## Current Position

The standalone implementation and its browser evidence are complete in the
clean worktree. The same fixture is being validated by the Larena read-only
import adapter; independent exact-candidate acceptance remains open. No
production, release, archive, or repository-retirement action is in scope.

## Current Goal

In a clean worktree based on accepted `simai/docara` `v1.3.65`, implement a
backward-compatible prototype with JSON site/section/page descriptors,
explainable inheritance, documentation and landing presets, typed Markdown
calls to real Simai Framework components, an immutable asset runtime lock, a
reproducible fixture, and a Larena import proof.

## Done When

- `docara.json`, inherited `_section.json`, and optional page sidecars are
  schema-validated and resolve deterministically.
- Schemas exist for site, section, page, component call, and framework lock.
- `ResolvedPagePlan` exposes the resolved values plus source/provenance trace.
- `docs` and `landing` presets work in the same generator.
- Markdown calls for `ui.alert` and `ui.button` validate against real Framework
  manifests and render useful static HTML.
- Framework assets are resolved from an exact lock without `main` or `latest`.
- Existing `.settings.php` projects continue to build.
- A nested fixture covers documentation and landing pages.
- Clean install, negative schema, determinism, and compatibility tests pass.
- The same fixture is accepted by a Larena Docara import adapter/proof.
- Desktop/mobile and light/dark browser smoke evidence is recorded.
- Independent tester acceptance covers every item above.

## First Batch

Create the clean repository contract: workflow, launch record, architecture
surface map, schemas, resolver boundary, and focused tests before rendering or
Larena integration expands.

## Stages

- Establish the portable configuration and resolution contract.
- Render the two presets and typed Framework component calls.
- Prove compatibility, clean installation, and deterministic output.
- Import the same fixture into Larena and complete browser/independent
  acceptance.

## Batches

- Batch 0: prepare the exact clean base, workflow, launch record, and gates.
- Batch 1: implement JSON schemas and descriptor validation.
- Batch 2: implement inheritance and explainable `ResolvedPagePlan`.
- Batch 3: implement `docs`/`landing` presets and the portable fixture.
- Batch 4: bind `ui.alert`/`ui.button` calls to real manifests and pinned assets.
- Batch 5: prove legacy `.settings.php` compatibility and clean installation.
- Batch 6: prove Larena import of the same fixture.
- Batch 7: collect responsive/theme browser evidence and independent verdict.

## Completion Gate

Do not complete the goal until the requirement matrix in this workflow has no
`missing` or `partial` rows and the independent tester returns a goal-level
`PASS` for the exact candidate revision.

## Current Remaining

Standalone Batches 1-5 are implemented and verified. The browser portion of
Batch 7 is complete. Larena parity and independent acceptance remain open.

## Do Not Complete Until

- standalone Docara and Larena consume the same portable fixture;
- legacy `.settings.php` regression is green;
- the generated asset plan contains no moving Framework reference;
- browser evidence covers both presets, both themes, and both viewports;
- no required verification is represented only by an unexecuted checklist.

## Context And Assumptions

- Accepted base: `v1.3.65` / `ba7724ae3d9e2b99388098637b81a35a2646e6a4`.
- Worktree: `/Users/rim/Documents/GitHub/docara-worktree-declarative`.
- Branch: `codex/declarative-site-contract`.
- Standalone Docara owns the portable file format. Larena adds persistence,
  admin, access, audit, revisions, and workflow through an adapter.
- JSON is the canonical portable configuration format for this prototype.
- Normal Markdown remains semantic HTML; Smart-components are used only for
  typed parameterized elements.

## Owners

| Surface | Owner | Role |
| --- | --- | --- |
| Delivery and repository | `teamlead` + `dev` | coordinator / implementation |
| Standalone generator | `docara` | domain owner |
| Framework manifests/assets | `sf5` | contract owner |
| Larena import adapter | `larena` | platform owner |
| Acceptance and evidence | `tester` | gatekeeper |
| Runtime/browser safety | `ops` | consulted / runtime gate |

## Allowed Scope

- Files in the clean Docara worktree.
- Focused files under `larena-workspace/packages/docara` required for the import
  proof and its tests.
- Disposable install/build/browser fixtures under `/private/tmp`.
- Read-only inspection of Framework/UI repositories.

## Forbidden Scope

- Production deploy, package publication, tag, merge, push, or release.
- Archiving or deleting `docara-template` or `docara-mix`.
- Editing the dirty `codex/docara-ecosystem-recovery` worktree.
- Moving or rewriting Git history.
- Adding a second canonical Framework/component registry.
- Using `main`, `latest`, or an unpinned CDN reference in portable runtime data.
- Committing secrets, local credentials, generated vendor trees, or broad raw
  `source/` material.

## Batch Plan

| Batch | Result | Verification | Status |
| --- | --- | --- | --- |
| 0 | Clean base, workflow, launch record, gate evidence | exact SHA, clean status, process/gate checks | completed |
| 1 | JSON schemas and descriptor loader | positive/negative schema tests | completed |
| 2 | Inheritance resolver and `ResolvedPagePlan` trace | precedence, reset, determinism tests | completed |
| 3 | `docs`/`landing` presets and portable fixture | focused build and HTML assertions | completed |
| 4 | `ui.alert`/`ui.button` Markdown calls and asset lock | manifest/props/asset tests, no moving refs | completed |
| 5 | Legacy `.settings.php` compatibility and clean install | existing suite plus disposable install | completed |
| 6 | Larena import adapter/proof for the same fixture | package tests and normalized-plan parity | in-progress |
| 7 | Browser and reverse acceptance | desktop/mobile, light/dark, independent verdict | in-progress |

## Requirement Matrix

| Requirement | Authoritative evidence | Status |
| --- | --- | --- |
| Site/section/page/component/framework schemas | schema files + schema tests | pass |
| Explainable inheritance | resolver tests + plan trace fixture | pass |
| Docs and landing presets | build artifacts + browser evidence | pass |
| Real Framework alert/button calls | manifest sources + rendered output tests | pass |
| Immutable asset runtime | lock fixture + moving-reference rejection | pass |
| Legacy compatibility | existing `.settings.php` regression suite | pass |
| Clean install | disposable install log and generated site | pass |
| Negative validation | invalid fixture matrix | pass |
| Determinism | repeated plan/build hash comparison | pass |
| Larena import proof | package adapter tests using same fixture | missing |
| Responsive/theme smoke | screenshots/assertions for 4 context pairs | pass |
| Independent acceptance | exact-candidate tester verdict | missing |

## Gates And Stop Conditions

- Run repository and secret/source gates before writes and before commit.
- Stop if the accepted base changes or the clean worktree becomes contaminated
  by unrelated changes.
- Stop before any destructive cleanup, publication, release, or production
  action.
- A failing test opens a correction batch; it does not lower acceptance scope.
- Missing real Framework manifests is a contract blocker, not permission to
  invent local substitutes.

## Evidence

Store concise evidence under:

`source/workflow/evidence/2026-07-18-declarative-docara-portable-format/`

Record commands, exact revisions, exit codes, changed paths, semantic result,
and limitations. Generated dependency trees and browser caches remain outside
the repository.

## Evidence Plan

- Store exact command, revision, exit code, semantic outcome, and limitations
  for each batch under the evidence path above.
- Keep generated dependencies, browser profiles, and disposable builds outside
  the repository.
- Bind final independent acceptance to one exact candidate revision and every
  row of the requirement matrix.

## Personal Memory Context

Personal memory decision: `skip`
Personal memory reason: current repository sources, accepted tag, Framework
manifests, Larena package code, executable fixtures, and fresh test evidence are
the authoritative inputs for this implementation; no unverified personal-memory
fact is required.

## Progress

### Batch 0

- Status: completed
- Done:
  - confirmed `v1.3.65` exact revision;
  - created clean worktree and dedicated branch;
  - preflight action gates passed for reversible local work;
  - corrected the inherited ignore contract and passed project doctor;
  - validated the workflow and launch record without blockers.
- Verification:
  - `git rev-parse v1.3.65^{commit}` ->
    `ba7724ae3d9e2b99388098637b81a35a2646e6a4`;
  - worktree started clean at the exact tag;
  - project doctor -> `success`;
  - process check for `repository_prepared -> ready_to_code` -> `success`;
  - action-gate report ->
    `source/output/action-gates/action-gate-report-20260717220820.json`.
- Remaining: none for Batch 0.
- Next: begin Batch 1 with schema and descriptor-loader tests.

### Batch 1

- Status: completed
- Done:
  - added strict site, section, page, component-call, and framework-lock schemas;
  - added schema repository/validator and fail-closed descriptor loader;
  - covered positive and negative descriptor cases.
- Verification: included in the focused 51-test / 328-assertion suite.
- Remaining: none.

### Batches 2-5

- Status: completed
- Done:
  - deterministic object merge, list replacement, explicit `$reset`, ordered
    provenance and canonical `ResolvedPagePlan` hashes;
  - `docs` and `landing` presets from one portable fixture;
  - typed `ui.alert` and `ui.button` directives through real manifests;
  - exact Core revision plus locally verified Smart asset projection, with no
    `main`, `master`, or `latest` fallback;
  - `docara init --portable`, clean empty-directory build, deterministic repeat
    build, and legacy `.settings.php` compatibility.
- Verification:
  - focused portable suite: 51 tests / 328 assertions, PASS;
  - complete repository suite: 322 tests / 996 assertions, PASS;
  - Pint, Composer validation, JSON parsing, and `git diff --check`, PASS.
- Remaining: none.

### Batch 6

- Status: in-progress
- Done: Larena adapter implementation and focused tests are prepared against
  the shared fixture.
- Remaining: bind the adapter evidence to the exact standalone candidate,
  confirm cross-repository hash/plan parity, and commit the Larena candidate.
- Next: complete the Larena adapter after the standalone SHA is available.

### Batch 7

- Status: in-progress
- Done:
  - real browser smoke for documentation, nested documentation, and landing;
  - desktop/mobile and light/dark screenshots;
  - real `sf-alert`, `sf-icon`, and `sf-button` hydration;
  - exact local Smart requests, zero console errors/warnings, no horizontal
    overflow, working theme persistence, and visible Material Symbols glyph.
- Remaining: independent tester verdict against the exact standalone and
  Larena candidate revisions.
- Next: prepare the immutable tester assignment after both commits exist.

## Kaizen

- The visual review caught a glyph rendered as the text `info` even though DOM
  dimensions and custom-element checks passed. Acceptance now requires both DOM
  assertions and screenshot inspection for component semantics.
- Long legacy PHPUnit runs must be serialized because tests share
  `tests/fixtures/tmp`; concurrent runs can delete each other's fixtures.
- Pre-commit reverse review found and closed three boundary defects before the
  candidate commit: partial legacy/portable mixing, lexical root-symlink
  normalization, and empty-object type loss in published diagnostics.

## Final Result

- Result: standalone candidate implementation ready; cross-repository and
  independent acceptance remain open.
- Verification: standalone implementation and browser evidence pass.
- Remaining: Larena parity candidate and exact-candidate independent verdict.
- Follow-up: none outside the goal yet.
