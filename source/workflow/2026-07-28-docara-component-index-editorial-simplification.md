# Workflow: Docara component index editorial simplification

Date: 2026-07-28
Status: completed
Workflow ID: `2026-07-28-docara-component-index-editorial-simplification`
Track: `docara-consolidation`

## Goal

Make `/components/` a concise reader-facing entry point comparable in density
to the Retype component index: a title, one useful sentence and grouped links.

## Product Contract

- Keep all supported component detail pages and direct routes unchanged.
- Remove registry-oriented information from the overview: IDs, implementation
  families, long descriptions and the table itself.
- Group components by the task a documentation author is trying to solve.
- Keep technical facts on detail pages, where they help rather than distract.
- Use semantic headings and lists plus SIMAI Framework layout utilities.

## Done When

- all 28 supported components appear exactly once and link to their direct page;
- no unsupported component appears;
- the index has no table, technical IDs, family labels or repeated descriptions;
- Russian and English headings and introduction are short and natural;
- layout is 4 columns on wide screens, 2 on medium screens and 1 on mobile;
- focused tests, full tests, deterministic build, static verification and
  light/dark desktop/mobile browser checks pass;
- the verified result is installed on local `docara.test` with rollback evidence.

## Constraints

- Preserve the existing dirty worktree and unrelated user changes.
- The obsolete Docara skill is intentionally not used.
- The federation route selected an obsolete owner and no compatible local route;
  UX, design, content and repository-native development contracts are used as
  the safe raw-source fallback.
- No merge, tag, release or public deployment in this batch.

## Batches

| Batch | Result | Status |
| --- | --- | --- |
| 1 | Record the reader-facing index contract | completed |
| 2 | Replace the table with grouped semantic links | completed |
| 3 | Update RU/EN copy and regression tests | completed |
| 4 | Build, verify, browser-test and install locally | completed |

## Reference

- Retype component index: `https://retype.com/components/`.
- Existing accepted detail-page workflow:
  `source/workflow/2026-07-28-docara-component-reference-simplification.md`.

## Result

- The index shows four task-oriented groups and exactly 28 supported links.
- Technical IDs, implementation families, repeated descriptions and the table
  are absent from the overview.
- The short page no longer renders a duplicate right-hand table of contents.
- SIMAI Framework responsive utilities produce 4 columns on wide screens,
  2 at 800 px and 1 at 390 px.
- Russian and English use the same concise information architecture.
- The verified deterministic build is installed on local `docara.test`.

## Verification

- evidence:
  `source/workflow/evidence/2026-07-28-docara-component-index-editorial-simplification/verification.md`;
- PHPUnit: `333/333`, `6445` assertions;
- static verification: `220` HTML pages, `18445` local references, `0` broken;
- browser: dark and light themes; wide, 800 px and 390 px layouts; no page
  overflow; exact group and item counts confirmed;
- no merge, tag, release or public deployment was performed.
