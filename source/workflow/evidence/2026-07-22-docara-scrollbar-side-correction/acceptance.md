# Docara scrollbar side correction acceptance

Date: 2026-07-22
Verdict: PASS
Scope: local Docara source, generated build and `docara.test`

## Scenario

A reader uses the desktop documentation layout with independently overflowing
left navigation and right contents. The navigation scrollbar must remain close
to its divider, while the contents scrollbar and active marker occupy opposite
physical edges of the contents rail.

## Automated evidence

- focused PHPUnit: `37 tests`, `810 assertions`, PASS;
- full PHPUnit: `623 tests`, `5567 assertions`, PASS;
- production documentation build: PASS;
- static verification: `271` HTML pages, `20512` local references, `0` broken;
- `git diff --check`: PASS.

## Browser evidence

Desktop viewport: `1440 x 600`.

- navigation scrollbar overflow: `228 px`;
- navigation scrollbar width in Chrome: `14 px`;
- navigation gap from the inner divider edge: `1 px`;
- navigation outer distance: divider `1 px` + gap `1 px` = `2 px`;
- contents overflow on `/ru/examples/`: `184 px`;
- contents scroll container direction: `ltr`;
- contents scrollbar: physical right edge;
- active marker: physical left divider;
- active marker displacement: `0 px`;
- active marker width: `2 px` (`--sf-a2`).

Mobile viewport: `390 x 844`.

- desktop rails hidden;
- horizontal overflow: `0 px`;
- browser console errors: none.

## Simplicity evidence

The correction removes runtime scrollbar-width measurement from `docara.toc`.
The result uses Framework tokens and native browser behavior: one token for the
menu gap, native right-side scrolling for contents, and one CSS marker rule.

## Publication and rollback

- served URL: `https://docara.test/`;
- active path: `/Users/rim/Sites/docara.test/build_production`;
- rollback:
  `/Users/rim/Sites/docara.test/.docara-backups/scrollbar-side-20260722-132315/build_production.previous`;
- action gate:
  `source/output/action-gates/action-gate-report-20260722102310.json`.

## Not checked

No RTL documentation locale is present in this build for visual browser
acceptance. The implementation nevertheless restores the configured document
direction on contents children while keeping the requested physical scrollbar
and marker sides.

## Nonclaims

No public push, merge, tag, release, production deployment or
production-readiness claim was performed.
