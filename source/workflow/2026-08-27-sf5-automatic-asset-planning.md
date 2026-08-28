# Workflow: automatic stable asset planning in SIMAI Framework

Date: 2026-08-27
Status: completed
Owner: SF5 / Docara
Track: `docara-project-technology`
Process model: `docara_documentation_site_publication`
Launch record: `source/workflow/2026-08-27-sf5-automatic-asset-planning.launch.yaml`
Evidence: `source/workflow/evidence/2026-08-27-sf5-automatic-asset-planning/`

## Goal

Move first-paint resource discovery from Docara-specific shell declarations into
the existing SIMAI Framework registry and loader contract. Projects with a build
step receive an exact asset plan automatically; projects without a build step
keep the current dynamic Loader behavior and developer-facing `sfPath`,
`sfSmartPath`, `core.css` and `core.js` interfaces.

## Current Goal

Implement the shared SIMAI Framework Asset Planner and replace the
Docara-specific first-paint resource plan without changing the developer-facing
Framework connection contract or ui-doc content.

## Done When

The shared planner detects exact resources from final HTML, Docara consumes it
without `utility.full.css` or a manual shell tag list, compatibility remains
green, and static/browser evidence satisfies the acceptance limits below.

## Track Linkage

- parent track: `docara-project-technology`;
- this correction advances the current Framework/Docara architecture and does
  not reopen the completed Docara product goal or the historical release track.

## Stages

1. Framework registry and planner contract.
2. Docara integration and static verification.
3. ui-doc consumer/browser acceptance.
4. local site refresh and project-technology synchronization.

## Baseline and protected work

- `ui`: branch `codex/sf5-ui-radius-contract`, HEAD
  `2228262a`, clean at intake;
- `ui-loader`: `main`, HEAD `4f1beecd`, 80 dirty paths owned by existing work;
- `ui-builder`: `main`, HEAD `367b342`, clean at intake;
- `docara`: `main`, HEAD `8ab5bc4`, 54 dirty paths including the completed
  shell-preload correction and other user work;
- `ui-doc`: `main`, HEAD `de9b561`, 310 dirty paths owned by documentation work;
- none of these baselines may be reset, stashed or overwritten.

The completed
`source/workflow/2026-08-27-docara-shell-resource-preload-correction.md`
remains historical evidence. Its Docara-specific planner is the migration input,
not the target architecture.

## Reuse inventory and simplicity decision

- No new registry is introduced. The existing Framework Contract Registry
  remains the public capability contract, while `ui/distr/rule/rule.json`
  remains the Loader matching source for utilities,
  components, `sf-*` tags and dependency relations.
- The production planner extends those sources with deterministic asset order
  and exact runtime files; it does not introduce a second manifest.
- The dynamic Loader and browser HTTP cache remain the runtime fallback. No
  LocalStorage correctness dependency, SPA router, HTML cache or global
  skeleton is added.
- Docara keeps only its own layout CSS, invokes the shared plan contract and
  writes its existing build receipt.

## Batches

1. Extend the existing Framework Registry and add the generic Asset Planner.
2. Replace Docara-specific first-paint planning with the shared contract.
3. Verify Docara and the unchanged ui-doc consumer in static and browser tests.
4. Refresh the local site safely and synchronize project technology evidence.

| Batch | Goal | Acceptance | Status |
| --- | --- | --- | --- |
| A1 | Reuse Framework registries and add the generic Asset Planner | deterministic HTML detection, dependency closure, exact order and safety fixtures | completed |
| A2 | Replace Docara shell resource declarations with the shared plan | no manual shell tag list and no `utility.full.css` in optimized output | completed |
| A3 | Verify product and consumer | Docara build/verify, unchanged ui-doc content, cold/warm browser matrix | completed |
| A4 | Refresh local site and project technology | temporary rollback only, smoke, graph/context/memory current | completed |

## Evidence Plan

Record exact Git/dirty baselines, the reuse decision, planner fixtures and
safety failures, generated asset receipts, Docara/ui-doc build output, browser
CLS/geometry/network observations, local swap rollback cleanup and the final
human-centered simplicity verdict under the workflow evidence directory.

## Invariants

- Developer-authored HTML and existing connection snippets do not change.
- Dynamic Loader behavior remains available for late content and no-build
  projects.
- JavaScript files are ordered, not concatenated mechanically.
- The planner rejects traversal, symlink, hardlink, case collision, invalid
  regex, missing dependency and hash drift.
- `utility.full.css` is forbidden only when an exact optimized plan is present;
  compatibility fallback may continue to use the legacy dynamic route.
- Content and dependency locks in `ui-doc` are read-only.
- No commit, push, tag, release, package publication or public deploy.

## Done when

- the shared Framework planner and registry contract pass unit and integration
  fixtures for utilities, components, Smart Components and `data-sf-require`;
- Docara consumes the shared result and removes its manual component list,
  special `utility.full.css` and project-owned preload truth;
- static verification proves initial DOM resource completeness, unique requests,
  dependency order and truthful Loader handoff;
- Docara and ui-doc build successfully, and browser evidence shows shell CLS no
  higher than `0.01` and settled geometry drift no higher than `1 CSS px`;
- local `ui-doc.test` is swapped only after green checks, HTTP/browser smoke
  passes and the temporary rollback is removed;
- specification, correction routing, Mirai Graph, generated context and project
  memory describe the new shared mechanism without rewriting historical evidence.

## Completion

- The generic planner is implemented in `ui` and covered by eight focused
  fixtures. Dynamic Loader behavior remains the compatibility path.
- Framework component contracts now preserve first-paint geometry for
  undefined `sf-icon` hosts and reuse server-rendered Menu text during
  hydration instead of introducing an extra flex gap.
- Docara consumes the exact plan, emits content-hashed shell CSS, publishes a
  truthful `SF_PRELOADED` handoff and verifies the plan from final HTML.
- The unchanged `ui-doc` content and dependency locks produced 912 pages;
  `verify-static` checked 1,665 HTML outputs and 370,385 local references with
  zero broken records.
- Browser acceptance passed cold/warm desktop, mobile, light/dark and changed
  font geometry. Header/sidebar/outline/main were not layout-shift sources,
  the worst observed page CLS was `0.00134`, and the final corrected control
  page measured `0`.
- The temporary rollback `/tmp/ui-doc-test-rollback.KX8GMF` was deleted after
  successful build, HTTP and browser smoke. No permanent backup was created in
  `/Users/rim/Sites`.
- After the ServBay CLI configuration was corrected, the full 912-page ui-doc
  build passed again without a command-line memory override. Static verification
  again found zero broken references, and HTTP/browser smoke passed; this closes
  the build-memory follow-up in `post-servbay-memory-rebuild.json`.
- No commit, push, tag, release, package publication or public deploy was
  performed.
