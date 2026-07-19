# Batch 9 — independent exact-archive tester verdict

Date: 2026-07-19
Candidate: `de87bdef224d518d1c707286d4640be0238d34bc`
Tree: `7c0c20678aff65858e29e9be4dd304ddd44ba17b`
Parent: `4164ba2aa890a711b58a2ea016c4f4fbb77ef865`
Verdict: `PASS`

## Immutable execution

The independent tester executed only fresh Git archives under
`/private/tmp/docara-exact-acceptance-de87bdef.ZmAtIZ`. The mutable worktree
was not used as executable source and remained clean.

- two distribution archives are byte-identical;
- distribution archive SHA-256:
  `93024f6ecd12e9595ba6483bb6e589672789b358d17882e5049f0953c0c3437d`;
- exact tree inventory SHA-256:
  `2f854fd7e16f1ff712087751bbfb7712bc651978012d5125b5e180649dd32c66`;
- archive paths, entry types, export boundaries and embedded commit identity:
  PASS.

## Automated acceptance

- Composer strict validation and platform requirements: PASS;
- Pint, PHP lint, JSON/JSONL/YAML parsing and `git diff --check`: PASS;
- full sequential PHPUnit: 541 tests, 4,305 assertions, no errors, failures
  or skips;
- focused documentation/catalogue/outline/mobile matrix: 38 tests, 1,590
  assertions, PASS;
- isolated static-router test: five of five runs passed, each with 20
  assertions and no orphaned child process.

## Deterministic product build

Two clean production builds are byte-identical:

- files: 66;
- HTML pages: 56;
- canonical digest:
  `502e43119ea2f2fc6ce358042858937060a67c4aa3d4d5ac0295e3d19c8e782f`;
- canonical manifest SHA-256:
  `0e0bb352d6bad9c7b26445585470e3f4dce6318afbf965179b3ffa54749c8826`;
- static verifier: 5,793 local references, zero broken, for both explicit and
  default CLI modes.

Search finds the exact Russian query `расширение` in the single
`/development/extensions/` document. The catalogue contains 17 records:
12 supported and five unavailable. All checked revisions are immutable
40-hex commits. Required nonclaims remain false: support for all Framework
components, canonical Framework-registry ownership, production readiness and
public-release readiness.

## Evidence integrity

- tester verdict SHA-256:
  `639eec1970024eee9b14d6e3189bb6525db40c038ff18e89e7c885efa01cd1cd`;
- machine-readable checks SHA-256:
  `6b9d2f16f1ff60079940100d43096c4fde3116113ff4cb7edb85dcb4fa232df2`;
- 74-artifact checksum manifest SHA-256:
  `c46d2ab16e513eb7bf90e2ea40223f729dd05b28836f7b40d02e41b3bc4fb073`.

The final replay, repository hygiene and bounded secret scans passed. The
tester performed no source edit, commit, publication, push, merge, tag or
release action. This verdict does not itself claim browser or publication
acceptance.
