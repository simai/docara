# Workflow: Docara reading navigation layout

Date: 2026-07-22
Status: completed — accepted with external-test note
Baseline: `7bca7d0`
Primary owner: `docara`
Companions: `sf5`, `ux`, `tester`, `ops`
quality_controls: human_centered_simplicity
simplicity_review: source/workflow/evidence/2026-07-22-docara-reading-navigation-layout/human-centered-simplicity-review.json
simplicity_repository_refs: repo://docara-consolidation
simplicity_repository_baselines: repo://docara-consolidation@7bca7d0d8535bd8c03d36fa07146d4dcf016fc93

## Primary user outcome

The reader reaches the end of a documentation page and sees an unbroken
desktop column separator, an optically aligned next-page affordance, and a
mobile order that prioritizes continuing forward before going back.

## QA plan and scope matrix

| Surface | Baseline | Required result |
| --- | --- | --- |
| desktop sidebar separator | ends with the sticky sidebar box | fills the available reading viewport below the header |
| next card inline end | generic `p-2` leaves the forward arrow too far from the edge | Framework spacing scale supplies a smaller logical end padding |
| mobile previous/next | previous is visually first | next is visually first, previous second |
| semantics | `rel=prev/next` exist | links, labels, URLs and relations stay unchanged |
| responsive | cards stack | no overlap or horizontal overflow at 390 px |

## Role and access matrix

- Reader on desktop: scans the column boundary and uses either adjacent link.
- Reader on mobile: continues to the next page with the first visible card.
- Keyboard and assistive technology: both links remain reachable with their
  existing accessible names and `rel` semantics.

## Findings register

- `P2`: the sidebar separator is tied to content/sticky box height instead of
  the available reading viewport.
- `P3`: the forward icon has excessive logical-end spacing.
- `P2`: mobile visual priority contradicts the usual forward-reading action.

## Human-centered simplicity review

- changed surface inventory: one shell separator and two existing navigation
  links; no new controls, settings, dependencies or abstractions;
- necessity map: each change directly addresses one reported defect;
- removal review: no existing capability can be removed; both directions are
  protected reading navigation;
- simplest complete alternative: one min-height rule, one Framework padding
  utility substitution and one responsive order rule;
- progressive disclosure: not applicable; no content is hidden;
- complexity delta: zero new visible elements, zero JavaScript and zero new
  product classes;
- protected complexity: link semantics, keyboard focus, responsive stacking
  and rollback remain intact;
- automation review: no automation introduced;
- scenario evidence: focused tests, full regression, exact build, static
  verification and desktop/mobile browser measurements;
- tester evidence review: source guards, exact static build and measured
  desktop/mobile scenarios confirm all three requested outcomes;
- residual complexity: CSS visual order on mobile must be verified against the
  existing semantic DOM order;
- blocking findings: none at intake;
- verdict: PASS WITH EXTERNAL-TEST NOTE.

## Readiness matrix

- source regression guards: PASS;
- focused PHPUnit: PASS — 2 tests, 558 assertions;
- full PHPUnit: 622 unaffected tests passed; one remote API test received a
  transient SSL handshake timeout, then passed alone — 1 test, 1 assertion;
- exact build and static verification: PASS — 271 HTML pages, 20,512 local
  references, 0 broken;
- desktop browser acceptance: PASS;
- mobile browser acceptance at `390 x 844`: PASS;
- local publication backup and rollback: PASS.

## Exclusions

- no navigation hierarchy, text, URL or locale changes;
- no Framework source/revision change;
- no public push, merge, tag, release or production-readiness claim.
