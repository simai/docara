# QA plan

## Objective

Prove that the accepted component prototype and the generated Docara reference
describe the same public system and that its examples work in the browser.

## Checks

- exact prototype-to-runtime scope matrix;
- renderer and parser tests for every newly supported capability;
- catalog projection and route tests;
- two byte-identical production builds;
- static link and asset verification;
- visual and interaction checks in light/dark at 1440 px and 390 px;
- copy, tabs, disclosure and interactive component behavior;
- `git diff --check` limited to the batch changes.

## Stop conditions

- a prototype capability has no explicit disposition;
- a page claims support that the runtime cannot reproduce;
- a generated page has missing assets or broken interactions;
- an unrelated dirty-worktree file would need to be overwritten.
