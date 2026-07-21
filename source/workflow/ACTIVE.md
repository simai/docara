# Workflow: Figma alignment for Docara navigation

Date: 2026-07-21
Status: implemented and locally verified
Workflow ID: `2026-07-21-docara-navigation-figma-alignment`
Process model: `general_delivery`
Current state: `review_ready`
Target state: `review_ready`

## Current Goal

Align the official `docara.navigation` Smart component with the supplied Simai
Framework Simple Menu Figma nodes while preserving the declarative four-level
tree, active trail, keyboard behavior, responsive shell and portable build.

## Source Of Truth

- workflow:
  `source/workflow/2026-07-21-docara-navigation-figma-alignment.md`;
- Figma nodes: `17583:25972` and `17607:34059` in
  `ee9qUZp4VhVpDxeMqxWtcv`.

## Result

The official `tree` view now matches the supplied Framework Simple Menu:
left-side disclosures, responsive Framework level tokens, neutral active
surface, medium active typography and compact 1 px menu rhythm. Pointer,
keyboard, four-level, light/dark and mobile browser checks pass. The exact
verified build is served at local `docara.test` with rollback backup.

## Completion Guard

The local implementation guard is satisfied. Evidence:
`source/workflow/evidence/2026-07-21-docara-navigation-figma-alignment/acceptance.md`.
Public push, merge, package release and production readiness remain explicitly
excluded; independent tester acceptance is deferred to release promotion.
