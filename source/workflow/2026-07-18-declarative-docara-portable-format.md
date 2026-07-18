# Workflow: Portable declarative Docara format

Date: 2026-07-18
Status: in-progress
Process model: `general_delivery`
Current state: `tests_recorded`
Target state: `tests_recorded`

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

The standalone implementation and accessible browser shell are complete in a
clean worktree. Human-Centered Simplicity pre-acceptance found and removed a
second no-op component surface and nonfunctional presentation settings. The
corrected standalone contract is tested; the Larena read-only adapter and its
exact fixture are being rebound to that corrected export. No production,
release, archive, or repository-retirement action is in scope.

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

Standalone Batches 1-5 are corrected and verified. Larena Batch 6 is reopened
only to consume the corrected normalized call shape and presentation contract.
Independent exact-candidate acceptance remains open.

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
| Larena import proof | exact corrected build-export fixture + package adapter tests | partial |
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

## Human-Centered Simplicity

- quality_controls: `[human_centered_simplicity]`
- simplicity_review: `source/workflow/evidence/2026-07-18-declarative-docara-portable-format/hcs/human-centered-simplicity-review.json`
- simplicity_repository_refs: `[repo://docara-worktree-declarative, repo://larena-docara]`
- simplicity_repository_baselines: `[repo://docara-worktree-declarative@ba7724ae3d9e2b99388098637b81a35a2646e6a4, repo://larena-docara@8437fdae95fbfe024135fb1c10f8361ebc0e3422]`

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
  - complete repository suite after strict presentation-contract correction:
    323 tests / 1,033 assertions, PASS with PHP 8.4 and UTC;
  - Pint, Composer validation, JSON parsing, and `git diff --check`, PASS.
- Remaining: none.

### HCS correction batch

- Status: standalone completed; Larena rebind in progress.
- Removed from site/section/page descriptors because they were accepted but not
  rendered: JSON `components`, `variables`, top-level `theme`, sidebar
  position, navigation enabled/expanded/order and table-of-contents settings.
- The only presentation keys in v1 are now strict, typed and shared through one
  schema: `layout.max_width`, `settings.theme`, and `navigation.hidden`.
- Markdown remains the single Smart-component authoring surface. Each parsed
  call now becomes a schema-validated `docara.component_call.v1` projection
  with `id` and normalized `props` before runtime metadata is attached.
- The Larena import documentation now describes the implemented canonical
  export boundary instead of a second raw-tree parser.
- Verification: focused 43 tests / 283 assertions and full 323 tests / 1,033
  assertions, PASS.
- Remaining: regenerate the exact export from the correction commit and bind
  the Larena adapter/fixture to it.

### Batch 6

- Status: in-progress after HCS correction
- Done:
  - standalone remains the only owner of raw JSON/Markdown schemas,
    inheritance, directive parsing and `ResolvedPagePlan` construction;
  - Larena consumes only `.docara/resolved-page-plans.json` with a required
    immutable SHA-256 receipt;
  - canonical plan hashes, source trace, exact Framework lock, manifests,
    component calls and placeholders are checked fail-closed;
  - component props are rendered again through Larena `SmartRegistry` and
    `SmartManager` without trusting exported HTML;
  - source-path-derived page identity is stable across content revisions and
    collision-safe;
  - valid `@defaults` provenance and literal `DOCARA_COMPONENT_...` prose are
    accepted without weakening declared-placeholder checks.
- Previous verification (superseded fixture, retained as diagnostic history):
  - Larena package candidate:
    `771f7dd81a9653a7bc3146ef9b1451976a2e70c5`;
  - exact export: 97,114 bytes, SHA-256
    `ed3c3f7f83ce39e24fe502d8c6f3cf1607b784d0bbdf59c25c826f18032679af`;
  - exact regeneration is byte-for-byte equal to the Larena fixture;
  - focused package suite: 23 tests / 144 assertions, PASS;
  - full package gate: 70 tests / 874 assertions, PHPStan, lint, evidence and
    scope checks, PASS.
- Remaining: accept the corrected call schema and theme path, regenerate the
  exact fixture, rerun the package gate, and freeze a new Larena candidate.

### Batch 7

- Status: in-progress
- Done:
  - real browser smoke for documentation, nested documentation, and landing;
  - desktop/mobile and light/dark screenshots;
  - real `sf-alert`, `sf-icon`, and `sf-button` hydration;
  - exact local Smart requests, zero console errors/warnings, no horizontal
    overflow, working theme persistence, and visible Material Symbols glyph;
  - skip link, `aria-current`, no positive `tabindex`, visible focus for native
    and Smart buttons, and distinct system/light/dark accessible theme names;
  - keyboard preflight proves skip-link focus transfer and Enter/Space theme
    activation while focus remains on the toggle.
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
- HCS preflight caught that an outline on `sf-button` itself is invisible when
  the hydrated custom element uses `display: contents`; the shell now targets
  the real light-DOM button with `:focus-visible`.
- Removing local-check ignore patterns looked like harmless cleanup but failed
  the repository-hygiene gate. They were restored as internal protective
  complexity and remain outside the product/authoring surface.

## Final Result

- Result: the corrected standalone contract is ready; the previous Larena
  fixture is intentionally superseded.
- Verification: standalone full regression and strict/no-op negative coverage
  pass.
- Remaining: corrected Larena fixture/candidate, fresh browser evidence, then
  exact-candidate independent Tester and HCS verdicts.
- Follow-up: none outside the goal yet.
