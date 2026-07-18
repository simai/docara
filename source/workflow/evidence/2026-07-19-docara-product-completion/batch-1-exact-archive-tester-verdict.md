# Batch 1 independent exact-archive tester verdict

Date: 2026-07-19
Candidate: `d4ce688b38c6a29c2b57aaac2f8fe132f05b26b9`
Candidate tree: `a3eb29a7db0046e983f1a4c4c56aeb07bdae1024`
Baseline: `31f468be85d015b962fccc2b4c089204aab1410b`
Reviewer: `/root/menu_exact_tester`
Verdict: **PASS** for bounded navigation Batch 1

## Exact automated checks

- `git diff --check baseline..candidate`: PASS;
- reconstructed exact QA archive tree: `a3eb29a7…`, matching the candidate;
- focused PHPUnit: 1 test, 144 assertions, PASS;
- full PHPUnit: 432 tests, 2047 assertions, PASS;
- Pint: PASS;
- portable production build: PASS;
- static verifier: 41 HTML pages, 3574 local references, 0 broken.

Desktop semantic tree counts:

```text
Framework level classes 1 / 2 / 3 / 4: 8 / 31 / 1 / 1
active page: 1
current section: 1
ancestors: 2
contains-current disclosures: 3
aria-current="page": 1
```

## Exact browser checks

- Enter on a direct link navigates to the parent page;
- collapsing the current branch hides the current page but retains the current
  section, its 2px marker and the phrase `содержит текущую страницу`;
- reopening restores the active page;
- measured indentation X positions: 62 / 70 / 79 / 89;
- active reveal keeps the current page inside the desktop rail;
- light and dark themes retain the active states;
- desktop and 390 x 844 horizontal overflow: 0;
- mobile keyboard opening works; Escape closes the menu and returns focus to
  `Разделы`;
- browser console warnings/errors: 0.

## Archive packaging limitation

The committed `.gitattributes` intentionally excludes `/tests` from the normal
distribution archive. The tester therefore produced a disposable QA archive
with only `export-ignore` disabled and proved that its reconstructed Git tree
matches the candidate tree. Vendor was an external immutable test dependency;
Reflection confirmed that production classes and tests loaded from the archive.

This verdict accepts only Batch 1. It does not accept the whole Goal and does
not claim release, production or ecosystem readiness.
