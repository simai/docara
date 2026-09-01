# Release workflow: universal Framework icon subsets

Date: 2026-09-01
Status: completed
Mode: product release across dependent repositories

## Goal

Publish the already implemented and verified universal icon-subset capability
without mixing unrelated work, then bind Docara and `ui-doc` to the exact
released Framework revision.

## Done When

- `ui-builder` and `ui-loader` source changes are committed and pushed from
  their current `main` branches;
- SIMAI UI is rebuilt only from those exact commits, released from the current
  line as a backward-compatible minor release, and its immutable tag resolves
  to the published commit;
- Docara is regenerated from the exact released UI commit/tree, its tests,
  package verification and AI-contract gate pass, and a compatible release is
  published;
- `ui-doc` pins the exact released Framework and Docara versions, builds and
  verifies, and its update commit is pushed;
- every published ref is reachable, the rollback refs remain available, and
  no unrelated View Transition or local graph projection enters a release
  commit.

## Baseline And Ownership

| Repository | Baseline | Current line | Owner | Release role |
| --- | --- | --- | --- | --- |
| `ui-builder` | `367b3423f9707b850c6bef9476ab8d1ed44039e1` | `main` | SF5 builder | generator and packaging source |
| `ui-loader` | `c94a214fb727f0468863d10a94d4388e0f111852` | `main` | SF5 Loader | dynamic subset and fallback runtime |
| `ui` | `185ca0620df6b46b9e2c9c92231a46c9b79a786b` | `codex/sf5-ui-radius-contract`, identical to `origin/main` at baseline | SF5 distribution | immutable Framework release |
| `docara` | `562d86c33742f79f1d50e3092a9b7ad54731d073` | `main` | Docara | package-owned shell subset and verification |
| `ui-doc` | `b23c3e4963032c7e4849b382a7ed3d1bcd34b954` | `main` | UI documentation | exact consumer lock update |

Integration owner: current root task. QA verdict owner: tester. Runtime,
access, rollback and publication owner: ops.

## Human Outcome And Simplicity

The developer keeps the same Framework markup and Loader behavior. Production
builds automatically ship only the initial icon subset; late icons retain an
offline exact-version fallback. No new project setting, second registry,
mandatory service, background process or parallel cache is introduced.

Reuse path: extend the existing builder, Loader, UI distribution, Docara Asset
Planner, build receipt and verifier. The simpler complete alternative of a
Docara-only subset was rejected because it would duplicate the mechanism for
every Framework project. Hash binding, local fallback, rollback and exact
release provenance are retained protective complexity.

## Scope

Allowed:

- the exact changed files already recorded by
  `source/workflow/2026-08-31-framework-icon-subsets.md`;
- release versions, changelogs, release notes, release request/manifest and
  generated artifacts produced from exact committed inputs;
- the `ui-doc` Composer/Framework locks required to consume the releases;
- this workflow, its evidence and the active-workflow pointer.

Explicitly excluded:

- `ui/graph.json` and `ui/graph/**`;
- Docara native View Transition specs and workflow;
- unrelated worktrees, branches, cleanup or history rewriting;
- switching the live `icons.simai.io` service;
- public deployment of the documentation site.

## Release Order

| Batch | Work | Verification | Status |
| --- | --- | --- | --- |
| 1 | Freeze inventory, gates, rollback refs and release numbers | action gates, access check, diff ownership | completed |
| 2 | Commit/push `ui-builder` | repository checks, icon and reproducibility suites | completed |
| 3 | Commit/push `ui-loader` | ESLint and runtime unit tests | completed |
| 4 | Rebuild/release `ui` from exact source commits | two-wave build comparison, distribution audit, tag/release checks | completed |
| 5 | Reproject, test, package and release Docara | PHPUnit, Composer/package/AI gates, two clean packages, docs build | completed |
| 6 | Update and push `ui-doc` | dependency lock, documentation tracking, build, verify-static, browser smoke | completed |
| 7 | Verify public refs and close evidence | remote commit/tag/release/package checks, rollback proof | completed |

## Rollback And Stop Conditions

Rollback refs are the five baseline commits above plus immutable tags
`v5.4.1` and `v2.5.0`. No force push, tag replacement or remote deletion is
allowed. A failed downstream batch leaves already published immutable upstream
releases intact and restores the consumer to its previous exact locks.

Stop on:

- unexplained changed or untracked file entering a candidate;
- non-fast-forward remote state or an existing conflicting tag/release;
- failed deterministic build, package comparison, static verification or AI
  contract gate;
- missing GitHub/package access that cannot be resolved through Access Center;
- any requirement to deploy the public site or switch the live icon service.

## Evidence

- implementation evidence:
  `source/workflow/evidence/2026-08-31-framework-icon-subsets/verification-summary.json`;
- release evidence:
  `source/workflow/evidence/2026-09-01-framework-icon-subsets-release/`.

## Current State

Preflight repository hygiene and action gates pass in all five repositories.
Naming checks report only existing Federation graph schema v2 warnings and no
blocker. `ui-builder` is published at `96b56d2a4e5b`, `ui-loader` at
`a1f523bf43aa`, and the deterministic Framework distribution is published as
immutable `v5.5.0` at `286e48b8ce2b`.

Docara 2.6.0 is published from immutable tag `v2.6.0` at
`43db3aaa3cd8f9f335b2bd15896ac012c3719969`. Two independent clean checkouts
produced the same verified release ZIP, and the GitHub asset is byte-identical
to that candidate. The AI contract did not change and remains compatible with
the exact Docara skill revision pinned by Federation 8.6.0.

`ui-doc` is published at
`c3e1717e4f448eb22176fb8f815f28350ee058a5`, pins Framework 5.5.0 and Docara
2.6.0, and reports all 63 accepted component documentation entities current.
Its post-commit production build, static verification, browser smoke and remote
Quality workflow are green. The browser loaded the 244,368-byte initial subset,
all visible icons resolved, and neither the full outlined fallback nor
`icons.simai.io` was requested. The excluded View Transition and local graph
files remain outside every release commit; no public documentation deployment
or live icon-service switch was performed.
