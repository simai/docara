# Workflow: Docara Framework surface cleanup

Date: 2026-07-22
Status: completed
Workflow ID: `2026-07-22-docara-framework-surface-cleanup`
Parent track: `docara-consolidation`
Baseline: `f4efc68551043f4a5127dc3b5c1f6d35c9f53d25`
Primary owner: `docara`
Companions: `sf5`, `ux`, `dev`, `tester`
Quality control: `human_centered_simplicity` raw-owner fallback
Graph gap: federation process resolver incorrectly selected
`regulatory_demand_defense_process` for a bounded frontend refactor; the raw
owner skills and repository workflow are authoritative for this batch.

## Goal

Make Docara's reading breadcrumbs usable at deep hierarchy on desktop and
mobile through the shipped Simai Framework breadcrumbs contract, then audit
the primary publisher UI for `docara-*` classes that merely duplicate shipped
Framework utilities, components or Smart-components.

## Done When

- a reader can understand and use a five-level breadcrumb at 390 px without
  squeezed text columns, clipping or page overflow;
- breadcrumbs remain server-rendered and useful without JavaScript, preserve
  `nav`, localized accessible name and `aria-current="page"`, and enhance
  through the shipped Framework runtime when available;
- retained custom classes have an explicit product/layout/behavior reason;
- proven utility/component duplicates in the primary publisher surface are
  removed or replaced by exact shipped Framework primitives;
- focused regression, full PHPUnit, deterministic static verification and
  desktop/mobile browser acceptance pass;
- the exact verified build is published only to local `docara.test` with a
  rollback backup;
- public push, merge, tag, package release and production-readiness claims are
  excluded.

## Primary Scenario

A documentation reader opens a deeply nested page on a phone, immediately sees
the site root and current page with a discoverable collapsed middle path, can
reveal the full hierarchy, and can continue reading without horizontal page
overflow.

## Constraints And Risks

- keep portable server-rendered fallback; do not make breadcrumbs depend on
  client-side custom-element upgrade;
- reuse the shipped Framework `breadcrumbs` / `sf-breadcrumbs` contracts and
  utilities before creating Docara-specific presentation;
- do not remove `docara-*` hooks required for product layout, responsive
  behavior, JavaScript, stable tests, accessibility or content styling;
- do not edit generated served files as source;
- preserve locale-independent data and localized UI copy;
- keep the immutable legacy renderer unchanged as rollback evidence.

## Batch Plan

| Batch | Goal | Verification | Status |
| --- | --- | --- | --- |
| 1 | baseline, upstream contract and custom-class inventory | source refs, DOM/browser measurements, inventory fingerprint | completed |
| 2 | responsive progressively-disclosed breadcrumbs | focused DOM/unit tests, no-JS HTML inspection | completed |
| 3 | bounded publisher class cleanup | class-to-primitive matrix, focused regression | completed |
| 4 | build, browser, full regression and local publication | static verifier, desktop/mobile/keyboard, rollback proof | completed |
| 5 | docs, evidence and commit | diff check, hygiene/action gates | completed |

## Simplicity Contract

Simplest complete alternative: retain one server-rendered Framework
breadcrumbs structure, collapse only the middle path, and use one existing
Framework behavior/controller. Do not create a parallel Docara breadcrumb
visual language or a second source of hierarchy data.

Protective complexity retained: localized labels, semantic navigation,
current-page state, no-JavaScript fallback, focus, touch targets, deterministic
static output, compatibility and rollback.

## Progress

### Batches 1-3

- Status: completed.
- Baseline browser defect: at 390 px a five-level path is squeezed into narrow
  wrapped columns inside a fixed 44 px row; page overflow is absent only
  because flex items shrink.
- Source finding: Docara renders the shipped Core breadcrumb classes manually,
  while the current Framework also ships `sf-breadcrumbs` with ellipsis and
  disclosure behavior.
- Result: the server-rendered Core component now progressively collapses the
  middle path, retains a complete no-JavaScript fallback and receives a
  localized ellipsis label.
- Result: proven atomic duplicates were replaced by exact utilities and dead
  presentation classes were removed. Product geometry, behavior and
  accessibility classes were retained with reasons.
- Framework gap: the pinned producer does not visually honor `hidden` on
  breadcrumb items; one attribute-based compatibility rule is retained and
  recorded for upstream correction.

### Batches 4-5

- Status: completed.
- Production documentation build and static verification passed: 271 pages,
  20,569 local references and zero broken references.
- Local publication completed with served hash
  `9702c51db66373b850189adf9a2f48841e87f24148defe13be1b4cde66f813c5`
  and rollback backup.
- Mobile and desktop browser acceptance passed, including ellipsis disclosure,
  localized accessible name, current state and absence of page overflow.
- Final full regression passed: 618 tests and 5,459 assertions.

## Evidence

Evidence will be stored under:
`source/workflow/evidence/2026-07-22-docara-framework-surface-cleanup/`.

## Remaining

- none inside the bounded goal;
- upstream Framework producer correction for `[hidden]` and generated-label
  localization is a separate release task.
