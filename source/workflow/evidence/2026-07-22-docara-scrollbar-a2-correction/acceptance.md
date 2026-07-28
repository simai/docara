# Docara scrollbar a2 correction acceptance

Date: 2026-07-22
Verdict: PASS
Scope: local Docara source, generated documentation build and `docara.test`

## Source contract

- `--sf-a2` resolves to `0.125rem`, or `2 px` at the Framework root size.
- Left navigation uses logical `padding-inline-end: var(--sf-a2)`.
- Right contents uses logical `padding-inline-start: var(--sf-a2)`.
- The marker uses `--sf-a2` for its width and a runtime-calculated logical
  offset for native scrollbar compensation.
- The CSS fallback remains logical and token-based when JavaScript is absent.

## Automated verification

- focused PHPUnit: `37 tests`, `811 assertions`, PASS;
- full PHPUnit: `623 tests`, `5568 assertions`, PASS;
- production documentation build: PASS;
- static verification: `271` HTML pages, `20512` local references, `0` broken;
- `git diff --check`: PASS.

One discarded full-suite attempt ran concurrently with another orphaned test
process and produced temporary-fixture deletion errors. Both processes were
stopped, and the clean isolated full run above passed. The discarded output is
not product evidence.

## Browser verification

Desktop viewport: `1440 x 600`.

- left rail Framework padding: `2 px`;
- left edge-to-scroll-container distance: `3 px` = divider `1 px` + `a2 2 px`;
- left navigation overflow: `228 px`, proving that the scrollbar is active;
- right rail Framework padding: `2 px`;
- right edge-to-scroll-container distance: `3 px` = divider `1 px` + `a2 2 px`;
- long contents overflow on `/ru/examples/`: `198 px`;
- active marker width: `2 px`;
- active marker displacement from divider: `0 px` with and without an active
  native scrollbar.

Mobile viewport: `390 x 844`.

- desktop sidebar and outline rails are hidden as designed;
- horizontal document overflow: `0 px`.

## Local publication and rollback

- served URL: `https://docara.test/`;
- active directory: `/Users/rim/Sites/docara.test/build_production`;
- rollback directory:
  `/Users/rim/Sites/docara.test/.docara-backups/scrollbar-a2-20260722-125627/build_production.previous`;
- local action-gate report:
  `source/output/action-gates/action-gate-report-20260722094326.json`.

## Nonclaims

No public push, merge, tag, package release, production deployment or
production-readiness claim was performed.
