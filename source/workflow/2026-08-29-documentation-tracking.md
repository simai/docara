# Workflow: Source-backed documentation tracking

Date: 2026-08-29
Status: release-ready

## Goal

Implement optional, universal documentation tracking in Docara, provide a
neutral JSON source contract and a SIMAI Framework provider, pilot it in
`ui-doc`, and synchronize public documentation, graph context, and the Docara
skill without releasing or deploying anything.

## Done When

- Projects without `documentation_tracking` retain their current behavior.
- Docara exposes deterministic source discovery, status, validation,
  hash-bound acceptance, and source-aware page scaffolding through the shared
  Application services used by CLI and MCP.
- The neutral contract and the pinned SIMAI Framework registry cover core,
  utilities, components, and Smart Components without a hand-maintained copy.
- `ui-doc` produces a Russian source-to-documentation report and retains one
  source of status truth.
- Radius semantics are corrected in the Framework contract and Russian docs.
- Unit/integration, full builds, static verification, and focused browser smoke
  are green; skill changes pass the Skill Sync Gate.

## Context

- Approved architecture: Docara owns tracking; providers own public source
  contracts; translations remain independent.
- Baselines at start:
  - Docara `f94dae359ee2590a806d4e82df3076a818b9b82e` on `main`, dirty.
  - ui-doc `5a4299b1c9f155e2e08275ea1486a1877435ed7e` on `main`, dirty.
  - ui `2228262ad3588227b20045f66ca43556fcd27974` on existing
    `codex/sf5-ui-radius-contract`, clean.
  - ui-loader `c94a214fb727f0468863d10a94d4388e0f111852` on `main`, clean.
  - ui-smart `8b0e0482f78a19aa6af2483d61fe9fe3b79a8e1b` on `main`, clean.
- Existing `simai.framework.contract-registry` already groups utilities and
  enumerates components and Smart Components. It is extended/reused, not
  replaced.

## Constraints And Risks

- Preserve all unrelated dirty changes and the current development lines.
- Do not edit generated build output as source.
- Commit, push, tag and package publication were authorized on 2026-08-30;
  public deploy remains out of scope.
- Build and status operations never edit Markdown or lock files and never call
  AI or the network.
- Canonical skill writes require the owner plan and Skill Sync Gate.

## Batch Plan

| Batch | Goal | Work | Verification | Status |
| --- | --- | --- | --- | --- |
| 1 | Reuse baseline | Inventory existing SDK, translation acceptance, Framework registry, and project audit | Baseline receipts and focused existing tests | complete |
| 2 | Docara core | Schemas, neutral model/providers, status and acceptance services | Unit fixtures for statuses, hashes, paths, stale apply | complete |
| 3 | SDK surface | list/inspect/schema/validate/documentation/scaffold/MCP integration | CLI/MCP parity and integration tests | complete |
| 4 | Framework contract | Add documentation-facing public fingerprints and radius semantics to the existing registry build | Registry deterministic build and owner tests | complete-with-release-binding-follow-up |
| 5 | ui-doc pilot | Configure tracking, migrate accepted relations, thin/remove duplicate audit, correct radius docs | Full report/build/verify-static | complete |
| 6 | Documentation and graph | Product specifications, public docs, graph/context and skill sync | Graph validators and Skill Sync Gate | complete-with-revision-gate |
| 7 | Acceptance | Full regression, browser smoke, simplicity review | Tester verdict and final evidence | complete |

## Progress

### Batches 1-3: Docara contract and SDK

- Added the optional `documentation_tracking` configuration, schemas
  `docara.documentation_source.v1`, `docara.documentation_lock.v1`, and
  `docara.documentation_status.v1`.
- Added neutral JSON and SIMAI Framework providers, deterministic public
  fingerprints, all requested statuses, exclusions, diagnostics, protected
  plan/apply, source-aware page scaffold, CLI and MCP parity.
- Build and validation are read-only with respect to Markdown and the lock;
  report mode writes only `.docara/documentation-status.json`.
- Focused acceptance: 24 tests, 151 assertions, green. The status fixture also
  covers stale source/page/example/config/lock plans, duplicate keys, multiple
  entities per page, filesystem constraints, compatibility mode, and exact
  pinned neutral contracts.

### Batch 4: Framework contract

- Extended the existing Framework registry build instead of creating a second
  registry. The published UI 5.4.1 contract emits 334 entities: 1 core, 226
  utilities, 63 components, 43 Smart Components, and 1 project-specific recipe.
- Radius ownership is explicit: `--sf-radius--ui` belongs to compact controls;
  `--sf-radius-default` belongs to large surfaces; `square` and `rounded`
  override the semantic default.
- Deterministic contract tests and Docara schema validation are green.
- Corrected the Loader Registry inventory: the existing runtime assets
  `file-preview` and `link` had fallen out of `rule.json`. Both are again
  discoverable, the source inventory is 60 components, its deterministic gzip
  projection matches, and the regression test binds both IDs to real runtime
  directories.
- The Framework source contract and runtime are now bound to published UI
  `v5.4.1` (`185ca062...`) and Smart `v5.4.0` (`23d00d92...`) through the exact
  compatibility id `sf-v5.4.1-185ca062-23d00d92`.

### Batch 5: ui-doc pilot

- Enabled Russian source tracking and migrated the legacy component audit to a
  thin Docara CLI wrapper.
- Accepted through protected plans 215 of 334 source entities: all 61 mapped
  component documentation units, 153 unambiguous utility families, and the
  core design-token reference. The remaining 119 are reported as `new` and
  require explicit mapping/examples; none are silently excluded.
- `inspect page /ru/components/buttons/` exposes the accepted entity, exact
  revision/provenance and seven examples. Component audit remains 61/61.
- Corrected radius explanations in design tokens, utilities, and buttons
  without repeating the canonical semantic explanation.
- Current physical corpus is 909 Markdown pages (479 RU and 430 EN). Historical
  evidence mentioning 939 predates the approved component-section migration.

### Batches 6-7: documentation and acceptance

- Updated Docara public documentation, specification, graph specification and
  generated context. Updated the canonical English Docara skill source and its
  capability graph.
- Docara documentation build completed for 128 pages. Static verification
  checked 263 HTML files and 35,553 local references without a broken target.
- `ui-doc` built all 909 current Markdown pages. Static verification checked
  1,788 HTML files and 370,061 local references without a broken target.
- Browser smoke passed for button icons/examples, the radius table, mobile dark
  mode and canonical radius semantics; console errors and warnings were zero.
- Reconciled the inherited dirty-baseline assertions with the actual contracts:
  asynchronous Example environment measurement and the 826-file Framework
  projection. The optional Node semantic test now probes the discovered binary
  and skips when that external executable is unusable instead of terminating
  PHPUnit; the current ServBay alias resolves to a Homebrew Node with a missing
  `libsimdjson.30.dylib`.
- The final release-candidate Docara suite is green: 552 tests, 16,502 assertions, one expected
  environment skip. The skipped semantic scenario separately passes with the
  bundled runnable Node: 4 tests, 68 assertions. The tracking-focused suite
  remains green (24 tests, 151 assertions).
- Skill validators are green. The canonical skill changes remain deliberately
  unactivated until this Docara release is revision-bound; the final Skill Sync
  Gate follows publication. Route check previously passed 328/328.

## Final Result

- Result: implementation and local regression correction complete.
- Verification: see
  `source/workflow/evidence/2026-08-29-documentation-tracking/verification-summary.json`.
- Remaining: publish the revision-bound Docara package, migrate `ui-doc` to the
  exact release, and complete the revision-bound Skill Sync Gate.
- Follow-up: public deploy is not authorized and is not part of this workflow.
