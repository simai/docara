# Independent exact-candidate tester verdict

Date: 2026-07-18
Verdict: **PASS**
Candidate: `83d677c7eb5f22d9ca2f4ac16990fe16eddbe985`
Tree: `4956ff452b516ace2df744ee34b276881437adb9`

The tester worked from exact Git objects and changed no worktree file or ref.

## Reproducibility and automated checks

- deterministic release archive, embedded commit exact, SHA-256
  `97a86880db846ee63299312de669638cc083a189d3ab7064b844146d38ba6142`;
- focused portable contracts: 47 tests / 426 assertions;
- navigation precedence/reset matrix: 7 tests / 22 assertions;
- full PHPUnit: 432 tests / 2031 assertions;
- negative schema, pinning and branding subset: 6 tests / 44 assertions;
- Pint, Composer validate/platform, JSON and `git diff --check`: PASS;
- production build: 41 pages, 3574 references, zero broken, deterministic
  digest `94872dc8627fac21cbc5c0fed8f6a9515b7fdb35d7e75580b49656ce4162eccf`;
- starter build: 7 pages, 100 references, zero broken, deterministic digest
  `a8a1510700ec271151962c1e718e2362bb4565ac23ccd5338d749eba1c5438d9`;
- pinned Core `7e836d8a…`, Smart `dd786bba…`, no moving references.

## Browser and product contour

The exact live page SHA was `04cebca3…`. Mobile cold-load to same-tab desktop
resize, active link reveal, four depths, three opened ancestors, direct links
and disclosures, mobile Escape/focus return, decorative logo alts, brand name,
theme, manual rail scroll, page scroll, overflow and browser logs all passed.

All four Human-Centered Simplicity surfaces passed as necessary/protective:
`docs_navigation_tree`, `brand_configuration`,
`responsive_navigation_shell`, and `framework_theme_icons`.

Repository hygiene output is byte-identical to the accepted baseline: it still
contains only the two documented `CURRENT.yaml` policy conflicts and no new
blocker.

The verdict excludes search, right TOC, breadcrumbs, previous/next,
locale/version, reading settings, the expanded landing vertical, public
release and overall product readiness.
