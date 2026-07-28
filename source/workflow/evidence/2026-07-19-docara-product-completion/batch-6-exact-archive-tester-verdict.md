# Batch 6 — independent exact-archive tester verdict

Date: 2026-07-19
Candidate: `68a960ff1debde48664aa8541413dbef208612ee`
Tree: `9363f21c63e516ee4b97772f097b70aa52ff412f`
Verdict: `PASS`

## Exact input

The tester used only an exact archive of the candidate. The source worktree,
candidate commit and product repositories were not changed.

The candidate archive SHA-256 is:

`f509794da3c7c58a0cedec8619a4de43fa9d2f9d127df16345ac47b519eaa40b`

The test-only clean archive used local inclusion-only export attributes for
tests and workflow evidence. Its SHA-256 is:

`8da83fb709e71170b3f22483f95956a7b350e463f064f158cd3555121c4ec937`

No `.env` was present in the archive before the checks.

## Results

- complete PHPUnit suite: 505 tests, 2,942 assertions — PASS;
- Pint, 27 PHP syntax checks, 62 JSON documents and 8 YAML documents — PASS;
- complete candidate diff check — PASS;
- two independent production builds reproduced canonical digest
  `a4d1433fb933c94ffd522c974800d1f62f94d4b299544fbd1a7238eb7274831f`;
- every build contained 55 files;
- explicit and default `verify-static` modes checked 47 HTML pages and 4,943
  local references with zero broken references;
- the verifier did not execute a PHP configuration sentinel;
- focused negative coverage: 37 tests, 132 assertions — PASS;
- five manual tamper probes failed closed for missing asset, extra asset,
  component-catalog hash mismatch, symbolic link and hard link;
- the generated effective catalogue contains 17 records, of which 11 are
  executable/supported;
- the recorded catalogue content hash is
  `02d716492fffb02e801f2cabe0b2a2c763ffd6e8685c6b1489a6b5db03194d98`;
- canonical-registry, all-components, production-readiness and public-release
  nonclaims remain false.

## Artifacts

- verdict:
  `/private/tmp/docara-b6-exact-tester-68a960f/verdict.md`,
  SHA-256
  `92e033536150fc52b05cc16f7b30a9e0fb4cee839ad8ae691aa6de592f9d4e7a`;
- checks:
  `/private/tmp/docara-b6-exact-tester-68a960f/checks.json`,
  SHA-256
  `63cb8d32fbb7441409da8cf84586cc25d523e2338dcabb3c92df5517cead69aa`.

## Bounded debt and nonclaims

The full PHPUnit harness creates an ignored root `.env`. It was absent before
the run, was not read or disclosed and was removed after the check. This is
test-harness debt, not a product acceptance blocker.

This verdict accepts the non-browser Batch 6 contract only. It does not claim
browser acceptance, publication, public release, production readiness,
completion of the live catalogue or completion of the wider Goal.
