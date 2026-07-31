# Docara example component integration

Date: 2026-07-29
Status: complete
Workflow ID: `2026-07-29-docara-example-component-integration`
Track: docara-consolidation

## Goal

Turn the accepted prototype into one reusable Docara example component and use
it on component-reference pages.

## Done When

- one surface switches between `Example` and one or more named source tabs;
- Markdown and HTML/CSS/JavaScript sources can be represented without placing
  preview and code side by side;
- result and source are projections of the same accepted input;
- source tabs retain syntax highlighting and a copy action;
- component pages read in the order: common example, parameters, compact
  variations;
- Russian UI uses `Пример`, not `Демо`;
- keyboard tabs, light/dark themes and mobile layout are verified;
- the production documentation build and static verifier pass;
- the accepted build is installed on `https://docara.test/`.

## Constraints

- The obsolete Docara skill is explicitly excluded.
- Existing unrelated worktree changes are preserved.
- No merge, tag, package release or public deployment is part of this batch.

## Batches

1. Reusable example surface and client behaviour.
2. Component-catalog composition and compact variation examples.
3. Documentation, tests, deterministic build, static and browser verification.

## Result

- Added the reusable `:::example` component with one `Пример` tab and one or
  more source tabs.
- A single accepted source now drives both the rendered preview and the copied
  code. Markdown is supported as one source; web examples support HTML with
  optional CSS and JavaScript tabs.
- Component-reference pages now present a common example first, parameters
  second, compact variations third, and limitations only when useful.
- The Russian interface uses `Пример` consistently instead of `Демо`.
- The verified deterministic build is installed on `https://docara.test/`.
- The example header now uses the official Framework underline-tabs structure:
  tab text is `sf-text-1`, lower corners are square, and the complete surface
  has the standard `space-1` bottom margin.

## Verification

- PHPUnit: `334/334`, `6675` assertions.
- Deterministic build: two builds, `265` files, byte-identical.
- Static verification: `200` HTML pages, `17730` local references, `0` broken.
- Browser: source-tab switching, copy-action visibility, alert icons, page
  ordering and console errors checked on the local site; console errors: `0`.
- Formatting, JSON parsing and scoped `git diff --check`: PASS.
- Post-correction browser metrics: `16px / 24px` tab typography, `0px` lower
  radii, `2px` underline and `16px` (`space-1`) bottom margin; tab switching
  and copy-action visibility PASS, console errors `0`.
- Evidence: `source/workflow/evidence/2026-07-29-docara-example-component-integration/verification.md`.
