# Independent tester verdict

Date: 2026-07-18
Verdict: **PASS**

## Functional candidate

- Candidate: `725f22b9996b84640a115be63b86be57802477c5`.
- Full suite: 424 tests, 1919 assertions, PASS.
- High-risk independent suite: 154 tests, 1255 assertions, PASS.
- Documentation exact-archive build twice: 39 HTML pages, 398 local
  references, no broken references, deterministic output.
- Portable exact-archive build twice: 3 HTML pages, 7 local references, no
  broken references, deterministic output.
- Template export correctly failed closed because no unique SemVer release tag
  exists for the candidate.
- `git diff --check`, release archive inventory, and exact-tree checks passed.

## Bounded correction retest

- Candidate: `4a312c1b14cf1e0ed0ad77d32e39b006b2ff9049`.
- Tree: `baef56bd6d9a7955bbd4cda82c54282d4e7f64e9`.
- Diff from the functional candidate is limited to `.gitignore`,
  `docs/site/content/start.md`, and
  `docs/site/content/components/button.md`.
- No application source, scripts, tests, Composer contract, stubs, resources,
  or workflows changed.
- `git diff --check`: PASS.
- Two independent documentation builds: 39 HTML pages, 398 local references,
  `broken: []`, identical relative tree hash
  `451224dd74da76301bad6888d65c5b42b708640d5aaed4b175a32f404aa63dc4`.
- Quick Start has no inactive action. The Button page contains a labeled,
  disabled demonstration with an accessibility label.

## Boundary

This accepts the exact local product candidate and the bounded correction. It
does not accept or imply a release, external mirror publication, consumer
default-branch migration, `docara-mix` retirement, production readiness, or
ecosystem readiness.
