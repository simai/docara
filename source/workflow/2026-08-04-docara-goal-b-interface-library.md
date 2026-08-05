# Goal B — Full Interface Library & Useful Extension Demos

Date: 2026-08-04
Status: `correction_in_progress`
Current stage: `docara.stage.b.interface_library`
Current batch: `docara.batch.b.interface_library`
Current next action: `complete_goal_b_useful_demo_semantic_correction`
Next roadmap goal: `docara.goal.b.interface_library` (`correction_in_progress`, authorized=`true`)

## Track and entry baseline

Parent track:
`source/workflow/2026-08-04-docara-content-design-settings-track.md`.

Goal A was independently accepted on frozen product/runtime candidate
`8c04160ab50549b060fb933cf80f86193cd92113`. Its corrected exact-candidate
tree digest is
`8b7fdb611647e545c6dabe11ed9e31a43a655f36e87739be5fc44dddd6ca25f2`.
Goal B entry governance HEAD is
`3280a89cc21f2b4fcfc8e7539c673ca62a199446`; the tracked worktree was clean.

Accepted pipeline:

```text
Markdown -> typed Document IR -> NodeRendererRegistry
         -> SmartComponentGateway -> LayoutComposer -> PageBuilderResult
```

No batch may add a second parser, renderer, DesignRegistry, Smart gateway,
LayoutComposer, PageBuilder or preview engine.

## Goal

Build one deterministic registered interface library over the accepted
Design, Smart and Binding registries; migrate replaceable publisher chrome to
that production path; add useful project-owned content/shell demonstrations;
and consume only independently accepted exact-pinned Framework artifacts.

## Done When

The complete Done When is Goal B section 16.4 in the parent track. Completion
requires deterministic registry-derived Atlas, typed child contracts,
registered replaceable chrome, useful data-only project demos, exact accepted
Framework consumption, public/default parity, browser/accessibility/security
evidence and one exact independent-audit candidate.

## Owner and gates

- delivery/sequence: `teamlead`;
- implementation and repository integrity: `dev`;
- independent acceptance: external reverse-outcome auditor;
- canonical graph: repository graph contract;
- external Framework artifacts: Framework owner plus independent cross-host
  acceptance before Docara labels them supported.

Federation preflight completed with `status=success` and no hard blockers. The
central route still selected the disabled historical Docara skill for one gate
projection; repository routing therefore continues through the recorded
`teamlead -> graph -> dev` fallback and repository sources of truth.

## Batches

### B0 — Design Atlas contract

Status: `pass`.

- freeze `docara.design_atlas.v1`, support vocabulary and deterministic order;
- project Layout/View/Section/Block, Smart, binding and preset descriptors from
  accepted registries only;
- keep `owner` and `authoring_kind` independent;
- require every container to expose allowed children/slots/count/order/depth;
- expose one application-service result through CLI JSON/human and optional
  MCP;
- add schema, freshness, source-boundary and negative tests.

Evidence:
`source/workflow/evidence/2026-08-04-docara-goal-b-interface-library/B0-DESIGN-ATLAS.md`.

### B1 — Replaceable chrome migration

Status: `pass`.

- migrate search, breadcrumbs and pager to registered `docara.*` Smart leaves;
- reuse navigation, TOC and preferences for desktop/mobile shell;
- keep outer page/head application-owned;
- remove old trusted leaves only after parity, zero-reference and rollback.

### B2 — Interface variants and presets

Status: `pass`.

- package coherent docs/site/landing presets;
- preserve the three accepted navigation presentations;
- preview branding/sidebar/TOC/search/preferences/footer combinations;
- preserve byte-compatible default output.

### B3 — Project demos

Status: `pass`.

- add data-only `project.install_builder` content demo;
- add data-only `project.product_configurator` site demo;
- complete the project footer shell fixture;
- forbid backend/network/order/payment/command side effects.

### B4 — Framework useful-component consumption

Status: `pass`.

The independently accepted immutable owner packet now supplies exact
`ui.input`, `ui.dropdown` and `ui.checkbox` manifests, views, presets,
templates, asset/hydration hashes and cross-host proof. Its exact product
candidate is `7e0b87187ceb1f89fad730094bcc4aada3e4f3f2` and packet content SHA-256 is
`83551f972ad0b1a6e2037f61583769e32a4a78081e01ed0a0fe888b1187baca1`.

The external gate was closed by the independently accepted immutable
`ui.list-item` owner candidate
`639d7b67833cfdf1e2c349c5f83669ba0e34fe05`, packet content SHA-256
`7dbcb161e8bb48c342a385c3f28f7dc8628eecdf0c09758ab3113eb8dc2107db`.
Its scope is limited to a `type=text` direct child of `ui.dropdown`; icons,
avatars, tags and standalone form-control support remain explicit nonclaims.

The unchanged form-wave and list-item artifacts are now admitted by one
content-addressed Framework lock and the existing `FrameworkLockSmartProvider`
path. A populated project configurator resolves `ui.dropdown -> ui.list-item`
recursively through the same Gateway and renderer; install builder/input and
checkbox scenarios use the same path. Runtime assets are exact-hash checked
before render and usage-published, so unused form assets do not alter the
default site tree. Focused implementation evidence is recorded in
`B4-RESUMED-IMPLEMENTATION.md`.

### B5 — Integration and audit handoff

Status: `ready_for_independent_audit`.

The complete focused/full/package/consumer/full-single/static/browser/a11y
matrix is green on exact product candidate
`e06ff0c945dafd4e9678794773d8bde83c8de535`. Default output remains
usage-driven and unchanged, while the admitted project demonstrations exercise
the intentional Framework surfaces. Graph/specs/handoff are synchronized and
the executor stops at `goal_b_ready_for_independent_audit`.

The pre-unblock independent safe matrix is recorded in
`source/workflow/evidence/2026-08-04-docara-goal-b-interface-library/B5-INTEGRATED-ACCEPTANCE.md`.
Full/full/single, static, package, fresh-consumer and representative browser
checks are green on exact candidate `ccb076a89535954022ca89eb70b84d6c81d80de3`.
That matrix is historical baseline evidence only. Fresh final evidence is
recorded in `B5-FINAL-INTEGRATED-ACCEPTANCE.md`; independent acceptance remains
owned by the external reverse-outcome audit.

## Allowed surfaces

- application-service Atlas projection and schema;
- accepted Design/Smart/Binding registry descriptors and validators;
- existing publisher/compiler/composition integration;
- package and project data-only artifacts under registered roots;
- tests, specification, public developer docs, graph, workflow/handoff/evidence.

## Forbidden surfaces

- arbitrary class/callback/PHP/template/filesystem paths from project config or
  Markdown;
- handwritten Atlas as an independent truth source;
- unsupported/unadmitted Framework components presented as supported;
- external repository/site writes;
- Goal C, merge, push, tag, release or deploy.

## Evidence policy

Each batch records exact parent/candidate SHA, affected registries/routes,
commands/results/hashes, security negatives, rollback and remaining gaps under:

`source/workflow/evidence/2026-08-04-docara-goal-b-interface-library/`.

## Stop conditions

Use the parent Goal B stop conditions. In particular stop if the required
Framework wave is absent/unaccepted, a second runtime path is needed, Atlas
becomes handwritten truth, container admission becomes unbounded, default/a11y
parity cannot be preserved, external writes are required or user changes
overlap the active surfaces.

## Kaizen

No reusable lesson recorded yet. The stale disabled-skill selection by the
generic gate remains an existing federation routing-gap observation; it does
not change the repository contract.
