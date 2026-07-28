# Batch 9 — mobile anchor correction

Date: 2026-07-19
Rejected candidate: `4164ba2aa890a711b58a2ea016c4f4fbb77ef865`
Status: `PRECOMMIT_PASS`

## Decision

Keep the sticky product shell and native fragment navigation. Increase only the
mobile heading anchor reserve from `8rem` to `10rem`.

At the measured 14-pixel root size this changes the reserve from 112 to 140
pixels. It clears the observed 127-pixel sticky header by 13 pixels without
adding JavaScript, another layout primitive or a Framework owner change.

The fix preserves:

- native links and heading IDs;
- the existing desktop anchor reserve;
- the mobile menu, search, reading settings and component catalogue;
- pinned Simai Framework revisions and the PHP-only portable build.

## Test-first evidence

A documentation-site assertion requiring the complete mobile sticky-header
reserve was added first.

RED:

- command:
  `/Applications/ServBay/package/php/8.2/current/bin/php vendor/bin/phpunit --filter real_documentation_build_matches_the_exact_product_matrix_and_static_verifier tests/PortableDocumentationSiteTest.php`;
- result: one expected failure because generated CSS still contained the
  112-pixel `8rem` reserve.

GREEN after the bounded renderer correction:

- one test, 38 assertions;
- no failures.

Full regression, deterministic builds and exact-candidate acceptance remain
mandatory before publication.

## Precommit verification

- focused documentation-site contract: 1 test, 38 assertions;
- relevant documentation/catalogue/outline suite: 46 tests, 1,109 assertions;
- full sequential PHPUnit after the independently diagnosed test-harness
  correction: 541 tests, 4,305 assertions;
- Pint, Composer strict validation, PHP syntax and `git diff --check`: PASS;
- two clean production builds: 66 files and 56 HTML pages each;
- static verification per build: 5,793 local references, zero broken;
- canonical digest of both builds:
  `502e43119ea2f2fc6ce358042858937060a67c4aa3d4d5ac0295e3d19c8e782f`;
- generated `10rem` mobile anchor reserve present in all 56 HTML pages.

This is precommit evidence. A frozen candidate must still repeat the independent
exact-archive, complete-diff and native-Chrome gates.

## Routing gap

The central process resolver incorrectly classified the phrase `мобильное
перекрытие якоря` as a legal task even though its ranked candidates gave
`docara` the dominant score. The project workflow, current Goal and raw Docara,
UX, designer and tester owner sources were therefore used as the authoritative
fallback. No legal process was started.
