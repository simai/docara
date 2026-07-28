# Technology evidence

Date: 2026-07-24

## Owner contract

- content source: Markdown plus `docara.page.v1` JSON;
- presentation: existing Docara typed blocks and SIMAI Framework utilities;
- product Smart boundary: unchanged;
- Framework immutable lock: unchanged;
- generated `build_production`: verified output, not an authored source.

## Verification

- PHPUnit: 320 tests, 4,956 assertions;
- Pint: PASS;
- production build: 90 canonical pages;
- static verifier: 198 HTML pages, 10,718 local references, 0 broken;
- desktop: 1,440 px client width equals scroll width;
- mobile: 390 px client width equals scroll width;
- page images: 10, broken 0;
- console errors and warnings: 0;
- exact built/deployed tree comparison: PASS.

## Local publication

- URL: `https://docara.test/ru/`;
- document root: `/Users/rim/Sites/docara.test/build_production`;
- rollback:
  `/Users/rim/Sites/docara.test/.docara-backups/product-homepage-20260724-050131`;
- action gate:
  `source/output/action-gates/action-gate-report-20260724020117.json`.

Verdict: conformant for the product homepage and local test-site publication.
No public release, package release or ecosystem readiness is claimed.
