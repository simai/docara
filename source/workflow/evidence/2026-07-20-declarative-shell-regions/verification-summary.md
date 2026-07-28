# Verification summary

## Focused acceptance

- PHPUnit: 41 tests, 693 assertions, PASS.
- Covered compiler, renderer, content semantic parity, four-level shell
  composition and complete portable builder integration.

## Full regression

- PHPUnit: 572 tests, 4,701 assertions, PASS.
- PHP runtime: ServBay PHP 8.4.20 with explicit UTC configuration.
- Composer validation: PASS; Composer emitted only its own PHP 8.4 deprecation
  notices.
- Scoped Pint for changed PHP implementation and tests: PASS.
- `git diff --check`: PASS.

## Deterministic static output

Two consecutive `docs/site` production builds:

- aggregate digest:
  `dd7b4add26660ca067b94bccbbe9cadaf3d09fb7f11d91a5a98d1c03da90e92c`;
- HTML pages: 66;
- local references checked: 6,036;
- broken references: 0.

## Preserved fallback

`PortableHtmlRenderer.php` SHA-256 remains:
`a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`.
