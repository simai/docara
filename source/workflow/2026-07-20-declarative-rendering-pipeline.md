# Workflow: declarative rendering pipeline vertical slice

Date: 2026-07-20
Status: completed
Workflow ID: `2026-07-20-declarative-rendering-pipeline`
Process model: `general_delivery`
Current state: `evidence_recorded`
Target state: `evidence_recorded`
Parent track: `docara-consolidation`

## Goal

Implement the first Docara vertical slice of the shared declarative rendering
pipeline intended for later Larena consumption.

## Done When

- Markdown is parsed into an immutable typed `DocumentAst`; the new parser
  contains no HTML, CSS, or JavaScript.
- The `docara.docs` layout declares `header`, `sidebar`, `main`, `outline`, and
  `footer` regions through a validated descriptor.
- A page resolves through `Page -> Section -> Block -> Smart` into an immutable
  `ResolvedRenderPlan`.
- `ui.alert` resolves through a manifest, view, trusted template registry and
  presentation-only template, producing a render artifact with HTML, assets,
  and provenance.
- The new builder/orchestrator contains no HTML, CSS, or JavaScript.
- The legacy renderer remains present and unchanged as the accepted fallback.
- One representative page is rendered by both pipelines and a semantic parity
  assertion proves equivalent title, content, headings, regions, links, and
  Smart alert semantics.
- The same resolved fixture passes through a Larena contract adapter and
  produces an equivalent contract projection.
- Focused and full PHPUnit suites pass; deterministic static build and static
  verifier remain green.
- Independent reverse-outcome acceptance is recorded against the complete
  requirement matrix before the Goal is closed.

## Scope

Allowed:

- `src/Declarative/**`;
- narrow integration changes under `src/PortableSite/**`;
- `resources/layouts/**`, `resources/sections/**`, `resources/blocks/**`,
  `resources/smart/**`, and new schemas when required;
- focused fixtures and tests;
- current developer architecture documentation;
- this workflow, launch record, and evidence.

Forbidden:

- deleting or rewriting `PortableHtmlRenderer`;
- changing accepted legacy output without an explicit regression fixture;
- public release, push, merge, tag, or package publication;
- local `docara.test` publication before code/build acceptance;
- writes to Simai Framework, Bitrix, or Larena owner repositories;
- `.env`, secrets, database, ServBay, Git history, or destructive cleanup;
- arbitrary template paths/classes/callbacks from authored content.

## Batches

| Batch | Work | Verification | Status |
| --- | --- | --- | --- |
| 0 | Workflow, launch record, baseline and gate | project doctor, inventory | completed |
| 1 | Typed `DocumentAst` and parser | parser unit and negative tests | completed |
| 2 | Layout/regions and Page/Section/Block/Smart plan | schema/plan tests | completed |
| 3 | Trusted template registry and `ui.alert` artifact | template/security tests | completed |
| 4 | Builder shadow integration and semantic parity | integration fixture | completed |
| 5 | Larena contract adapter parity | adapter fixture | completed |
| 6 | Full regression, deterministic build, evidence | PHPUnit/build/verifier | completed |
| 7 | Independent reverse-outcome acceptance | requirement matrix verdict | completed |

## Design Invariants

1. HTML is a render artifact, never source-of-truth.
2. Parser produces typed nodes and provenance only.
3. Layout owns regions but not section internals.
4. Page references sections; section composes blocks; block binds data; Smart
   owns presentation.
5. Templates receive immutable view models and contain presentation only.
6. Assets are collected from manifests, not emitted from builder/parser.
7. Authored content cannot select an arbitrary executable file.
8. New and Larena-adapted projections derive from the same resolved plan.
9. Legacy renderer remains available until independent acceptance.

## Evidence

Root:
`source/workflow/evidence/2026-07-20-declarative-rendering-pipeline/`

Required:

- `baseline.md`;
- `verification-summary.md`;
- `requirement-matrix.md`;
- `kaizen.md`;
- `independent-acceptance.md`.

## Baseline

- worktree:
  `/Users/rim/Documents/GitHub/larena-workspace/source/worktrees/docara-consolidation`;
- branch: `codex/docara-consolidation`;
- starting revision: `922c00e`;
- initial unrelated change: the approved architecture proposal
  `source/workflow/2026-07-20-declarative-rendering-architecture.md`;
- project doctor: `PASS`, no findings or blockers;
- accepted legacy renderer remains
  `src/PortableSite/PortableHtmlRenderer.php`.

## Stop Conditions

- unrelated user work appears in overlapping files;
- semantic parity cannot be defined without changing accepted product behavior;
- required implementation expands into Framework/Larena owner writes;
- an arbitrary-template execution path is required;
- destructive, release, public publication, secret, or live runtime action is
  required.

## Current Progress

- Architecture audit completed and recorded.
- Federation route selected Larena with Docara, Simai Framework, developer and
  tester companion boundaries.
- Current clean implementation worktree and accepted legacy baseline verified.
- Immutable `DocumentAst`, declarative definitions, resolved plan, trusted
  `ui.alert` templates, semantic parity and Larena adapter are implemented.
- Builder integration is diagnostic-only: accepted legacy HTML remains the
  published result.
- Focused suite: 44 tests / 667 assertions.
- Full UTC suite: 568 tests / 4,625 assertions.
- Two disposable production builds are byte-identical with digest
  `ad232d46d29a9b39e67a542be424fe8e845a403d044dd5900a241568c440cdf9`.
- Each static verifier checked 66 HTML pages and 6,036 local references with
  zero broken references.
- Build diagnostics contain 44 rendered declarative pages with semantic and
  Larena adapter parity `pass`; three of them exercise `ui.alert`.
- Accepted legacy renderer SHA-256 remains
  `a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`.

## Next

Choose the next bounded vertical slice. The recommended direction is one
structural Smart-component plus declarative header/sidebar/outline composition.
Do not delete or switch away from the legacy renderer in that Goal.
