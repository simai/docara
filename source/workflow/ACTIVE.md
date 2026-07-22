# Active workflow: Docara search experience alignment

Date: 2026-07-22
Status: completed
Workflow ID: `2026-07-22-docara-search-experience-alignment`
Process model: `full_qa`
Current state: `review_ready`
Target state: `review_ready`

## Current goal

Bring Docara documentation search closer to the proven Retype interaction
without copying its implementation: use the Simai Framework modal overlay and
UI primitives, simplify the query control, highlight matches, preserve secure
same-origin index validation and provide keyboard, theme and responsive QA.

## Done when

- the modal backdrop is always a dark neutral scrim in light and dark themes;
- the query row is compact and built from Framework components/utilities;
- titles and excerpts visibly highlight matched query terms with semantic
  `mark` elements;
- results form one scan-friendly list rather than separate floating cards;
- keyboard open, navigation, activation, escape and focus restoration work;
- focused tests, exact build, static verification and desktop/mobile browser
  acceptance pass;
- rollback-safe local publication is visible at `https://docara.test/`.

## Current boundary

This batch may change Docara source, tests, workflow evidence and the local
`docara.test` build. Public push, merge, tag, package release and production
readiness are excluded.

## Framework mapping

- Smart component: `sf-modal` owns overlay, focus trap and modal lifecycle.
- Framework component: native `sf-icon-button` classes and the `sf-icon`
  Smart element own the close command.
- Framework component: size-1 bordered input anatomy owns the query field.
- Utilities: layout, spacing, surface, border, radius, typography and color.
- Product runtime: search index validation, scoring, highlighting and result
  navigation remain in `resources/portable/search.js`.

## Evidence

Evidence is written to
`source/workflow/evidence/2026-07-22-docara-search-experience-alignment/`.

## Result

- 123 focused tests and 1,343 assertions pass;
- production build contains 271 pages and 20,512 valid local references;
- browser acceptance confirms 20 results, 22 semantic highlights, keyboard
  focus movement, a theme-independent black scrim and zero console warnings;
- the local build is published at `https://docara.test/ru/` with a retained
  rollback copy;
- public push, merge, tag, package release and production readiness remain
  outside this batch.

## Contextual excerpt correction

The 2026-07-22 follow-up fixes a semantic gap in result cards: ranking could
match page body text while the excerpt still showed the beginning of the page.
Each result now selects the best matching description, heading or body source
and centres its excerpt around the actual query occurrence. Live acceptance
for `устано` returned 15 results; every card contained a visible highlighted
match, including all 15 excerpts.

The search idle hint and keyboard-help footer were also corrected from the
Framework `label-small` (`1/4`, 10/12 px) to the project minimum
`label-medium` (`1/3`, 12/16 px). The input and 15-result `устано` flow remain
functional after the typography change.
