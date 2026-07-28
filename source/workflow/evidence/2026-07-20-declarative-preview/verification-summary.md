# Verification summary

Date: 2026-07-20
Status: PASS before local deployment

## Focused acceptance

Command:

```text
/Applications/ServBay/package/php/8.2/8.2.29/bin/php vendor/bin/phpunit \
  tests/Unit/DeclarativePreviewTest.php \
  tests/Unit/DeclarativeRenderingTest.php \
  tests/PortableSiteBuilderTest.php \
  tests/PortableDocumentationSiteTest.php \
  tests/Unit/StaticBuildVerifierTest.php
```

Result: 56 tests, 972 assertions, PASS.

Coverage includes:

- safe route mapping and fail-closed unsafe source output;
- exact internal-link projection with original URL provenance;
- trusted full-document and catalogue templates;
- rendered/skipped receipt projection;
- build identity and exact SHA-256 checks;
- negative verification after receipt tampering;
- backward-compatible verification of builds without the optional preview
  receipt.

## Full regression

Runtime:
`/Applications/ServBay/package/php/8.2/8.2.29/bin/php`

Result: 575 tests, 4,759 assertions, PASS in 4 minutes 14 seconds.

PHP 8.4 was also used for focused implementation checks. Two unrelated legacy
snapshot fixtures shift month-end dates under PHP 8.4; the same snapshots pass
on supported ServBay PHP 8.2. No snapshot was changed.

## Production build

Two consecutive clean production builds produced the same content-tree digest:

```text
d6ae16efae2e5066b2f7ca957cae2bd7d896e83e26acea3be3d27956b8f518be
```

Static verifier result:

```json
{
  "schema": "docara.static_build_verification.v1",
  "deployment_base": "/",
  "html_pages": 113,
  "local_references_checked": 10920,
  "broken": []
}
```

Declarative preview receipt:

- authored pages: 45;
- rendered: 45;
- skipped: 0;
- preview HTML: 45 pages plus one catalogue;
- primary publisher switched: false;
- full visual parity claimed: false;
- production readiness claimed: false.

## Boundaries

- `PortableHtmlRenderer.php` SHA-256 is unchanged:
  `a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`.
- `git diff --check`: PASS.
- Pint on the changed PHP surface: PASS.
- No push, merge, release, tag or public deployment performed.
