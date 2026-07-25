# Docara documentation completeness and component catalog

Date: 2026-07-25
Status: in progress
Workflow ID: `2026-07-25-docara-documentation-completeness-and-catalog`

## Goal

Make the published Docara documentation complete, current and easier to read:

- expose the vertical content rhythm as `layout.content.gap`;
- use `gap: 0` for Docara's own documentation;
- replace the searchable two-column component catalog with one readable card
  per row;
- include every supported generated component page in the documentation tree;
- reconcile the public documentation with the accepted product behavior and
  remove stale or incomplete explanations.

## Source of truth

1. Current schemas, compiler, publisher and tests in this repository.
2. Accepted workflow and evidence files under `source/workflow/`.
3. Current generated site, not the retired Jigsaw/Mix implementation.
4. SIMAI Framework utilities and Smart-component contracts pinned by the
   immutable Framework lock.

## Done when

- `layout.content.gap` is inherited, validated and rendered only through
  Framework `gap-*` utilities;
- Docara's own docs resolve to `gap-0`;
- the catalog has no filter UI or filter runtime and uses one card per row;
- supported component detail pages are visible under the catalog in navigation;
- the user-facing configuration, layout, component and architecture docs cover
  the current contracts;
- tests, static verification, link verification and browser acceptance pass;
- the exact verified build is served at `https://docara.test/`.

## Evidence

Evidence is written to:
`source/workflow/evidence/2026-07-25-docara-documentation-completeness-and-catalog/`.

## Boundaries

No default-branch merge, tag, package release or production deployment.
