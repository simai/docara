# Batch 9 — preview test-harness backpressure correction

Date: 2026-07-19
Status: `PRECOMMIT_PASS`
Scope: test transport only

## Finding

The first full regression after the mobile-anchor fix reached 540 of 541 tests
and failed in
`ServeCommandTest::static_router_serves_pretty_and_exact_files_without_executing_php`.
The fourth raw-socket request, `/missing/`, received an empty response.

This was not a production-router regression:

- the mobile correction did not touch the router or its test;
- direct `curl` and a standalone Symfony Process probe returned the expected
  `200`, `404` and `405` responses;
- an independent diagnostic reproduced the isolated PHPUnit failure 5 of 5
  times on unique ports;
- the first two responses were immediate, the third arrived only after the
  two-second timeout and the fourth was empty;
- retrying the empty request did not help.

The child PHP server wrote request logs to Symfony Process pipes. The test
stopped reading those pipes after readiness, so the single-threaded child
eventually blocked on stderr backpressure before it could serve the fourth
request.

## Correction

The test now drains incremental stdout and stderr before every request. The
production router, response rules and request matrix are unchanged. Empty HTTP
responses are not silently retried.

## Evidence

- before correction: isolated router test 0/5 PASS;
- after correction: isolated router test 5/5 PASS, 20 assertions each;
- full sequential PHPUnit: 541 tests, 4,305 assertions, PASS;
- Pint `--test`: PASS;
- Composer strict validation under ServBay PHP 8.2: PASS;
- PHP syntax and `git diff --check`: PASS.

Disposable independent diagnostic:
`/private/tmp/docara-serve-diagnostic-4164ba2.va28YH/`.

The harness correction adds no product surface, no runtime behavior and no
readiness claim. It prevents a false deadlock in the exact acceptance suite.
