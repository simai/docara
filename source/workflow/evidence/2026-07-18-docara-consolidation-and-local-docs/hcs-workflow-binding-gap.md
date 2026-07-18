# HCS workflow-binding control-plane gap

Date: 2026-07-18
Exact product candidate: `4a312c1b14cf1e0ed0ad77d32e39b006b2ff9049`
Exact-candidate HCS verdict: **PASS**
Carrier workflow-binding status: **PENDING CONTROL-PLANE CORRECTION**

The independent HCS review and tester verdict pass for the exact product
candidate in `artifact_comparison` mode. Their immutable SHA-256 values are:

- review: `0fd9b0a4bd7ca89724eef090bf278a06a58ddfce0abd4e5115b62a358be1dd70`;
- tester verdict:
  `bd7186f73a161325c6edee2a99950e9dfd65707043d31ee8baf08ca8872c6dee`.

The current central checker cannot also bind this artifact-scoped review to a
tracked evidence-carrier workflow:

1. an embedded workflow binding requires a non-empty Git baseline covering
   every repository reference;
2. `artifact_comparison` is forbidden whenever that Git baseline is present;
3. `git_range` requires its target revision to equal current HEAD and its file
   inventory to equal the full Git diff;
4. committing the review itself advances the carrier HEAD, recreating the
   target mismatch.

Independent attempts confirmed both fail-closed branches. No checker, graph,
or owner contract was changed, and no PASS was fabricated. The safe result is
to retain the exact-product HCS PASS and expose workflow-binding as a separate
central control-plane correction. This gap does not modify product code,
served output, browser acceptance, or the release/retirement nonclaims.
