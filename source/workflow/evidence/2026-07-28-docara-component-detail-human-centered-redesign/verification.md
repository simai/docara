# Verification

Date: 2026-07-28
Verdict: PASS

## Automated checks

- PHPUnit: 333 tests, 6460 assertions, 0 failures.
- Pint: selected changed PHP files pass.
- JSON parsing: changed language packs, schemas and site configuration pass.
- Static verification: 198 HTML files, 17327 local references, 0 broken.
- Two clean disposable builds are byte-identical.
- `git diff --check` passes for the scoped changed files.

## Browser checks

- Desktop viewport: 1440 x 1200.
- Mobile viewport: 390 x 844.
- Document horizontal overflow on mobile: none (`scrollWidth = innerWidth = 390`).
- Example width on mobile: 334 px inside a 334 px article.
- Alert icon instances: 5; all use the registered `Docara Material Symbols`
  font, have `sf-icon-loaded` and a visible 20 x 20 px box.
- Desktop example resolves to two equal 400 px columns inside an 802 px article.
- Removed public sections are absent.

## Local installation

- Target: `https://docara.test/ru/components/alert/`.
- Served HTML hash matches the installed build artifact.
- Previous build backup:
  `/Users/rim/Sites/docara.test/.docara-backups/build_production-20260728-225638`.

No merge, tag, package publication or public deployment was performed.
