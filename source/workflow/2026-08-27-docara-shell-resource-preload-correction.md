# Workflow: correct Docara shell resource loading

Date: 2026-08-27
Status: completed
Owner: Docara / Development
quality_controls: human_centered_simplicity
simplicity_review: source/workflow/evidence/2026-08-27-docara-shell-resource-preload-correction/human-centered-simplicity-review.json
simplicity_repository_refs: repo://docara
simplicity_repository_baselines: repo://docara@8ab5bc48c251283c00f1de23ab4f04384a1021a3
Process model: `docara_documentation_site_publication`
Launch record: `source/workflow/2026-08-27-docara-shell-resource-preload-correction.launch.yaml`
Evidence: `source/workflow/evidence/2026-08-27-docara-shell-resource-preload-correction/`

## Current Goal

Make the Docara header, navigation, outline and main layout reach their final
geometry before first paint by statically planning the exact Framework shell
resources, without a fixed header height, a global skeleton, partial navigation
or a second cache or asset registry.

## Goal

Make the Docara header, navigation, outline and main layout reach their final
geometry before first paint by statically planning the exact Framework shell
resources, without a fixed header height, a global skeleton, partial navigation
or a second cache/asset registry.

## Start State

The existing dynamic Framework loader inserted shell-critical CSS after first
paint. Fresh timing evidence reproduced header, sidebar and main-layout shifts,
including shell CLS around `0.05355`, even though the prior stabilization work
had already improved the visible layout.

## Final Outcome

Docara now emits the exact verified shell resource closure before Core starts.
Header, navigation, outline and main layout keep their natural typography-based
geometry from the first sample through settlement, and the local `ui-doc.test`
consumer passes the cold/warm browser matrix without shell shift sources.

## Goal Vector

- User outcome: documentation navigation feels steady rather than assembling in
  visible phases.
- Product mechanism: extend the existing planner, runtime lock, receipt and
  verifier instead of adding another cache, registry or loading system.
- Protected constraints: no fixed header height, global skeleton, partial
  navigation, ui-doc content edit or weakened path/hash validation.
- Completion evidence: deterministic builds, static verification, regression
  tests, browser geometry/CLS measurements and atomic local-site smoke.

## Done When

- the package-owned Framework lock declares exact loader identities and dependency
  closure for shell Smart components;
- `FrameworkAssetPlanner` deterministically emits one content-hashed shell CSS
  asset from the existing Docara shell and admitted Framework CSS inputs;
- `window.SF_PRELOADED` is emitted before Core and contains only completely
  projected modules in verified dependency order;
- the existing build receipt records source files, order, hashes and actual
  preload modules, and `verify-static` rejects drift or false preload claims;
- compatible old project locks inherit package-owned preload metadata without
  mutation; complete absence of the package contract keeps dynamic behavior
  with a diagnostic warning;
- Docara tests, full docs build, `verify-static`, ui-doc consumer build and the
  agreed browser matrix pass;
- shell layout-shift sources are zero, total CLS is at most `0.01`, and initial
  versus settled shell geometry differs by at most `1 CSS px`;
- the local `ui-doc.test` bytes are replaced atomically only after green checks,
  HTTP/browser smoke passes, and the temporary rollback is removed;
- specification, changelog, graph, generated context, workflow evidence and
  project memory describe the correction without rewriting historical evidence.

## Baseline

- repository: `/Users/rim/Documents/GitHub/docara`;
- branch: `main`;
- HEAD: `8ab5bc48c251283c00f1de23ab4f04384a1021a3`;
- pre-correction tracked diff SHA-256:
  `18c3419fc46da5a291ff6e29dafe6daf194683420855d9bad7e494243e58a7b0`;
- the dirty tree contains the completed prose-margin and first shell-stability
  batches and is preserved as the correction input;
- the earlier workflow
  `source/workflow/2026-08-27-docara-stable-shell-loading.md` remains completed
  history. Its browser conclusion is superseded only for routing because fresh
  timing evidence reproduced a shell CLS around `0.05355` after cached resources
  were inserted following first paint.

## Human Outcome And Invariants

- Outcome: navigating documentation feels steady; users do not see the header,
  menu or columns jump while cached Framework resources finish loading.
- Keep: natural typography-dependent header height, size `1` controls, normal
  full-page static navigation, current Core/utility assets and the Framework
  dynamic loader for non-critical components.
- Protect: old projects, path/hash validation, atomic builds, static verifier,
  sandboxed examples, user font/theme preferences and current dirty work.

## Constraints And Risks

- Do not edit ui-doc content or dependency lock.
- Do not add a global skeleton, fixed header height, Turbo/partial navigation,
  second cache, second registry or parallel status engine.
- Do not mechanically concatenate Framework JavaScript.
- Reject traversal, external URL rewriting, symlink, hardlink, case conflict and
  source-hash mismatch while producing the shell asset.
- No commit, push, tag, release or public deployment.
- The local site operation requires a temporary rollback and post-swap HTTP and
  browser smoke; no permanent backup directory may remain in `/Users/rim/Sites`.
- Stop if truthful preload requires guessing loader identities or if the package
  lock cannot describe a complete dependency closure.

## Batch Plan

| Batch | Goal | Work | Verification | Status |
| --- | --- | --- | --- | --- |
| C1 | Freeze correction baseline | workflow, launch record, action gates, cold/warm measurements | Git/diff fingerprints, request and layout-shift evidence | completed |
| C2 | Static shell resource plan | lock metadata, hashed CSS compiler, preload order, receipt | unit/integration tests and negative path/hash cases | completed |
| C3 | Static acceptance | verifier rules, compatibility warning, docs/spec/graph/context | Docara full build and `verify-static` | completed |
| C4 | Consumer acceptance | build ui-doc through current checkout, browser matrix | CLS/geometry/network/console evidence | completed |
| C5 | Local publication | atomic `ui-doc.test` replacement and cleanup | HTTP/browser smoke, rollback removal | completed |

## Stages

- Stage 1 — advances the Goal Vector by freezing the reproduced baseline and
  preserving the existing dirty work.
- Stage 2 — advances the Goal Vector by projecting the truthful shell resource
  closure before first paint.
- Stage 3 — advances the Goal Vector by enforcing the contract in tests, static
  verification and compatibility behavior.
- Stage 4 — advances the Goal Vector by proving the result in the real ui-doc
  consumer and local site.

## Batches

- C1 advances the Goal Vector through baseline and layout-shift evidence.
- C2 advances the Goal Vector through the static shell resource plan.
- C3 advances the Goal Vector through Docara build and verifier acceptance.
- C4 advances the Goal Vector through ui-doc and browser acceptance.
- C5 advances the Goal Vector through atomic local publication and rollback
  cleanup.

## Evidence Plan

- Record exact repository and consumer fingerprints before and after the work.
- Run unit/integration tests, complete Docara/ui-doc builds and `verify-static`.
- Measure cold/warm desktop/mobile shell geometry, CLS, network duplication,
  console errors and failed requests.
- Confirm the atomic local-site target, HTTP status and removal of temporary
  rollback directories.

## Reverse Audit Plan

Start from the desired steady first paint and reject completion unless the
browser evidence proves no shell layout-shift source, CLS at most `0.01`, shell
geometry drift at most `1 CSS px`, no duplicate critical resources, and no
consumer-source or dependency-lock changes.

## Simplest Complete Alternative

The retained solution extends `FrameworkAssetPlanner`, the package runtime lock
and the existing Framework receipt. A full SPA, global loading curtain, fixed
geometry, new cache or a monolithic Framework bundle would add surface without
fixing the first-paint ordering contract and is excluded.

## Progress

### Batches C1-C5

- Status: completed.
- Done: the package runtime lock, existing planner, receipt and verifier now
  describe and enforce one exact shell resource closure. The generated
  `docara-shell.<sha256>.css` contains the admitted shell and component CSS;
  truthful `SF_PRELOADED`, dependency scripts, Core, local font preloads and
  server-rendered fallback geometry are emitted before dynamic content work.
- Compatibility: compatible old project locks inherit package-owned shell
  metadata without mutation; complete absence of that metadata keeps the old
  dynamic route with a diagnostic warning.
- Verification: 537 tests / 11,911 assertions; Docara and ui-doc full static
  builds pass with zero broken references; desktop/mobile cold and warm browser
  checks pass without shell layout-shift sources, failed requests, console
  errors or duplicate resource URLs. Header geometry is 73 CSS px on desktop
  and 65 CSS px on mobile from first sample through settlement. Ten independent
  live cold runs measured maximum CLS `0.00321972085774561`.
- Local publication: `ui-doc.test` was atomically switched to tree digest
  `9812351a4b72fc48c4ad84f6599dab2ffdef08f2a270d8d1c8f7ffc8a26a3cb7`;
  HTTP/browser smoke passed and both temporary rollback directories were moved
  to the system Trash. No permanent backup directory remains in the site path.
- Remaining: none inside this workflow. Git publication and a public deploy
  require a separate explicit user decision.

## Drift / Correction

- An intermediate live smoke exposed a remaining `0.0115` CLS contribution from
  delayed highlight styling; correction moved the code-block adjacency geometry
  into the early shell CSS and the final ten cold runs stayed at or below
  `0.00321972085774561`.

## Evidence

- Acceptance index:
  `source/workflow/evidence/2026-08-27-docara-shell-resource-preload-correction/INDEX.md`.
- Machine-readable verification summary:
  `source/workflow/evidence/2026-08-27-docara-shell-resource-preload-correction/verification-summary.json`.
- Human-centered simplicity review: PASS.

## Result Verdict

PASS — the bounded correction meets its technical, visual, compatibility and
local-publication acceptance criteria without entering Git publication or the
public deployment contour.

## Final Result

- Result: the Docara shell reaches stable natural geometry before first paint,
  while non-critical Framework components remain dynamically loaded.
- Verification: PASS; see
  `source/workflow/evidence/2026-08-27-docara-shell-resource-preload-correction/`.
- Remaining: no implementation task is active.
- Follow-up: explicit user decision for any commit, push, tag, release or public
  deploy.
