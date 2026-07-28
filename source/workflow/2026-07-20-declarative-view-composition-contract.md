# Workflow: declarative view composition contract

Date: 2026-07-20
Status: completed
Workflow ID: `2026-07-20-declarative-view-composition-contract`
Process model: `general_delivery`
Current state: `evidence_recorded`
Target state: `evidence_recorded`
Parent track: `docara-consolidation`

## Goal

Develop the current Region Composition Contract into the shared declarative
model `Layout -> Region -> Section -> Block -> Smart`: reusable definitions,
stable call IDs, named slots, safe Simai Framework View Trees, a complete
`ResolvedRenderPlan`, and a registered Blade renderer for complex trusted
presentation leaves.

## Done When

- Layout, Section, Block and View definitions are separately registered,
  schema-validated and immutable during one compile.
- Region configuration contains Section calls with stable IDs and references,
  not copied Section internals.
- Section definitions own named slots, allowed Blocks and default Block calls;
  resolved Block calls can only select registered Blocks and valid slots.
- Block definitions own renderer kind and allowed Smart contracts.
- Layout and Section structure is rendered from a safe View Tree whose element
  tags, attributes and Simai Framework utilities are allowlisted.
- A registered Blade renderer exists for complex trusted presentation leaves;
  author JSON cannot contain PHP, Blade source, arbitrary HTML, callbacks or
  filesystem template paths.
- `ResolvedRenderPlan` fully expands definitions, calls, slots, view trees,
  Smart plans, assets, provenance and diagnostics using stable IDs.
- The Larena adapter carries the same composition semantics without treating
  Docara's generated plan as canonical content.
- Existing declarative pages and the region demonstration render through the
  new model while the accepted legacy publisher remains byte-identical.
- Focused negative/positive tests, full regression, deterministic build,
  static verification, staged local deployment and desktop/mobile browser
  checks pass.

## Scope

Allowed:

- declarative schemas, registered definitions, plan DTOs, compiler and renderer;
- safe View Tree validation/rendering and exact Framework utility allowlist;
- registered product-owned Blade templates and presentation-only view models;
- Larena adapter, tests, documentation, previews and local reversible deploy;
- workflow, evidence and project memory.

Forbidden:

- PHP, Blade source, raw HTML, callbacks, expressions or template paths in
  authored JSON;
- arbitrary HTML tags, event attributes, scripts, styles or unregistered CSS;
- changes to the accepted legacy renderer;
- writes to Larena, Framework, Bitrix or other owner repositories;
- public push, merge, tag, release or production deployment;
- secrets, database or ServBay configuration changes.

## Architecture Decisions

1. Author descriptors store references and stable instance IDs. Reusable
   definitions remain product-owned registered JSON.
2. Layout View Trees place named regions; Section View Trees place named slots.
3. Blocks are resolved into slots before presentation. A Smart block selects a
   registered Smart contract; Markdown remains a typed content block.
4. Safe View Tree nodes are data, not templates. Only a bounded tag vocabulary,
   safe attributes and an exact Framework utility registry are accepted.
5. Blade is a registered renderer for trusted complex leaves only. Template
   paths never cross the author boundary.
6. The resolved plan is generated, explainable and non-canonical. It includes
   every expanded definition/call/binding required to reproduce rendering.
7. The current accepted legacy publisher remains unchanged until a separate
   full-shell migration goal.

## Batches

| Batch | Work | Status |
| --- | --- | --- |
| 0 | Baseline, routing, workflow and requirement matrix | completed |
| 1 | Reusable definitions, stable instances, slots and composition | completed |
| 2 | Safe View Tree and Framework utility contract | completed |
| 3 | Complete ResolvedRenderPlan and registered Blade renderer | completed |
| 4 | Migrate preview/docs and Larena adapter | completed |
| 5 | Focused/full tests, deterministic build and static verification | completed |
| 6 | Staged local deployment and browser acceptance | completed |
| 7 | Reverse completion audit, evidence, memory and closure | completed |

## Verification

- positive nested composition with stable IDs and slot expansion;
- negative duplicate ID, unknown definition/slot/Smart/view and unsafe utility;
- negative PHP, Blade, HTML, callback, event/style/script and template-path
  author surfaces;
- registered Blade allowlist and presentation-only source checks;
- complete plan round-trip and Larena adapter parity;
- accepted legacy renderer digest;
- focused and full PHPUnit;
- two identical production builds and static reference verification;
- desktop and mobile checks on `docara.test`.

## Evidence

Root:
`source/workflow/evidence/2026-07-20-declarative-view-composition-contract/`

Required:

- `baseline.md`;
- `requirement-matrix.md`;
- `verification-summary.md`;
- `deployment.md`;
- `browser-acceptance.md`;
- `acceptance.md`.

## Result

The bounded declarative composition contract is implemented and accepted.
Authored region configuration now contains stable Section calls only. Registered
definitions expand into slots, Blocks, Smart plans and safe Framework View
Trees inside `docara.resolved_render_plan.v2`; trusted complex leaves may use a
registered Blade renderer without exposing executable surfaces to author JSON.

The exact local candidate is published at `https://docara.test/`. The accepted
legacy publisher remains byte-identical and primary; replacing it is explicitly
outside this workflow.

## Residual compatibility note

The supported PHP 8.2 suite passes completely. On PHP 8.4, two inherited Jigsaw
snapshot fixtures produce dates one day earlier while all other tests pass.
This pre-existing cross-version fixture/runtime issue does not affect the
declarative candidate or the published PHP 8.2 build and is not presented as a
PHP 8.4 readiness claim.

## Stop Conditions

- safe composition requires executable author configuration;
- exact Framework utility evidence is unavailable;
- accepted legacy publication changes;
- unrelated overlapping worktree changes appear;
- local deployment lacks backup, staging, rollback or HTTP verification;
- destructive, secret, public release or production action is required.
