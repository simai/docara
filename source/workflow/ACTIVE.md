# Active workflow: Docara 2.2 release and local ui-doc update

Date: 2026-08-26
Status: `active_release_workflow`
Current workflow: `source/workflow/2026-08-26-docara-2-2-release-and-ui-doc-local-update.md`

## Canonical current markers

- terminal state: `docara_terminal_no_active_implementation`;
- completed goal: `docara.goal.unified`;
- last completed stage: `docara.stage.lfr.local_framework_runtime`;
- last completed batch: `docara.batch.lfr.integrated_retest`;
- next action: `explicit_user_decision`;
- repository revision: `d514c536b8cf379b90a15be8aaf14bcb85b06f7e`;
- product baseline revision: `c5f6140a85435913a9d5f7389bdf34967d4d70f8`;

## Active bounded work

- state: `active`;
- goal: publish verified Docara `2.2.0` and refresh local `ui-doc.test`;
- workflow: `source/workflow/2026-08-26-docara-2-2-release-and-ui-doc-local-update.md`;
- repository revision at start: `9cd1e114b3ae795cf53e849ad7e8756cec7582b9`;
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
- Commit, push, tag and package publication are authorized only for Docara
  2.2.0. Public deployment and Codex restart remain unauthorized.
