# Acceptance

Date: 2026-07-25
Verdict: PASS

## Automated

- Focused PHPUnit: 46 tests, 1627 assertions — PASS.
- Full PHPUnit: 331 tests, 5126 assertions — PASS.
- Pint: PASS.
- Production build: 90 source pages — PASS.
- Static verification: 198 HTML pages, 14237 references, 0 broken — PASS.
- `git diff --check`: PASS.

## Browser

Page: `https://docara.test/ru/components/catalog/`

Desktop:

- computed `.docara-reading-column` gap: `0px`;
- catalog filter surfaces: `0`;
- catalog entries: `21`;
- first card width equals its list width;
- supported component links in the documentation menu: `16`.

Mobile `390x844`:

- no horizontal document overflow;
- desktop sidebar is hidden;
- catalog filter surfaces: `0`;
- all 21 entries remain available as one-column cards.

Screenshots:

- `browser/docara-catalog-desktop.png`;
- `browser/docara-catalog-mobile.png`.

## Local publication

- Published: `/Users/rim/Sites/docara.test/build_production`.
- Rollback:
  `/Users/rim/Sites/docara.test/build_production.rollback-20260725-093751`.
- Smoke URLs `/ru/`, `/ru/components/catalog/` and
  `/ru/components/catalog/ui.button/` return HTTP 200.

## Scope

This is feature-branch and local test-site acceptance. It is not a default
branch merge, package release or production deployment.
