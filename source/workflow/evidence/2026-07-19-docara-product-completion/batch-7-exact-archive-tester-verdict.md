# Batch 7 — independent exact-archive tester verdict

Date: 2026-07-19
Candidate: `a5cc0e7ddd3a4ef218381e3e4129825eedf6d671`
Tree: `ec09ea5249a43c712729cbb74ab03e736987a353`
Diff base: `9048e38eb045a4b829197c6f6ac7339e603d0fb1`
Verdict: `PASS`

## Immutable inputs

- distribution archive SHA-256:
  `718e82c846846569e1859c07d338c5819dbec4999ef7b40a727e342e854a3bad`;
- test-only archive SHA-256:
  `45d9f869dc4024aebd98afe966c6d9e28605d3fb783e2c6727232ee768740f45`;
- two distribution archives are byte-identical;
- both archives contain the exact candidate commit ID;
- the test-only archive differs only by locally removing the `tests`
  export-ignore rule from the same candidate.

The compatible dependency tree was copied only after matching
`composer.json`, Composer installation metadata and all 7,190 vendor files.
The repository, candidate, runtime, workflow and readiness were not changed by
the tester.

## Automated verification

- Composer `validate --strict`: PASS;
- Composer platform requirements: PASS;
- Pint: PASS;
- PHP lint: 352 files, PASS;
- JSON resources: 30 files, PASS;
- `git diff --check`: PASS;
- complete sequential PHPUnit: 534 tests, 3923 assertions, no errors, failures
  or skips;
- JUnit SHA-256:
  `4a892ab889fbbad6341cc226673cf951e165f85e19a349aac03e841d1ec63c31`;
- catalogue/renderer/verifier focus matrix: 85 tests, 1603 assertions, PASS;
- serve/router matrix: 6 tests, 31 assertions, PASS;
- static-verifier fail-closed matrix: 18 tests, 221 assertions, PASS.

## Build and catalogue

Two clean builds are byte-identical:

- files: 70;
- HTML pages: 60;
- tree digest:
  `dc6e2a997314a2497da29af3e696937d7f46aee2a832ed3166e2862cdd963675`;
- static verifier: 6477 local references, zero broken.

The exact catalogue contains:

- 17 records;
- 12 supported live examples;
- five unavailable records;
- one index and 12 detail pages;
- 59 search documents;
- catalogue hash:
  `b767b85606778bf9ce22f6fd1db5a433c55c68d0847aaedfddfbf00849fdc0b1`;
- receipt hash:
  `9c162a082032fb5be7223a806539d7d272d755378a3c5c894a42b6b862b0970d`.

Example files, example hashes, detail outputs and receipt records match. Router
tests cover dotted pretty URLs, current PHP binary use, traversal/PHP
execution/write-method rejection and refusal to serve a stale tree after build
failure or declined overwrite.

The test-created local `.env` was removed. Production readiness, public release
readiness, support for all Framework components and canonical-registry status
remain false.

This is non-browser Batch 7 acceptance only. It does not itself publish the
candidate or complete the wider Goal.
