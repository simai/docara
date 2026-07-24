# Docara branding acceptance

Date: 2026-07-23

## Automated gates

- full PHPUnit: 312 tests, 4193 assertions, PASS;
- changed and repository-wide Pint: PASS;
- Composer validation: PASS (PHP 8.4 dependency deprecation notices only);
- schema and Smart JSON parsing: PASS;
- documentation build: 86 source pages, 190 generated HTML files;
- static verification: 10,394 local references, zero broken;
- `git diff --check`: PASS.

## Runtime and visual checks

- served URL: `https://docara.test/ru/authoring/branding/`, HTTP 200;
- rendered Smart view: `compact`;
- one theme-independent image and no secondary label;
- brand mark: 32 by 32 CSS pixels (`--sf-c6`), title line height: 24px;
- accessible link name: `Docara`;
- desktop light and dark themes: visually accepted;
- system theme restored after verification;
- no page-level horizontal overflow;
- compact brand is 104px wide and remains below the existing mobile header
  budget; responsive shell behavior remains covered by the unchanged mobile
  contract. The selected browser transport did not expose viewport resizing,
  so no new 390px screenshot is claimed for this batch.

## Asset evidence

- served logo SHA-256 matches `/Users/rim/Downloads/simai.svg`:
  `10781f8fb31318e8807a215ec002ec1f8033227bf8678300e3dc63a7e3c1aebc`;
- served favicon SHA-256 matches `/Users/rim/Pictures/SIMAI/favicon.ico`:
  `9bcd7305a24235227b13096694eca338fddc72731a948bdf7a8a6d6a0958afda`.

## Local publication

- published tree: `/Users/rim/Sites/docara.test/build_production`;
- manifest SHA-256:
  `94c642530871de20f451d0d79975a7e9e334670a2396b547213ea0aa8589ab6a`;
- rollback copy:
  `/Users/rim/Sites/docara.test/.docara-backups/20260723-153709/build_production`.

No release, merge, push or production-readiness claim was made.
