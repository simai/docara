# Workflow: Docara stable release and ui-doc Framework 5.4 integration

Date: 2026-08-28
Status: in-progress

## Goal

Finish the already implemented generic Framework Asset Planner in Docara,
publish it as the next stable backward-compatible Docara release, update
`ui-doc` to that stable package and the published SIMAI Framework 5.4.0
runtime, rebuild and verify `ui-doc.test`, then create separate focused commits
and pushes for Docara and ui-doc.

Public deployment of the documentation site is excluded.

## Done When

- the current Docara dirty baseline is classified and contains only work tied
  to the completed generic asset-planning/loading goal and its required docs,
  tests, runtime projection and release records;
- proposed native page transitions remain outside the release unless already
  required by the accepted asset-planning contract;
- Docara tests, documentation build, static verification and browser smoke are
  green from one immutable release candidate;
- a new stable Docara version is committed, pushed, tagged and published with
  verified release notes and package artifacts;
- ui-doc uses the published Docara version and the published Framework 5.4.0
  tuple, with obsolete `candidate` wording and 5.3.2 Start instructions fixed;
- the existing Russian content and example work is preserved, verified and
  committed in focused ui-doc commits;
- `ui-doc.test` serves the exact verified output after an atomic local refresh;
- no public site deployment, secret exposure, force push or history rewrite is
  performed.

## Baselines

- Docara repository: `/Users/rim/Documents/GitHub/docara`, branch `main`, base
  revision `8ab5bc48c251283c00f1de23ab4f04384a1021a3`, dirty user-owned worktree.
- ui-doc repository: `/Users/rim/Documents/GitHub/ui-doc`, branch `main`, base
  revision `de9b5619d0a4ee91e692eb47d7c97fd6d5baee3f`, two commits ahead of
  `origin/main`, dirty user-owned worktree.
- Current stable Docara: `2.2.0`.
- Target Framework release: `5.4.0`.
- Local site binding: `/Users/rim/Sites/ui-doc.test` -> ui-doc
  `build_production`.

## Authorization And Boundaries

- User authorization: explicit `делай` after the proposed release, update,
  verification, commit and push sequence.
- Authorized: repository edits inside the two named repositories, local test
  builds, temporary rollback packages, atomic refresh of `ui-doc.test`,
  commit/push, Docara tag/package/GitHub Release publication.
- Forbidden: public documentation deploy, force push, history rewrite,
  destructive cleanup, branch/worktree creation, secret output, edits to
  unrelated repositories, translation remediation.

## Rollback And Stop Conditions

- Before edits, save binary Git patches and archived untracked-file manifests
  for both worktrees under a fresh `/tmp` directory.
- Before replacing `build_production`, keep only a temporary rollback under
  `/tmp`; remove it after green HTTP and browser smoke.
- Git rollback uses the recorded base revisions, focused release commits and
  the temporary dirty-state packages. No reset/checkout cleanup is permitted.
- Stop on unexplained dirty-surface ownership, failing release tests, missing
  immutable Framework 5.4 contract, secret/access ambiguity, non-fast-forward
  push, package/tag collision or a requirement for public deploy.

## Batch Plan

| Batch | Work | Verification | Status |
| --- | --- | --- | --- |
| 1 | Freeze baselines, classify Docara diff and exclude proposal-only work | inventories, workflow/process and action-gate evidence | in-progress |
| 2 | Complete Docara release candidate | unit/integration tests, docs build, verify-static, browser smoke, diff review | pending |
| 3 | Publish stable Docara | release audit, commit, push, tag, package/GitHub Release checks | pending |
| 4 | Update ui-doc to stable Docara and Framework 5.4.0 | lock/package checks, content/version audit, project validation | pending |
| 5 | Build and locally publish ui-doc.test | full build, verify-static, HTTP/browser matrix, temporary rollback cleanup | pending |
| 6 | Commit and push ui-doc | focused commit inventory, secret/hygiene checks, fast-forward push | pending |

## Smoke Plan

- Docara package tests and its own documentation routes.
- ui-doc Russian home, Start, Fundamentals, component reference, utility page
  with badges/assets and a JavaScript example.
- Desktop/mobile, light/dark, navigation stability, example tabs and console.
- Confirm no public host or deployment target is changed.

## Current Status

- Route selected Docara with docs/tester/ux/dev companions and release process.
- Release action-gate preflight recorded at
  `source/output/action-gates/action-gate-report-20260828184754.json`.
- Next: create temporary rollback packages and complete the Docara diff audit.
