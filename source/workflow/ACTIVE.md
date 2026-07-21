# Active workflow: Docara right outline refinement

Date: 2026-07-22
Status: implemented and locally verified
Workflow ID: `2026-07-22-docara-outline-legacy-refinement`
Process model: raw-owner `docara + sf5 + ux + designer + tester + ops`
Current state: `review_ready`
Target state: `review_ready`

## Current Goal

Make the canonical `docara.toc` desktop outline as compact and orienting as
the useful legacy surface while preserving the declarative publisher, Simai
Framework contract, mobile behavior, RTL and accessibility.

## Result So Far

Desktop links now render at 14px in 36px rows using Framework spacing and
typography utilities. The component owns scroll-aware
`aria-current="location"` state and a 2px token-based rail marker. The mobile
outline retains a 44px minimum target and no page-level overflow.

## Completion Guard

Full PHPUnit passed at 618 tests / 5,465 assertions. The exact build passed
static verification for 271 HTML pages and 20,569 local references, and is
served at local `docara.test` with rollback backup. Evidence:
`source/workflow/evidence/2026-07-22-docara-outline-legacy-refinement/`.

Public push, merge, tag, package release and production readiness remain
excluded.
