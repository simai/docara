# Workflow: Docara Example controls release and ui-doc adoption

Date: 2026-09-07
Status: in-progress (follow-up correction)
Project mode: productization
Size: large
Simplicity policy: human-centered simplicity, full

## Goal

Release the already designed fullscreen and visual-wrap controls as a compatible
Docara update, adopt that exact release in ui-doc, rebuild the complete site,
verify it, and atomically publish the verified bytes to `ui-doc.test`.

## Done When

- Docara exposes fullscreen only for the Result preview and visual wrapping for
  source tabs and ordinary code blocks without changing copied source.
- Mouse activation does not show keyboard focus; keyboard focus remains visible.
- State, labels and icons communicate both enabling and disabling each toggle.
- Package defaults, page/section/site configuration and per-block overrides use
  the existing inheritance and component surfaces.
- Tests, formatting, strict Composer validation, documentation build,
  `verify-static`, deterministic package checks and release audit pass.
- A compatible immutable Docara release is published from a clean revision.
- ui-doc pins that release, completes a full build and static verification, and
  its browser smoke passes on desktop/mobile and light/dark modes.
- The current `ui-doc.test` release remains a named rollback target until the new
  release passes post-cutover smoke.

## Context And Assumptions

- The feature currently exists only as uncommitted work in the main Docara
  checkout and is mixed with unrelated landing-page work.
- The clean existing worktree at release `2.8.1` is the release integration line.
- The public API and CLI remain compatible; the change extends rendering and
  configuration only.
- Standard semantic release versioning is allowed; no version suffix is added to
  runtime identifiers, attributes, schema names or helper names.

## Constraints And Risks

- Do not modify or include the unrelated Docara home/hero/surface/media work.
- Do not modify Federation sources or its in-progress checkout.
- Do not edit generated ui-doc output as source.
- Do not switch `ui-doc.test` before a complete verified build exists.
- Stop on secret exposure, a dirty release candidate, nondeterministic package,
  failed required test, missing release access, or failed post-cutover smoke.

## Ideal Final Result And Reuse Review

The reader gets useful controls automatically, authors can override them only
when needed, and projects need no custom script or styling. Reuse the existing
Example renderer, Markdown renderer, configuration inheritance, shell action
buttons, icon system, local storage synchronization and atomic publication
layout. Add no new component family, service, dependency or background process.

## Batch Plan

| Batch | Goal | Work | Verification | Status |
| --- | --- | --- | --- | --- |
| 1 | Isolate candidate | Extract only fullscreen/wrap code, schemas, copy, docs and tests into the clean worktree | diff audit, targeted tests | complete |
| 2 | Accept Docara | Run full test, docs build, static verification and deterministic package audit | tester verdict | complete |
| 3 | Publish Docara | Commit clean candidate, create immutable release and verify publication | tag/readback/release assets | planned |
| 4 | Adopt in ui-doc | Upgrade exact dependency and rebuild all documentation | full build and verify-static | planned |
| 5 | Publish ui-doc.test | Stage versioned bytes, atomically switch and browser-smoke | rollback/readback/browser evidence | planned |
| 6 | Correct control visibility and real wrapping | Hide fullscreen on source tabs and remove the intrinsic max-content width while wrap is active | focused runtime test and browser geometry | in-progress |
| 7 | Publish correction | Release the compatible patch, upgrade ui-doc, rebuild and atomically publish | release/readback/static/browser evidence | planned |

## Owner Map

- Coordination and release order: teamlead.
- Product contract and documentation build: docara.
- Implementation and repository hygiene: dev.
- Acceptance and browser evidence: tester.
- Versioned staging, rollback and cutover: ops.
- Parallel workstreams and subagents: none.

## Acceptance Corpus

- Default Example, disabled-control overrides and inherited settings.
- Result/source tab transitions, fullscreen enter/exit and wrap on/off.
- Ordinary fenced code block wrap on/off.
- Mouse focus versus keyboard `:focus-visible`.
- State icon and accessible label changes.
- Desktop/mobile, light/dark, console and horizontal overflow.
- Full documentation build, static verifier and deterministic release package.

## Progress

### Batch 1

- Status: complete.
- Done: confirmed `v2.7.5` as the clean baseline and the existing detached
  worktree as the release integration line; isolated the Example/code controls,
  inherited settings, schemas, localized copy, documentation and focused tests.
- Verification: PHP/JSON/JavaScript syntax passed; focused release corpus passed
  with 100 tests and 9,551 assertions; five updated integration contracts passed
  with 897 assertions; Pint passed. Unrelated main-checkout changes remain
  outside this worktree.
- Remaining: repeat the complete suite after the five reviewed contract updates,
  then run build/static/package acceptance.
- Next: complete Batch 2 acceptance for the exact `2.8.0` candidate.

### Batch 2

- Status: complete.
- Evidence: the first complete run executed 582 tests and 19,457 assertions. It
  found only five stale contract expectations caused by the intentional toolbar,
  localization namespace and rendered HTML digest changes; each was reviewed
  against the new semantic output and its focused rerun is green.
- Final evidence: Pint and strict Composer validation passed; the final complete
  suite passed 582 tests with 20,255 assertions and two platform skips; the
  production documentation build produced 128 authored pages; `verify-static`
  verified 263 HTML pages and 35,560 local references with zero broken links.
- Reviewer verdict: implementation, localization, configuration inheritance,
  generated HTML and documentation are release-ready. Deterministic packaging
  remains the first verification after the exact release commit.

## Final Result

- Result: releases `2.8.0` and `2.8.1` plus their ui-doc adoption are complete;
  the user's follow-up exposed one CSS regression and clarified that fullscreen
  belongs only to the Result tab.
- Verification: on the published Alerts page the wrap state changes to active,
  but the source `code` element keeps `min-inline-size:max-content`; its scroll
  width remains 1003px inside an 896px viewport. This is the reproduced cause.
- Remaining: batches 6-7.
- Next: correct the existing CSS/JavaScript contract, prove geometry in a real
  browser, publish a patch release, and rebuild `ui-doc.test` with rollback.
- Follow-up: unrelated Docara landing-page work remains in its existing workflow.

### Batch 6

- Status: implementation complete; release checks in progress.
- Changed surface inventory: one existing fullscreen action, one existing wrap
  state selector, their narrow runtime regression assertions, authoring contract,
  changelog, release index and patch notes. No new component, setting, data
  attribute, dependency or background behavior was added.
- Necessity map: retain fullscreen on Result; hide its entry action on source;
  retain the exit action only as protective recoverability when fullscreen is
  already active; retain wrap and copy on source; remove the inherited
  `max-content` width only while wrap is active.
- Simplest complete alternative: reuse the current controls and state attributes
  with one visibility condition and one CSS width reset. A new toolbar mode,
  JavaScript text transformation or component family is unnecessary.
- Progressive disclosure: wrap appears when a source tab is selected;
  fullscreen appears on Result and, once active, remains discoverable as Exit.
- Complexity delta: zero new visible actions, settings, identifiers or runtime
  branches beyond restoring the prior context condition; one CSS declaration.
- Retained protective complexity: keyboard focus, Escape/native fullscreen exit,
  unchanged copied bytes, shared wrap preference and the existing no-control
  configuration remain intact.
- Verification: focused runtime test passed 3 tests / 59 assertions; Pint and
  strict Composer validation passed; the complete suite passed 582 tests /
  20,260 assertions with two expected platform skips in 51:20.
- Blocking findings: none before release preflight.
- Verdict: implementation PASS; browser geometry remains required on the built
  and published artifact.
