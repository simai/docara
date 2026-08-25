# Workflow: Shared examples and translation tracking

Date: 2026-08-25
Status: completed
Owner: Docara
Companions: Development, Content, Tester, Mirai Graph, Teamlead

## Goal

Implement project-owned reusable examples and non-blocking translation
freshness tracking in Docara 2, adopt both capabilities in `ui-doc`, update the
English Docara skill and synchronize the project technology context.

## Done When

- External `:::example {id="..."}` sources under project `examples/` work in
  full and single-page builds without changing inline example behavior.
- Example paths, files and assets are confined and a deterministic example
  receipt is generated.
- Translation status and hash-bound acceptance commands implement the approved
  page and `lang.json` states without mutating sources during build/status.
- `ui-doc` uses project-owned examples, has translation tracking enabled, and
  produces a reconciled report for its RU/EN corpus.
- Product docs, specification, English skill, Mirai Graph and project memory
  describe the same verified behavior.
- Tests, static verification and representative browser smoke are green, or a
  precise external blocker is recorded.

## Constraints And Risks

- Preserve all unrelated dirty-worktree changes in every repository.
- Do not modify `ui-play`.
- Do not commit, push, tag, publish, release or deploy.
- Do not rewrite historical workflow/evidence; mark superseded routing only in
  current state.
- Build and status remain read-only for Markdown and the translation lock.
- Skill source changes require the Federation Skill Sync Gate.

## Accepted Architecture

- Project examples live under the fixed project-root `examples/` directory.
- External example IDs are slash-separated directory paths; `index.html` is
  required and `index.css`, `index.js`, and `assets/` are optional.
- Inline and ID-based examples are mutually exclusive and inline examples stay
  backward compatible.
- Translation tracking is configured independently through
  `translation_tracking`; the `ui-doc` source locale is `ru` and mode is
  non-blocking `report`.
- Freshness and editorial review are separate: accepted review levels are
  `ai_verified` and `human_reviewed`.
- Build/status never call AI or edit authored sources.

## Batch Plan

| Batch | Work | Verification | Status |
| --- | --- | --- | --- |
| 1 | Freeze current state, workflow and reusable owner patterns | Route, repo diff, technology/hygiene status | completed |
| 2 | Implement external examples and receipts | Unit, negative, full/single-page tests | completed |
| 3 | Implement translation config, report and accept CLI | Unit, integration, deterministic and no-write tests | completed |
| 4 | Update Docara docs/specification | Docs build and static verification | completed |
| 5 | Adopt examples and translation tracking in `ui-doc` | Corpus reconciliation, build, static and browser smoke | completed |
| 6 | Update English Docara skill and graph/project memory | Skill Sync Gate, graph/project-context checks | completed with external validator blocker recorded |
| 7 | Final outcome-integrity review | Allowlist diff and nearest regression | completed |

## Frozen Acceptance Corpus

- Existing inline Example renderer and code-block regression tests.
- New external example positive/negative and dependency tests.
- Translation pairing, status, `lang.json`, hash and stale-plan tests.
- Docara documentation production build and `verify-static`.
- Full `ui-doc` production build, `verify-static`, report reconciliation and
  representative RU/EN desktop/mobile light/dark browser smoke.
- Cross-repository diff review proving no `ui-play`, release or deployment
  mutation.

## Progress

### Completed outcome

- The product supports confined, receipted shared examples and report-only,
  hash-bound translation tracking without changing inline examples.
- Docara documentation built 127 Markdown routes and verified 261 HTML pages
  with no broken local references.
- `ui-doc` migrated 221 consumers to 215 shared examples, built all 939
  Markdown routes, and verified 1,692 HTML pages and 359,545 local references.
- The translation report reconciles 509 RU and 430 EN pages without duplicate
  keys; ambiguous/missing relations remain an explicit review queue.
- Browser acceptance covered RU/EN, desktop/mobile, light/dark propagation,
  JavaScript, local assets, and adaptive tab height.
- Project Technology sync/verify and Federation fast verification passed. The
  Skill Sync Gate passed, while the skill repository's wider validator remains
  blocked by three pre-existing untracked recipes with unsupported schemas.

## Final Result

- Result: implemented and verified
- Verification:
  `source/workflow/evidence/2026-08-25-shared-examples-translation-tracking/verification-summary.json`
- Remaining: no active implementation; translation review requires an
  explicit user-selected batch
- Publication: explicitly out of scope
