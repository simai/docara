# Acceptance: Docara reading navigation layout

Date: 2026-07-22
Candidate baseline: `7bca7d0`
Verdict: PASS WITH EXTERNAL-TEST NOTE

## Scope and scenario

Checked the declarative Docara documentation shell at
`https://docara.test/ru/migration/`: full-height desktop sidebar separator,
next-card logical-end spacing, mobile previous/next priority, relations,
overflow, exact build and static output.

## Source and build evidence

- focused PHPUnit: 2 tests, 558 assertions, PASS;
- production build: PASS;
- static verifier: 271 HTML pages, 20,512 local references, 0 broken;
- `git diff --check`: PASS;
- generated shell uses the full-height rail/sticky-inner pattern already used
  by the outline rail;
- next card uses Framework utilities `p-y-2`, `p-inline-start-2` and
  `p-inline-end-1`; no new spacing value was introduced.

## Browser evidence

Desktop default viewport (`1296 x 713`):

- layout height: `2075 px`;
- sidebar rail height: `2075 px`, `align-self: stretch`;
- sidebar content remains sticky and viewport-bounded;
- next logical-end padding: `16 px`, reduced from `20 px`;
- at the bottom of the page the sidebar and layout both end at the viewport
  bottom;
- horizontal overflow: `0`;
- browser console errors: none.

Mobile (`390 x 844`):

- next card top: `1798.5 px`;
- previous card top: `1876.5 px`;
- next is visually first and previous second;
- `rel="next"` and `rel="prev"` remain intact;
- horizontal overflow: `0`.

## Regression note

The full 623-test run produced one external-only error in
`RemoteCollectionsTest::items_key_in_config_can_fetch_content_from_a_remote_api`:
`SSL: Handshake timed out` while reading `jsonplaceholder.typicode.com`.
The exact test immediately passed on retry (1 test, 1 assertion). No changed
file participates in that remote collection path.

## Publication and rollback

- deployed output: `/Users/rim/Sites/docara.test/build_production`;
- rollback copy:
  `/Users/rim/Sites/docara.test/.docara-backups/reading-navigation-20260722-070833/build_production.previous`;
- action-gate report:
  `source/output/action-gates/action-gate-report-20260722070833.json`.

## Simplicity verdict

PASS. The correction adds no controls, JavaScript, configuration, dependency
or new product class. It reuses the existing rail composition and Framework
spacing utilities while preserving accessibility and navigation semantics.
