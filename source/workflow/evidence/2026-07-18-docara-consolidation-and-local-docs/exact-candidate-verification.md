# Exact candidate verification

Date: 2026-07-18
Candidate SHA: `4a312c1b14cf1e0ed0ad77d32e39b006b2ff9049`
Candidate tree: `baef56bd6d9a7955bbd4cda82c54282d4e7f64e9`
Functional parent: `725f22b9996b84640a115be63b86be57802477c5`
Base: `f51debe3e1def82d2dcf611bf820c4517cd65f8f`
Branch: `codex/docara-consolidation`

## Final pre-commit verification

- full PHPUnit on ServBay PHP 8.2 on the functional parent: **PASS**, 424 tests
  and 1919 assertions; the correction changes only `.gitignore` and two
  documentation pages;
- focused Init/portable/static-verifier/template-mirror/Vite/Framework suite:
  **PASS**, 164 tests and 1267 assertions;
- Laravel Pint: **PASS**;
- canonical and generated-mirror workflows: ordinary `actionlint` **PASS**;
- documentation build: **PASS**, 39 HTML and 398 quoted local references;
- documentation deterministic tree hash twice:
  `451224dd74da76301bad6888d65c5b42b708640d5aaed4b175a32f404aa63dc4`;
- portable PHP-only build from the exact clean candidate: **PASS**, 3 HTML and
  7 quoted local references;
- portable deterministic tree hash twice:
  `1f87d8a1d1c981cde386c8fbb896b68e1b82897cfce9606fae57f73479c5e15c`;
- independent exact-candidate correction retest: **PASS**;
- Human-Centered Simplicity exact-candidate review and tester verdict: **PASS**;
- local static deployment, HTTPS routes, desktop/mobile themes, console and
  network checks: **PASS**;
- served tree hash:
  `451224dd74da76301bad6888d65c5b42b708640d5aaed4b175a32f404aa63dc4`;
- timestamped rollback backup hash:
  `fdd1da06b21f165a7f3f601100c0eb9bf691e961b7e0f73ba795deb8150d2e8a`.

Independent verdicts, deployment details, and browser inventory are recorded
in sibling evidence files. The later evidence-closure commit is not a new
product candidate.

The central repo-hygiene checker has one pre-existing policy conflict: it
allows Docara `source/` but labels the two federation-required project-memory
`CURRENT.yaml` files as Larena-only. A clean clone of this exact accepted
candidate fails on the same two paths. The evidence closure records the gap and
does not claim that central check as PASS; all other hygiene/secret path checks
and `git diff --check` pass.

## Nonclaims

- no new Docara release or tag was created;
- the generated external mirror was not published;
- consumer default branches were not migrated and their locks do not contain
  the new Docara candidate;
- `docara-mix` was not archived and is not retirement-ready;
- tabs remain a visible exact Simai Framework contract blocker;
- local `docara.test` acceptance is not production, release, or ecosystem
  readiness.
