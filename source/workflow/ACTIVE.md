# Active workflow: Docara stable release and ui-doc Framework 5.4 integration

Date: 2026-08-28
Status: `in-progress`
Workflow: `source/workflow/2026-08-28-docara-2-3-release-and-ui-doc-5-4.md`

## Current execution

- the user authorized completion and publication of the already implemented
  common Framework Asset Planner as a stable Docara release;
- after publication, ui-doc will be updated to the stable Docara package and
  published Framework 5.4.0, rebuilt and verified locally;
- Docara and ui-doc receive separate focused commits and pushes;
- public documentation deployment remains forbidden;
- current batch: freeze rollback packages and audit the Docara dirty baseline.

## Previous terminal state

## Current routing

- the shared Framework Asset Planner and Docara pilot are complete;
- the existing Framework Registry and Loader remain the only owners of runtime discovery and dependency data;
- `ui-doc.test` is rebuilt and verified without modifying its content or dependency locks;
- no implementation task remains active;
- an optional CSS-only native page-transition pilot is documented in
  `source/workflow/2026-08-28-docara-native-view-transitions-plan.md`, but it
  remains proposed until an explicit user decision;
- commit, push, tag, release, package publication and public deploy remain
  unauthorized.

## Superseded correction routing

- `docara-stable-shell-loading` and `docara-shell-resource-preload-correction`
  remain historical inputs;
- their Docara-specific preload route is superseded by the common Framework
  Asset Planner completed in the last workflow.

## Latest correction result

- final HTML is analysed by the common Framework planner through the existing
  Loader registry;
- exact content-hashed CSS and ordered JavaScript are ready before first paint,
  with no `utility.full.css` in the optimized build;
- Smart icon and Menu hydration preserve the server-rendered geometry;
- local `ui-doc.test` is refreshed and browser-verified with worst measured CLS
  `0.00134` and no shell layout-shift sources;
- no implementation task remains active; native page transitions are a
  separate visual-navigation proposal, not unfinished loading work.

## Latest completed bounded work

- workflow: `source/workflow/2026-08-27-sf5-automatic-asset-planning.md`;
- evidence: `source/workflow/evidence/2026-08-27-sf5-automatic-asset-planning/verification-summary.json`;
- result: common Framework production planning replaces Docara-specific
  first-paint resource declarations while the dynamic Loader remains intact;
- checkout HEAD at verification: `8ab5bc48c251283c00f1de23ab4f04384a1021a3`;
- publication: not authorized and not performed.
- next action: `explicit_user_decision`;

## Proposed next route

- plan: `source/workflow/2026-08-28-docara-native-view-transitions-plan.md`;
- state: `proposed`, not active;
- design: one CSS-only root transition with reduced-motion and unsupported
  browser fallbacks;
- forbidden expansion: no SPA, router, HTML cache, link interception or
  ui-doc content changes;
- next action: explicit user decision to run or decline the pilot.

## Canonical current markers

- terminal state: `docara_terminal_no_active_implementation`;
- completed goal: `docara.goal.unified`;
- last completed stage: `docara.stage.lfr.local_framework_runtime`;
- last completed batch: `docara.batch.lfr.integrated_retest`;
- next action: `explicit_user_decision`;
- repository revision: `d514c536b8cf379b90a15be8aaf14bcb85b06f7e`;
- product baseline revision: `c5f6140a85435913a9d5f7389bdf34967d4d70f8`;

## Last completed bounded work

- state: `completed`;
- goal: publish verified Docara `2.2.0` and refresh local `ui-doc.test`;
- workflow: `source/workflow/2026-08-26-docara-2-2-release-and-ui-doc-local-update.md`;
- repository revision at start: `9cd1e114b3ae795cf53e849ad7e8756cec7582b9`;
- release revision: `c24cb112bb3f46b82ba1d60391a0d78d5dcf5f9d`;
- release authorized: `true`;
- public deployment authorized: `false`;
- Codex restart authorized: `false`.

## Source of truth and constraints

- Product behavior is owned by current Docara source, schemas and tests.
- The approved contract and stop conditions are recorded in the linked
  workflow.
- The prior Docara 2.1 release and ui-doc update remain completed history.
- Existing dirty `ui-doc` source changes are preserved and excluded from the
  release/update allowlist.
- The package, immutable tag and GitHub Release with three verified project
  assets are public and resolve to the exact release revision.
- There is no active implementation task. Public deployment and Codex restart
  remain unauthorized.
