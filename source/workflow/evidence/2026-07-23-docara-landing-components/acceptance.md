# Docara landing components acceptance

Date: 2026-07-23
Candidate: working tree on `codex/docara-consolidation`
Scope: `docara.hero`, `docara.logos`, generated catalog, landing composition,
native code-block visibility and local `docara.test` publication.

## Automated verification

- `/Applications/ServBay/bin/php vendor/bin/phpunit`: PASS, 316 tests.
- `/Applications/ServBay/bin/php vendor/bin/pint --test`: PASS.
- `git diff --check`: PASS.
- `docara build production`: PASS, 88 canonical pages.
- `docara verify-static build_production`: PASS, 194 HTML pages, 10,640 local
  references, 0 broken.
- `diff -qr docs/site/build_production /Users/rim/Sites/docara.test/build_production`:
  PASS.

## Browser verification

- desktop `1296 x 767`: one H1, hero/features/logos/steps/columns/cta visible,
  no horizontal overflow;
- mobile `390 x 844`: hero and features use one column, logos use two columns,
  no horizontal overflow;
- light theme: PASS;
- dark theme: PASS;
- generated `docara.hero` detail page: live example present;
- generated `docara.logos` detail page: four live items present;
- browser error/warning log: empty;
- code block has `source init`, computed opacity `1`.

## Rollback

Previous local build:
`/Users/rim/Sites/docara.test/.docara-backups/20260723-174925`.

## Verdict

PASS for the local Docara candidate. Public release, merge, commit, push and
production publication are not part of this acceptance.
