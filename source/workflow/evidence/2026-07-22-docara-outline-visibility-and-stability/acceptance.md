# Docara outline visibility and stability acceptance

Date: 2026-07-22
Verdict: PASS
Scope: local Docara source, generated build and `docara.test`

## Findings closed

- `P1`: the active marker had valid computed geometry but was clipped by the
  scroll section and therefore not visible.
- `P2`: a short contents list moved `19 px` upward before its sticky constraint
  engaged, despite having no internal overflow.

## Implementation evidence

- The full-height divider is an inset rail shadow.
- The contents section starts at the rail edge; its prior visual spacing is
  preserved inside the scroll container with Framework tokens.
- The active marker is now inside the clipping boundary and overlays the
  divider.
- The contents sticky offset is `4.5rem`, shared with documentation heading
  scroll margins.

## Automated evidence

- focused PHPUnit: `37 tests`, `810 assertions`, PASS;
- full PHPUnit: `623 tests`, `5567 assertions`, PASS;
- production documentation build: PASS;
- static verification: `271` HTML pages, `20512` local references, `0` broken;
- `git diff --check`: PASS.

## Browser evidence

Target: `https://docara.test/ru/migration/legacy/`
Desktop viewport: `1440 x 600`.

At the top of the page:

- contents internal overflow: `0 px`;
- contents section top: `72 px`;
- marker position: exactly the rail divider (`0 px` displacement);
- visible marker size: `2 x 36 px`;
- visible marker color: `rgb(172, 199, 255)`.

After scrolling the document by `650 px`:

- contents internal overflow remains `0 px`;
- contents section top remains `72 px`;
- active item changes from `Перед началом` to `Перенесите проект`;
- its blue marker remains visible with `0 px` divider displacement.

Overflow case: `https://docara.test/ru/examples/`.

- contents overflow: `180 px`;
- native scrollbar width: `14 px`;
- scrollbar remains on the physical right;
- active marker remains on the physical left divider.

Mobile viewport: `390 x 844`.

- desktop rails hidden;
- horizontal overflow: `0 px`;
- console errors: none.

## Simplicity evidence

The correction needs no DOM marker element and no runtime geometry script. It
uses the existing Smart-component state, one Framework-token-based marker rule
and native sticky/overflow behavior.

## Publication and rollback

- served path: `/Users/rim/Sites/docara.test/build_production`;
- rollback:
  `/Users/rim/Sites/docara.test/.docara-backups/outline-visibility-20260722-134305/build_production.previous`;
- action gate:
  `source/output/action-gates/action-gate-report-20260722104305.json`.

## Not checked

No RTL documentation locale is present for visual browser acceptance.

## Nonclaims

No public push, merge, tag, package release, production deployment or
production-readiness claim was performed.
