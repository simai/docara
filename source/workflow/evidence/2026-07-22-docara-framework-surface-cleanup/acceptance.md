# Acceptance

Date: 2026-07-22
Verdict: PASS

## Automated verification

- focused publisher scenario: PASS, 1 test / 442 assertions;
- Markdown renderer and effective catalog: PASS, 52 tests / 819 assertions;
- full PHPUnit before the final compatibility rule: PASS, 618 tests / 5,458
  assertions;
- final full PHPUnit after all source changes: PASS, 618 tests / 5,459
  assertions;
- production documentation build: PASS;
- static verification: 271 HTML pages, 20,569 local references, zero broken;
- JSON language packs, PHP syntax and `git diff --check`: PASS.

## Browser acceptance

Route:
`https://docara.test/ru/authoring/layout-and-navigation/hierarchy/four-level/`

At 390 x 844:

- visible path is `Docara -> ... -> Четыре уровня навигации`;
- breadcrumb box is 362 x 44, `scrollWidth` equals `clientWidth` before
  disclosure;
- no page-level horizontal overflow;
- ellipsis accessible name is localized in Russian;
- activating ellipsis reveals all five levels;
- the expanded full chain scrolls within its own navigation region and still
  does not create page overflow.

At 1440 x 900:

- root, ellipsis and current page remain visible;
- `aria-current="page"` remains on `Четыре уровня навигации`;
- no page-level overflow;
- browser console errors: none.

## Local publication and rollback

- local target only: `/Users/rim/Sites/docara.test/build_production`;
- accepted served tree hash:
  `9702c51db66373b850189adf9a2f48841e87f24148defe13be1b4cde66f813c5`;
- immediate backup hash:
  `cdcd3998fe7d9cad451d7081ff4c80d3109994b2a3517a875c08d3656835cb72`;
- rollback source:
  `/Users/rim/Sites/docara.test/.docara-staging/framework-surface-20260722-004632/served-before`;
- static verifier and HTTPS smoke passed after atomic swap;
- ServBay configuration, secrets, public branches, tags and releases were not
  changed.
