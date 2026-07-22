# Workflow: Docara Smart search trigger

Date: 2026-07-22
Status: review ready
Workflow ID: `2026-07-22-docara-smart-search-trigger`
Parent track: `docara-consolidation`
Baseline: `ae185c7`
Owner: `docara`
Companions: `sf5`, `ux`, `tester`

## Goal

Replace the manually assembled search control in the canonical Docara header
with the admitted Simai Framework `sf-button` Smart component while preserving
the search dialog, keyboard shortcut, accessibility attributes and responsive
icon-only state.

## Done when

- the canonical header emits `sf-button` with size `1`, type `outline`, scheme
  `on-surface` and the Framework search icon;
- the keyboard hint is supplied through the component's `icon-right` slot;
- Docara does not duplicate Framework button classes, spacing or radius;
- desktop and mobile light/dark rendering, click, `Cmd/Ctrl+K`, focus and dialog
  state pass browser verification;
- focused and full tests, exact build, static verification and rollback-safe
  local publication pass.

## Boundary

The known Framework producer defects for mobile root sizing and outline border
geometry remain upstream work. Docara must not compensate for them with local
height or padding overrides. Public push, merge, tag, release and production
readiness are excluded.

## Stages

- [done] Replace manual button markup and update responsive selector.
- [done] Add regression coverage and run focused/full verification.
- [done] Build, publish locally with rollback and run browser acceptance.
- [done] Record evidence and Kaizen.

## Evidence plan

Evidence root:
`source/workflow/evidence/2026-07-22-docara-smart-search-trigger/`

- `verification.md`
- `browser-acceptance.md`
- `deployment.md`

## Result

The canonical search trigger now invokes the admitted `sf-button` contract
directly. Framework props own size, type, scheme, icon, spacing and radius;
the keyboard hint uses the documented `icon-right` slot. Forwarded data and
ARIA attributes preserve the existing search controller without duplicating
the generated native button DOM.

Focused and full tests, exact build, static verification, rollback-safe local
publication and desktop/mobile light/dark browser checks pass. The known
Framework producer geometry defects remain explicit nonclaims and receive no
Docara compensation.

## Kaizen

The source inspection showed that a separate `docara.search-trigger` renderer
would add an unnecessary wrapper: `sf-button` already forwards behavior and
ARIA attributes and exposes the required right slot. The accepted simpler rule
is to compose the admitted Framework Smart directly and reserve a product Smart
for behavior or state that the admitted component cannot express.
