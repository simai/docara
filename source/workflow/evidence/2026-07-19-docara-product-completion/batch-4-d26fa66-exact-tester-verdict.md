# Batch 4 — successor exact tester verdict

Date: 2026-07-19
Candidate: `d26fa66c6d6a5a36ec113288e6fce29f2f6b1a0e`
Tree: `aa20ad5d0d95f82149a30d189dd5fa9d78163d4a`
Parent: `4812b19eb4cf99f0a9fba739d726d77659ef6dd8`
Accepted baseline: `06a993f3e0ce8df3bbe26569aa917b7bfe6de6a5`
Verdict: `PASS` for the bounded non-browser tester slice
Browser gate: `PENDING`

The independent tester used two byte-identical standard Git archives and one
detached exact full tree. Checks passed:

- two 495-entry archives, common SHA-256
  `60db05a26435a307e57e0a7ba3df1dacb825e0beab59151d99682ab8c37bdc0d`;
- Composer strict and production/development platform requirements, Pint and
  syntax for 206 PHP, 40 source JSON and 13 source JavaScript files;
- full PHPUnit: 465 tests, 2387 assertions, zero failures/errors/skips;
- focused mutual-exclusion regression: 1 test, 299 assertions;
- two clean production builds: 51 files, 44 HTML pages, 4535 checked local
  references and zero broken references each;
- recursive equality and common path-independent digest
  `9cf966409f87a568fbd6a79efc12c6922369dc5ce5fe92adcbdff074e297e67f`;
- generated JavaScript and five unique inline scripts from 220 instances;
- Framework locks, runtime mirror, manifest/Smart hashes, six fixed revisions
  and zero moving references;
- 88 generated-page storage/order checks and single-owner modal exclusion;
- complete diff-check, secret gate, distribution `.env` absence, exact-tree
  hygiene and explicit nonclaims.

The repository has no committed `composer.lock`; the tester therefore made no
fresh-resolution reproducibility claim. The isolated trees reused and rehashed
the already accepted 7190-file vendor corpus because `composer.json` is
byte-identical to the accepted parent.

Raw evidence:

- `/tmp/docara-b4-d26fa66-exact-tester/verdict.md`, SHA-256
  `83ad24224ce473c24c6bb6d342be8fcba872f17d8640daa39ada4aba97139c94`;
- `/tmp/docara-b4-d26fa66-exact-tester/checks.json`, SHA-256
  `c3fec44703232fe0c9262aae69733c769fded9ab79af0e02b93ad763f69dfe03`;
- PHPUnit JUnit SHA-256
  `a1edf62bcbf92bf6fe219a9dd61fdd8ea267ae3f2eba843932e6b0a42f777240`.

Native Chrome was not run because the external execution transport returned an
explicit usage limit and prohibited a rerun or indirect workaround. Physical
`Cmd/Ctrl+K`, focus, responsive and disabled-storage browser acceptance remains
mandatory. This bounded PASS does not accept Batch 4 or authorize publication,
production, ecosystem or wider-Goal readiness.
