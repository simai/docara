# Goal 2-C — preview and design validation correction

Date: 2026-08-03
Status: `ready_for_independent_audit`
Track: Docara extensible LEGO architecture
Current goal: Goal 2 correction
Input revision: `4c0f623403773c00669ef53782785d750839de0a`
Rejected implementation candidate: `33a377758f12d02a34e50c2f4f6d2aa760cf678b`
Independent audit marker: `019fc4b1-57f1-7f10-a7e0-92a9322f4bee`
Parent workflow: `source/workflow/2026-08-03-docara-goal2-design-registry-preview.md`
Evidence: `source/workflow/evidence/2026-08-03-docara-goal2c-preview-validation-correction/INDEX.md`

## Goal

Keep the accepted single DesignRegistry and production rendering path while
making preview purpose fail-closed for production verification, publishing a
self-contained preview runtime, tracking the real selected-target dependency
graph, and rejecting invalid View Trees before registration.

## Done When

- production/static/release verification rejects preview-purpose output with a
  stable machine error while accepting ordinary full builds;
- the documented preview root serves production HTML and every required local
  asset without a neighbouring build tree;
- dependency closure contains only effective page/config/design/Smart/template/
  asset inputs and watch rebuilds exactly once only for relevant change;
- invalid View Tree kind/tag/attribute/utility/region/slot fails before
  registration while descriptor-owned dynamic regions remain valid;
- one parser, renderer registry, Smart gateway, LayoutComposer and PageBuilder
  remain; Goal 1 regressions and public full/single parity stay green;
- complete focused/full/static/browser/graph/context evidence is bound to the
  correction candidate; Goal 2 returns to `ready_for_independent_audit` and
  Goal 3 remains unstarted.

## Boundaries

Allowed: repository runtime, tests, schemas, public developer documentation,
specification, graph, workflow, handoff and compact evidence required by this
correction.

Forbidden: Goal 3 SDK/MCP, second preview renderer/Gateway/composer/PageBuilder,
arbitrary project executable/template paths, external owner repositories,
`docara.test`, `docara-new.test`, merge, push, tag, release or deploy.

Rollback: revert correction commits in reverse order to exact input revision.
The rejected evidence remains immutable history and is marked superseded rather
than rewritten.

Federation note: the central route selected the disabled stale Docara skill and
returned `needs_preparation`; per the repository/user boundary this workflow
uses the raw repository specification and the enabled teamlead/dev/tester/graph
fallback instead of enabling or using that skill.

## Batch plan

| Batch | Outcome | Verification | Status |
| --- | --- | --- | --- |
| C2.0 | recovery, exact defect reproductions, correction state | four RED regressions and durable inventory | complete |
| C2.1 | typed preview purpose and receipt boundary | preview verifier non-zero; full verifier pass | implementation green |
| C2.2 | self-contained isolated preview runtime | all preview HTTP assets 200/correct MIME; browser clean | complete |
| C2.3 | actual target dependency graph/watch | unrelated=0; relevant edit/create/delete=1 | implementation green |
| C2.4 | registry-time View Tree validation | invalid constructs fail; dynamic region passes | implementation green |
| C2.5 | full retest, documentation, graph and handoff | complete Goal 2 matrix, clean worktree | complete |

## Current progress

- Exact branch/HEAD/worktree recovery passed; input worktree was clean.
- Independent audit defects are accepted as the RED baseline.
- The four rejected outcomes are now permanent focused regressions. Focused
  preview/design/static verification passes 30 tests and 246 assertions on the
  working candidate; the broader preview/design/declarative suite passes 113
  tests and 1,572 assertions.
- Exact preview HTTP/browser acceptance and the full Goal 2 regression and
  governance contour are complete on the candidate below.

## Final result

Exact product/docs candidate:
`39f1e3f6e97d7f8138e892b5884ba194cc889a7f`. Full PHPUnit, two-build
determinism, full/single equality, static verification, exact isolated preview
HTTP/browser, Goal 1 cross-host and security regressions are green. Detailed
commands, counts and hashes are in the evidence index.

The next action is only `independent_goal2_reverse_outcome_audit`. Goal 2 is
not self-accepted; Goal 3 remains unstarted and unauthorized.

## Remaining

Only independent audit remains. No implementation batch or Goal 3 work is
authorized from this workflow.
