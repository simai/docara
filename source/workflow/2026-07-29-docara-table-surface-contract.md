# Docara table surface contract

Date: 2026-07-29
Status: complete_pass
Workflow ID: `2026-07-29-docara-table-surface-contract`
Track: docara-consolidation

## Outcome

Markdown tables now use one shared comfortable-density surface: the scroll
container owns the outline and rounded corners, header cells use the Framework
surface-container color and receive a larger vertical inset, while body cells
remain compact enough for reference data. The experimental compact density was
removed. The semantic table markup and responsive horizontal scrolling remain
unchanged.

The desktop navigation rail no longer adds an outer inline-start inset. Its
selected surfaces now begin on the same vertical guide as the brand mark, while
the text hierarchy keeps its own component-level indentation.

## Human-centered simplicity review

- Primary outcome: readers can immediately distinguish the header and perceive
  the table as one complete block.
- Simplest complete change: shared CSS rules on the existing
  `data-docara-table-scroll` contract; no new component or JavaScript.
- Protected complexity: semantic table markup, horizontal overflow and theme
  tokens remain Framework-owned.
- Complexity delta: no new runtime state and no page-specific styling.

## Verification

- targeted PHPUnit: `90` tests, `1,158` assertions — PASS;
- full production build: `100` canonical pages — PASS;
- static verifier: `200` HTML pages, `17,739` local references, `0` broken — PASS;
- table density: one comfortable default; compact alternative removed — PASS;
- browser at `1440px`, light and dark: `12px` radius, `1px` cell dividers,
  `16px` header block padding, `12px` body block padding — PASS;
- navigation active-surface start minus brand-mark start: `0px` — PASS;
- mobile `390px`: no page overflow; documentation sidebar is hidden by the
  responsive shell — PASS;
- local publication batch: `table-menu-align-20260730-021454`;
- independent backup:
  `/Users/rim/Sites/docara.test/.docara-backups/table-menu-align-20260730-021454/build_production`;
- same-filesystem rollback tree:
  `/Users/rim/Sites/docara.test/.docara-staging/table-menu-align-20260730-021454/served-before`.

## Boundary

Only the shared table surface, the desktop navigation-rail inset, their
regression assertions, the prototype and the generated local documentation
were changed. No merge, tag, package release or public deployment was
performed.
