# Workflow: Docara project technology mode

Date: 2026-08-09
Status: completed
Mode: project governance / Mirai Graph Hybrid SOT
Process model: `project_technology_management`
Track: `docara-project-technology`
Final outcome: `docara_project_technology_ready`
Owner: `graph`
Coordinator: `teamlead`

## Current Goal

Enable the unified project technology mode for Docara so each meaningful task
can start from and finish with a fresh, evidence-backed project context built
from the repository, durable workflow, project memory, handoffs and canonical
Mirai Graph.

## Done When

- root `graph.json` v2 is the single canonical manifest;
- the existing `graph/` knowledge model remains connected as canonical project
  data rather than becoming a competing manifest;
- repository, `docs/` and private `source/` inputs are inventoried without
  copying private source content into the graph;
- project technology status, continuation context and receipt are current;
- project memory records the enabled mode and task-boundary sync policy;
- verification and repeated synchronization prove the mode is valid and
  idempotent;
- product/runtime source remains unchanged and no Git publication or release
  action occurs.

## Source Boundaries

- canonical manifest: `graph.json`;
- canonical project graph data: `graph/specs/`, `graph/schemas/` and related
  project graph sources;
- authoritative raw meaning: repository code, `README.md`, `docs/` and
  `source/`;
- derived local runtime: `.simai/project-technology/`;
- generated graph context remains derived and grants no write, approval,
  release or deploy authority.

## Stages

- [x] `project_technology_inspect`: inspect the repository, current graph,
   workflow, memory, handoff and safety boundaries.
- [x] `project_technology_plan_or_apply`: preview the transactional enablement and
   confirm the exact write surfaces.
- [x] `project_technology_synchronize`: enable and synchronize the unified mode.
- [x] `project_technology_capture_continuation`: bind the current terminal Docara
   state and durable sources into a compact continuation context.
- [x] `project_technology_verify`: verify manifest, inventory, freshness,
   idempotency and product/runtime zero diff.

## Batches

- [x] Inventory repository knowledge, graph state and durable `source/` routers.
- [x] Enable and repair the unified root Mirai Graph manifest.
- [x] Synchronize project memory and continuation context without accepting
  false release routing.
- [x] Verify graph, technology freshness, idempotency and outcome integrity.

## Track Linkage

This bounded governance track delivers `docara_project_technology_ready` while
preserving the terminal product state. It does not open an implementation or
release track.

## Evidence Plan

- project technology `enable`, `sync`, `status` and `verify` results;
- `.simai/project-technology/receipt.json` and bounded context metadata;
- root manifest validation;
- project-memory and graph semantic checks;
- final allowlist and product/runtime zero-diff checks.

## Safety And Stop Conditions

- do not enable or use the disabled legacy Docara skill;
- do not create a second graph, skill, daemon or background loop;
- do not copy complete `source/` content into graph/runtime context;
- stop on an unexpected product/runtime write, secret exposure, release/deploy
  transition or loss of the existing dirty worktree state;
- no commit, push, tag, release or deploy without separate authorization.

## Current State

- inspection: completed;
- transactional preview: completed;
- action gate: warning-only due stale owner routing and two existing schema-name
  warnings; no write blocker was reported;
- project technology enablement: completed;
- canonical identity repair: completed (`docara.unified`);
- false `release` track created by keyword inference: removed; terminal project
  memory restored;
- graph verification: completed;
- continuation capture and idempotency verification: completed;
- next action: none inside this workflow.

## Verification

- root `graph.json` schema/manifest verification: PASS;
- root/inner graph identity: PASS; the inner v1 index is consistent and
  read-only under the root v2 manifest;
- graph JSON and project-memory YAML parsing: PASS;
- `php scripts/project-context.php generate` and `check`: PASS, `issues=[]`;
- private source content copied into project-technology context: false;
- technology status/freshness/continuation: ready/current/ready;
- repeated task-boundary synchronization: PASS, `zero_diff=true`;
- project-memory reindex: PASS;
- working product/runtime diff: zero;
- product baseline to repository HEAD product/runtime diff: zero;
- Git publication, release and deploy actions: none.

Evidence:

- `.simai/project-technology/receipt.json`;
- `source/workflow/evidence/2026-08-09-docara-project-technology-mode/verification-summary.json`;
- `source/workflow/evidence/2026-08-09-docara-project-technology-mode/outcome-integrity-review.json`.

## Result

Docara now uses one unified project technology mode. Significant tasks must
load task-relevant graph context and raw owner sources at start, then update the
workflow, graph, checks and project memory and perform a fresh project-
technology sync at completion. This is a task-boundary process, not a daemon or
background loop.
