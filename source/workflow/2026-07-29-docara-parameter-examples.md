# Workflow: Docara parameter examples

Date: 2026-07-29
Status: complete
Workflow ID: `2026-07-29-docara-parameter-examples`
Track: docara-consolidation

## Goal

Make component parameters easier to understand by showing a compact live
example and its exact authoring source directly inside the matching parameter
description. Use Badge as the reference implementation.

## Done When

- example sources can explicitly bind one example group to one declared
  parameter without changing variant-coverage evidence;
- unknown and duplicate parameter-example bindings fail closed;
- Badge shows separate examples for `type`, `scheme` and `size` under the
  matching descriptions;
- marker comments are absent from published source and generated HTML;
- focused tests, production build, static verification and browser review pass;
- the verified build is served locally on `docara.test` with rollback evidence.

## UX Contract

- the reader sees meaning, allowed values and default first;
- the visual example follows immediately, so no cross-page comparison is
  required;
- the existing Example/Markdown viewer is reused, keeping source optional and
  the page compact;
- parameter examples are not repeated in a detached generic variants section.

## Implementation Contract

- `<!-- docara-parameter:<name> -->` starts a parameter-bound example group;
- `<name>` must exactly match a declared authoring parameter;
- one parameter may have at most one example group;
- `docara-parameter` markers do not participate in `docara-variant` coverage;
- published Markdown strips both internal marker families.

## Batch Plan

| Batch | Work | Verification | Status |
| --- | --- | --- | --- |
| 1 | Add projector contract and fail-closed parsing | PHP lint and unit tests | complete |
| 2 | Add RU/EN Badge parameter examples | Generated detail assertions | complete |
| 3 | Build, verify and publish local site | static verification, browser review, HTTP smoke | complete |

## Constraints

- preserve unrelated dirty-worktree changes;
- use SIMAI Framework primitives and the existing portable example viewer;
- do not change Framework source or generated distribution in this batch;
- no merge, tag, release or public deployment.

## Progress

- Complete: parameter-bound example groups are rendered directly inside the
  matching parameter descriptions.
- Complete: Badge illustrates `type`, `scheme` and `size` in Russian and
  English sources.
- Complete: the implementation reuses the standard Example/Markdown viewer,
  including source visibility and the copy control.
- Next optional authoring work: add `docara-parameter` groups to other
  components where a visual parameter explanation materially helps readers.

## Final Result

- Result: `https://docara.test/ru/components/badge/` shows a live visual
  example and exact Markdown source under each of `type`, `scheme` and `size`.
- Contract: `<!-- docara-parameter:<name> -->` binds an example to a declared
  parameter; internal markers are removed from published output.
- Focused verification: `66` PHPUnit tests, `1879` assertions, PASS.
- Full verification: `336` PHPUnit tests, `6699` assertions, PASS.
- Production build: `100` pages, PASS.
- Static verification: `200` HTML pages, `17730` local references, `0` broken.
- Browser review: PASS at `1280x720` and `390x844`; three parameter examples,
  correct definition nesting, working Markdown tab and copy control, no page
  or example overflow, no browser console errors.
- HTTP smoke: `/ru/components/badge/` returns `200` and publishes all three
  parameter-example bindings.
- Action-gate evidence:
  `source/output/action-gates/action-gate-report-20260729074935.json`.
- Rollback:
  `/Users/rim/Sites/docara.test/.docara-backups/parameter-examples-final-20260729-111013/build_production.previous`.
