# Exact candidate verification

Date: 2026-07-18
Candidate SHA: pending implementation commit; this file records the frozen
pre-commit tree and will be bound to the exact SHA after commit
Parent: `f51debe3e1def82d2dcf611bf820c4517cd65f8f`
Branch: `codex/docara-consolidation`

## Final pre-commit verification

- full PHPUnit on ServBay PHP 8.2: **PASS**, 424 tests and 1919 assertions;
- focused Init/portable/static-verifier/template-mirror/Vite/Framework suite:
  **PASS**, 164 tests and 1267 assertions;
- Laravel Pint: **PASS**;
- canonical and generated-mirror workflows: ordinary `actionlint` **PASS**;
- documentation build: **PASS**, 39 HTML and 398 quoted local references;
- documentation deterministic tree hash twice:
  `abec753f99654bbe0a57c6b4c117c25fc71a5e9e131a9778059e0ace2f7c6f9a`;
- the portable PHP-only route has a passing pre-freeze proof; its 3-page,
  7-reference deterministic proof is repeated from the exact commit archive
  before the final evidence closure.

The exact SHA, commit cleanliness, final diff/hygiene results, independent
verdict, and local deployment evidence are appended only after their
respective gates complete.

## Nonclaims

- no new Docara release or tag was created;
- the generated external mirror was not published;
- consumer default branches were not migrated and their locks do not contain
  the new Docara candidate;
- `docara-mix` was not archived and is not retirement-ready;
- tabs remain a visible exact Simai Framework contract blocker;
- local `docara.test` acceptance is not production, release, or ecosystem
  readiness.
