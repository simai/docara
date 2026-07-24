# Active workflow: Docara header navigation

Date: 2026-07-24
Status: completed
Workflow ID: `2026-07-24-docara-header-navigation`
Track: docara-consolidation
Process model: `docara_documentation_site_publication`
Current state: `readiness_verdict_recorded`
Target state: `readiness_verdict_recorded`
Launch record:
`source/workflow/2026-07-24-docara-header-navigation.launch.yaml`

## Current Goal

Add a configurable, multilingual and responsive primary navigation to the
Docara header using the existing `docara.navigation` Smart component and the
existing single mobile navigation sheet.

## Final Outcome

Docara renders multilingual primary links through one navigation Smart
component on desktop and in the shared mobile sheet. The published local
surface has no persistent item frames, while active, hover and keyboard-focus
states remain clear. Brand, primary navigation, search and header controls use
one Framework size-1 rhythm at `var(--sf-d0)`.

## Done When

- locale-specific header items pass strict configuration validation;
- desktop and mobile projections use the same typed navigation source;
- no persistent borders remain around navigation items;
- active, hover and `focus-visible` states remain distinct;
- tests, production build, static verification and browser acceptance pass;
- the exact verified build is served locally with a rollback copy.

## Stages

1. Define and validate multilingual header navigation.
2. Project it through the existing Smart component and mobile sheet.
3. Correct Framework border-output compatibility without losing UI states.
4. Verify and publish the local build reversibly.

## Batches

1. Header navigation contract and renderer.
2. Responsive desktop/mobile integration.
3. Border and keyboard-focus visual refinement.
4. Automated, static and browser acceptance.

## Track Linkage

- parent track: `docara-consolidation`;
- current workflow: `2026-07-24-docara-header-navigation`;
- this workflow closes only header navigation and its local test publication.

## Current result

- Strict multilingual `header_navigation` configuration is implemented.
- The existing `docara.navigation` Smart component renders desktop and mobile
  projections.
- One adaptive mobile dialog combines primary links and documentation tree.
- Production build, static verification, automated tests and browser
  acceptance passed.
- The verified build is published to `https://docara.test/ru/`.
- The header brand, active menu item, outline search button and settings
  control all compute to exactly `40px` from `var(--sf-d0)` and share the same
  vertical center.

## Evidence

- workflow:
  `source/workflow/2026-07-24-docara-header-navigation-plan.md`;
- acceptance:
  `source/workflow/evidence/2026-07-24-docara-header-navigation/acceptance.md`;
- local target: `https://docara.test/ru/`.

## Boundary

No commit, merge, tag, package publication, public deployment or
production-readiness claim.

## Personal Memory

Personal memory decision: skip

Personal memory reason: repository workflow and evidence already preserve the
decision; the user did not request a personal-memory update.

## Kaizen

`stable_reusable_lessons_or_skip_reason`: generated Framework component CSS
may repeat physical border widths after logical and custom-property
declarations. Browser computed-style acceptance is therefore required for
border-sensitive component states; a product-scoped logical-axis fallback is
acceptable until the immutable Framework distribution carries the generator
fix. Size labels alone are not sufficient browser evidence: the pinned
outline button at Framework size `1` computed to `42px` because its borders
were added around the content box. A standard Framework `h-d0` utility keeps
the Smart component and constrains the contextual header surface to the
documented size scale without a hard-coded pixel value.

The search shortcut refinement keeps the integration equally narrow:
`text-1`, `color-on-surface-variant` and `m-inline-start-1/2` express
typography, tone and spacing through Framework utilities. The outline button
itself exposes its supported `--sf-button--border-color` contract because a
generic border-color utility cannot override the component layer.
