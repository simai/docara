# Verification: Docara Smart search trigger

Date: 2026-07-22
Verdict: PASS

## Source contract

- the canonical header emits `sf-button` rather than a manually assembled
  native button;
- props: `size=1`, `type=outline`, `scheme=on-surface`,
  `icon-left=search`;
- the `kbd` hint is passed through `slot=icon-right`;
- no copied Framework `sf-button--*`, gap, alignment or radius classes remain
  in the product template;
- mobile product CSS only controls responsive visibility of the generated text
  container and shortcut.

## Automated checks

- focused Framework surface test: PASS, `1 test`, `9 assertions`;
- focused real builder test: PASS, `1 test`, `510 assertions`;
- full PHPUnit on ServBay PHP 8.2.29: PASS, `624 tests`, `5584 assertions`;
- exact production build from `docs/site`: PASS;
- static verifier: `271` HTML pages, `20512` local references, `0` broken;
- `git diff --check`: PASS.

## Compatibility

Search dialog IDs, forwarded ARIA/data attributes, search index/runtime,
responsive label hiding and theme integration remain unchanged. The immutable
legacy renderer was not edited.

## Nonclaims

The pinned Framework still renders desktop outline size `1` at 42 px rather
than its nominal 40 px and scales mobile rem primitives from a 14 px root.
Those producer defects are not corrected or compensated in this Docara batch.
