# Docara integration verification

Date: 2026-07-18
Candidate state: final pre-commit integration tree; exact SHA is recorded in
`exact-candidate-verification.md`

## Review corrections

Independent implementation review found and the correction batch resolved:

1. navigation ordering needed an explicit upper schema boundary;
2. Smart directives indented by four spaces had to remain CommonMark code;
3. generated-template destination checks had to reject symlink traversal;
4. the documentation shell used an incomplete border utility combination.

Regression coverage was added for each applicable contract. No fake tabs
component or local Framework manifest was introduced.

## Verification results

- Final focused PHP suite: **PASS**, 164 tests and 1267 assertions.
- Full PHP 8.2 suite: **PASS**, 424 tests and 1919 assertions.
- Laravel Pint: **PASS**.
- `git diff --check`: **PASS**.
- Fresh production documentation build: **PASS**, 39 HTML pages.
- Rendered internal links: **PASS**, 398 checked, 0 broken.
- Deterministic rebuild: **PASS**, both trees hash to
  `abec753f99654bbe0a57c6b4c117c25fc71a5e9e131a9778059e0ace2f7c6f9a`.
- Symfony CLI deprecation output: absent after the compatibility correction.

## Environment note

The successful full suite used ServBay PHP 8.2 explicitly in `PATH`. A first
attempt inherited `/opt/homebrew/bin/php`, whose local ICU dynamic library is
missing, and therefore did not constitute a product failure. The successful
run used the explicit ServBay runtime and required no product correction.

## Remaining gates

These results prove the current implementation tree only. Five isolated
consumer builds and the portable Node-free proof are recorded separately.
Exact-commit independent acceptance and local ServBay publication remain
separate gates; release, mirror publication, consumer default-branch merges,
and `docara-mix` retirement remain explicitly unclaimed.
