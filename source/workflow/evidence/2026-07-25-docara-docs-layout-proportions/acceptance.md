# Docara documentation layout proportions acceptance

Date: 2026-07-25
Verdict: PASS

## Candidate

- worktree:
  `/Users/rim/Documents/GitHub/larena-workspace/source/worktrees/docara-consolidation`;
- local site: `/Users/rim/Sites/docara.test/build_production`;
- target:
  `https://docara.test/ru/components/catalog/native.headings_and_text/`;
- served build digest:
  `add421c8a45640d1905985373a1366887a69b82e90ed9114ec47be28c3a5d268`.

## Contract

- all shared page containers combine `container` with the Framework `w-full`
  utility;
- `max-container-7` remains the only configured maximum site width;
- navigation uses `minmax(var(--sf-f4), var(--sf-f8))`;
- the outline rail uses `var(--sf-f2)`;
- raw layout widths `12rem`, `15rem`, and `18rem` are absent from the
  documentation grid contract.

## Automated checks

- focused PHPUnit: 36 tests, 909 assertions;
- full PHPUnit: 331 tests, 5,124 assertions;
- static site build: 90 source pages;
- static verifier: 198 HTML files, 14,236 local references, zero broken
  references;
- `git diff --check`: PASS;
- source build and served build digests are identical.

## Browser matrix

| Viewport | Grid | Main content | Result |
| --- | --- | --- | --- |
| 390x844 | one column | 366px grid; rails hidden | PASS |
| 1280x720 | 288px / 706px / 192px | 658px actual content | PASS |
| 1440px | 288px / 864px / 192px | 816px actual content | PASS |
| 1920px | 288px / 1088px / 192px | 1040px actual content inside 1664px max container | PASS |

Light and dark themes retain valid Framework surfaces and text colors.
Horizontal overflow is zero in the accepted matrix.

Screenshots:

- mobile:
  `.playwright-cli/page-2026-07-25T19-50-40-379Z.png`;
- desktop 1440:
  `.playwright-cli/page-2026-07-25T19-51-17-310Z.png`;
- desktop 1920:
  `.playwright-cli/page-2026-07-25T19-51-19-889Z.png`.

## Publication and rollback

- ServBay publication root: `/Users/rim/Sites/docara.test/build_production`;
- rollback copy:
  `/Users/rim/Sites/docara.test/.docara-backups/docs-layout-20260725-224652/site.previous`;
- HTTP smoke: root, Russian locale, and target component page return 200.

## Non-blocking existing issue

The browser console still reports an unrelated Framework highlight-component
CDN chunk load failure for `22635021162243.js`. No highlight/runtime asset
changes were made in this batch; the issue is not caused by the grid contract
and remains outside this acceptance.
