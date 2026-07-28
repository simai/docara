# Replacement-ready QA plan

Status: approved for autonomous execution by the active Goal
Date: 2026-07-20

## Intake

- Target: portable Docara replacement readiness.
- Repository:
  `/Users/rim/Documents/GitHub/larena-workspace/source/worktrees/docara-consolidation`.
- Target runtime: `https://docara.test/`.
- Reference runtime: `https://docara-legacy.test/en/`.
- Reference source: retained legacy stubs and detached main worktree, read-only.
- Primary users: documentation reader, portable-site author, legacy migrator,
  maintainer.
- Safe-write boundary: current worktree, disposable exact archive/build trees,
  and `docara.test` only after a separate local runtime preflight.
- Test data: no persistent user/content data is required.
- Cleanup: temporary archive/build/browser directories are disposable;
  publication keeps one documented rollback.
- Out of scope: public release, default branches, tags, pushes, repository
  retirement, Framework owner writes, production-readiness claims.

## Coverage

| Layer | Scope | Required evidence |
| --- | --- | --- |
| L0 | PHP/runtime, clean worktree, source policy | command and gate output |
| L1 | source, schemas, unit/full tests, docs, secrets/hygiene | exact commands |
| L2 | legacy migration/redirect contract | capability ledger and route corpus |
| L3 | generated route/link/404 crawl | static verifier plus redirect assertions |
| L4 | representative browser flows | screenshots/DOM/console |
| L5 | search, menus, TOC, dialogs, code copy | real actions and state assertions |
| L6 | anonymous public surface | N-A for authentication; negative external redirect checks |
| L7 | JSON/Markdown/lock source-of-truth | exact source-to-output assertions |
| L8 | legacy reference invariants | reference block map and comparative evidence |
| L9 | 1440/768/390, keyboard/focus/accessibility | browser/UX gate |
| L10 | URL migration/public metadata | redirects and 404; public SEO release remains N-A |
| L11 | path/URL/XSS/schema safety | negative tests and static review |
| L12 | build determinism and bounded asset/runtime checks | two clean exact builds |
| L13 | landing/catalog/search/theme/navigation regressions | representative matrix |
| L14 | fix/retest and independent exact-candidate acceptance | bound tester verdict |

## Acceptance Criteria

- AC-01: every retained legacy capability has one explicit disposition and
  usable migration guidance.
- AC-02: every required legacy route resolves to accepted content or a
  deterministic safe redirect.
- AC-03: locale/version behavior is explicit, schema-backed and documented.
- AC-04: desktop shell is content-first while active trail, search, settings,
  breadcrumbs, TOC and previous/next remain available.
- AC-05: mobile navigation/outline overlay does not reflow the article and has
  complete keyboard/focus behavior.
- AC-06: code blocks have one visual surface, language and accessible copy
  behavior without page overflow; syntax highlighting and line numbers are
  not claimed until separately admitted.
- AC-07: Framework/runtime provenance remains immutable and source-backed.
- AC-08: docs, schemas, static verifier and tests agree.
- AC-09: one exact archive produces deterministic builds with zero required
  broken references.
- AC-10: the same candidate passes complete-diff HCS, comparative UX/design,
  browser and independent tester gates.
- AC-11: accepted local publication has matching source/staging/served digests
  and a verified rollback.

## Execution Order

1. Baseline and matrices.
2. Capability/redirect/locale decisions.
3. Schema and negative tests.
4. Generator/renderer/UI implementation.
5. Documentation and focused regression.
6. Exact archive and deterministic/full checks.
7. Comparative browser/UX/design/HCS.
8. Independent tester.
9. Local runtime preflight/publication/served smoke.

## Approval

The user activated the exact Goal and requested continuation toward its full
outcome. This satisfies the plan execution gate for reversible repository and
disposable local checks. Local served-site replacement still requires its own
backup/rollback preflight. Public/release/destructive actions remain forbidden.
