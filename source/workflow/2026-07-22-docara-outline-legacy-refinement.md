# Docara right outline refinement

Date: 2026-07-22
Status: implemented and locally verified
Workflow ID: `2026-07-22-docara-outline-legacy-refinement`
Baseline: `dda73e8a5188a4b177d5c5aa8bea29f51727fe9d`
Process model: raw-owner `docara + sf5 + ux + designer + tester`

## Goal

Bring the canonical `docara.toc` desktop outline back to the useful density
of the legacy Docara surface while preserving the declarative publisher,
Simai Framework contract, mobile touch targets, RTL and accessibility.

## Evidence baseline

At a 1440x900 desktop viewport the legacy outline renders 14px / 24px links
inside approximately 36px rows and marks the current heading on the vertical
rail. The current publisher renders 16px links with 16px block padding, which
produces 56-104px rows, and its component JavaScript does not expose an active
heading state.

## Intended change

- keep layout ownership in the `outline` region and visual/runtime ownership
  in the official `docara.toc` Smart component;
- use Framework typography and spacing utilities for the compact link rhythm;
- let the component expose one `aria-current="location"` item while scrolling;
- render a token-based active marker over the existing logical-start rail;
- keep mobile links at a minimum 44px touch target and preserve visible focus;
- add exact build and browser evidence before declaring completion.

## Boundaries

No legacy renderer edits, page-specific CSS, public push, merge, tag, package
release or production readiness claim. Federation routing was unavailable due
to installed graph/skill symlink mismatches, so the selected raw owner skills
remain authoritative and the mismatch is recorded as a graph gap.

## Result

The canonical `docara.toc` now matches the useful legacy density on desktop:
14px text in 36px rows. Its own runtime maintains exactly one
`aria-current="location"` link, updates it while scrolling and projects a
2px token-based marker onto the logical-start rail. Mobile retains the 44px
minimum target and the existing sheet behavior.

Full acceptance passed at 618 tests / 5,465 assertions. The exact verified
build is published at local `docara.test` with a rollback backup. Evidence is
stored under
`source/workflow/evidence/2026-07-22-docara-outline-legacy-refinement/`.

## Kaizen

The initial utility selection used non-existent `px-*` / `py-*` aliases and
was caught by computed-style browser evidence. The accepted template uses the
real Framework `p-1/3` utility. Future component spacing changes should verify
both registry presence and computed values before acceptance.
