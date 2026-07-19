# Batch 9 — browser findability correction

Date: 2026-07-19
Starting candidate: `fe990afeb22c42b68ae498ae7104b304fc0b98d2`
Status: `CORRECTION_READY_FOR_EXACT_ACCEPTANCE`

## Why the first candidate was rejected

Native-Chrome acceptance of the exact starting candidate returned
`CORRECTION_REQUIRED` even though its automated exact-archive checks were
green:

1. the exact global-search query `расширение` returned no result while
   `/development/extensions/` existed;
2. `/components/catalog/` rendered all 17 records but had no catalogue filter.

The preliminary source/security review and automated exact results for that
candidate are superseded. The browser findings take precedence. No local
publication was attempted, and the accepted Batch 7 build remained served.

## Product correction

The development guide now uses the reader-facing word `расширение`, so the
existing deterministic search index can find the page by that exact query.

The generated component catalogue now has one responsive filter surface
derived from the same effective component entries:

- text search over localized title, description, ID, lifecycle, gap reason and
  limitations;
- type selection for Markdown, Docara components, Simai Framework
  Smart-components and planned capabilities;
- availability selection for supported and currently unavailable records;
- live visible/total count, empty state and one reset action;
- native labelled input/select controls, 44-pixel targets, focus-visible
  treatment and Escape-to-reset behavior.

Filtering introduces no second registry, no new component-readiness claim and
no Framework owner write. Item metadata is generated from the effective
catalogue record already used to render the visible card or gap disclosure.

## Test-first and build evidence

The correction began with three expected failures: the exact search term and
the Russian/English filter contracts were absent. The corrected tree passes:

- focused catalogue/documentation suite: 12 tests, 649 assertions;
- full sequential PHPUnit: 541 tests, 4,304 assertions;
- Pint: PASS;
- Composer strict validation: PASS;
- PHP syntax and `git diff --check`: PASS.

Two clean production builds are byte-identical:

- authored Markdown: 43;
- HTML pages: 56;
- search documents: 55;
- files: 66;
- catalogue records: 17;
- supported detail pages: 12;
- unavailable records: 5;
- static local references checked per build: 5,793;
- broken references: 0;
- canonical path-independent digest:
  `e60f6bbea7b59de84184025fe6322781db605067afb60ffbc2ea9cdf48576972`.

The digest uses sorted relative paths, a NUL separator and the raw SHA-256 of
each file, matching the previously documented canonical algorithm.

## Acceptance boundary

This record is preacceptance evidence, not the verdict. Freeze the commit that
contains this correction and rerun the complete exact-archive tester,
complete-baseline Human-Centered Simplicity/source/security review and
native-Chrome matrix at 1440, 768 and 390 pixels. The Goal and local
publication remain pending until all three independent gates pass on the same
SHA.
