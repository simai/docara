# Workflow: Figma alignment for Docara navigation

Date: 2026-07-21
Status: implemented and locally verified
Workflow ID: `2026-07-21-docara-navigation-figma-alignment`
Process model: `general_delivery`
Quality control: `human_centered_simplicity`
Current state: `review_ready`
Target state: `review_ready`
Parent track: `docara-consolidation`
Memory decision: `skip`
Memory reason: repository workflow and evidence are sufficient; no personal
memory update is needed.

## Current Goal

Align `docara.navigation` with the supplied Simai Framework Simple Menu design
without breaking declarative navigation data, accessibility or portable
publication.

## Outcome

Align the official `docara.navigation` Smart component with the Simai
Framework Simple Menu design while preserving Docara's declarative four-level
navigation data, active trail, keyboard disclosure, responsive shell and
portable build.

## Final Outcome

The official `docara.navigation` `tree` view uses the supplied Framework Simple
Menu visual and interaction contract, passes full local verification and is
served rollback-safely on `docara.test` without changing navigation content or
introducing a second frontend implementation.

## Design evidence

- Simple Menu Item component: Figma node `17583:25972` in file
  `ee9qUZp4VhVpDxeMqxWtcv`;
- assembled Simple Menu: Figma node `17607:34059` in the same file;
- structured design context, screenshots and variable definitions were read on
  2026-07-21;
- Code Connect was unavailable for the current Figma seat, so repository
  component mapping is performed against the bundled Framework Menu assets.

## Primary scenario

A documentation reader can scan the left menu, see the exact current page and
its hierarchy, expand or collapse branches with pointer or keyboard, and use
the same navigation at desktop and mobile widths. The visual states use Simai
Framework tokens and match the supplied Simple Menu composition without
introducing a second menu primitive.

## Done When

- the existing `docara.navigation` manifest/view remains the source of truth;
- the selected `tree` view uses the Framework Menu structure and Figma token
  mapping for levels 1-4, hover, open and active states;
- current page, active trail, focus and disclosure semantics remain intact;
- focused tests, full regression, deterministic static build and desktop/mobile
  browser checks pass;
- the exact verified build is published rollback-safely only to local
  `https://docara.test/`;
- no public push, merge, tag, package release or production-readiness claim is
  made.

## Stages

- completed: exact Figma context, screenshots, variables and component map;
- completed: template, Framework-token CSS, interaction and renderer changes;
- completed: focused/full/static/browser verification;
- completed: documentation and rollback-safe local publication.

## Batches

1. Figma evidence and current component baseline.
2. Smallest complete Smart-component implementation.
3. Regression, responsive, theme and interaction acceptance.
4. Documentation, local atomic publication and durable evidence.

## Evidence Plan

- implementation, design mapping, QA, browser and rollback evidence:
  `source/workflow/evidence/2026-07-21-docara-navigation-figma-alignment/acceptance.md`;
- action-gate report:
  `source/output/action-gates/action-gate-report-20260721175601.json`.

## Track Linkage

- parent track: `docara-consolidation`;
- previous completed goal: symmetric locale routing;
- this batch changes only the presentation/interaction contract of the existing
  navigation Smart component.

## Scope

Allowed:

- `resources/smart/docara.navigation/**`;
- navigation renderer/view-model tests when required;
- Docara documentation describing Smart view customization;
- this workflow and its evidence;
- reversible local build publication paths under
  `/Users/rim/Sites/docara.test`.

Forbidden:

- changing Markdown navigation data to imitate the design;
- adding Tailwind or a second frontend framework;
- editing generated served files as source;
- changing ServBay configuration, secrets or public release state;
- broad changes to the separate `ui` repository in this bounded batch.

## Simplicity decision

The simplest complete implementation reuses `sf-menu`, `sf-menu-item`,
`sf-menu-element`, Framework tokens and the existing Docara renderer. It does
not add a new view name, a second JavaScript controller, decorative wrappers or
page-specific CSS. Figma's fixed 360 px example width is adapted to the
existing responsive sidebar rather than copied as a hard-coded width.

Protective complexity retained: active-page semantics, active ancestor data,
visible focus, 44 px disclosure hit area, keyboard operation, four-level depth
limit and mobile navigation.

## Result

The Figma-aligned `tree` view is implemented, fully regression-tested and
published rollback-safely to local `docara.test`. Verification and rollback
evidence:
`source/workflow/evidence/2026-07-21-docara-navigation-figma-alignment/acceptance.md`.

The workflow is `review_ready`. Public push, merge, tag, package release and
production readiness are outside this goal. Independent tester acceptance is
required only before later release promotion.
