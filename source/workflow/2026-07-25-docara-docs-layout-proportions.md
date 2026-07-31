# Workflow: Docara documentation layout proportions

Date: 2026-07-25
Status: completed
Track: docara-consolidation

## Goal

Give documentation pages a wider primary reading area while keeping the
multi-level section navigation usable and making the page outline compact.

## Done When

- shared Docara containers use the Framework `w-full` utility and remain
  bounded by the configured `max-container-*` modifier;
- the documentation grid uses Framework size variables rather than new raw
  dimensions;
- the default outline rail is `--sf-f2`, while the sidebar retains its current
  `--sf-f4` to `--sf-f8` range;
- generated documentation is rebuilt and served at `https://docara.test/`;
- desktop and mobile layouts have no horizontal overflow and the existing
  responsive rail behavior is preserved.

## Context

At a 1296px browser viewport the current `.container` resolves to 1152px.
The grid then allocates 288px to navigation and 240px to the outline, leaving
544px before reading-column padding. `max-container-7` is not reached because
the shared containers do not also declare `w-full`.

The federation router selected the installed Docara skill, but the user has
explicitly marked that skill obsolete. This batch therefore uses the repository
as source of truth with `dev`, `sf5`, and UX implementation-support contracts.

## Constraints And Risks

- preserve all existing dirty-worktree changes;
- do not edit generated Framework distributions;
- do not introduce a second content-width setting;
- do not commit, merge, tag, or publish a public release in this batch;
- local `docara.test` publication must retain a rollback copy.

## Batch Plan

| Batch | Goal | Work | Verification | Status |
| --- | --- | --- | --- | --- |
| 1 | Correct the canonical grid | Update the page template, shell CSS, and narrow regression assertions | Focused tests and `git diff --check` | completed |
| 2 | Rebuild and accept | Build the documentation site, publish locally, inspect desktop/mobile and both themes | Static verification and browser evidence | completed |

## Progress

### Batch 1

- Status: completed.
- Done:
  - added the Framework `w-full` utility to the shared header, documentation
    grid, landing content, and footer containers;
  - retained the site-level `max-container-7` modifier as the only maximum
    width setting;
  - replaced the raw `12rem`, `15rem`, and `18rem` grid dimensions with
    `--sf-f2`, `--sf-f4`, and `--sf-f8`;
  - added regression assertions for the container and grid contracts.
- Verification:
  - `PortableSiteBuilderTest`: 35 tests, 843 assertions;
  - `PortableDocumentationSiteTest`: 1 test, 66 assertions;
  - `git diff --check`: PASS.
- Remaining: none.

### Batch 2

- Status: completed.
- Done:
  - rebuilt all 90 source pages;
  - published the result to the ServBay document root;
  - retained a local rollback copy;
  - inspected the target component page at mobile and desktop widths.
- Verification:
  - full PHPUnit: 331 tests, 5,124 assertions;
  - static verifier: 198 HTML files and 14,236 local references, no broken
    references;
  - source and served build digest:
    `add421c8a45640d1905985373a1366887a69b82e90ed9114ec47be28c3a5d268`;
  - HTTP: `/`, `/ru/`, and the target component page return 200;
  - 390px: sidebar and outline hidden, horizontal overflow 0;
  - 1280px: columns 288px / 706px / 192px;
  - 1440px: columns 288px / 864px / 192px, horizontal overflow 0;
  - 1920px: bounded 1664px grid, columns 288px / 1088px / 192px,
    horizontal overflow 0;
  - light and dark themes retain valid surfaces and text contrast.
- Evidence:
  `source/workflow/evidence/2026-07-25-docara-docs-layout-proportions/acceptance.md`.
- Remaining: none.

## Final Result

- Result: PASS. Documentation pages now use one coherent Framework container:
  the sidebar remains usable, the outline is compact, and the primary content
  receives the recovered width.
- Verification: tests, static references, deterministic served build, HTTP,
  responsive browser geometry, and both themes passed.
- Remaining: none in this scope.
- Follow-up: the existing Framework highlight chunk load error is unrelated to
  this layout change and remains a separate asset-runtime issue.
