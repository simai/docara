# R1-C authoring and runtime convergence

Date: 2026-08-02  
Parent revision: `ce136d5`  
Rollback: revert this bounded checkpoint; the prior content-lang-only runtime
remains complete and tested.

## Implemented contract

- `FrontMatterParser` accepts only `title`, `description`, `tags`, `draft` and
  `translation_key`.
- Front matter is removed before Framework extraction and `MarkdownCompiler`,
  while blank source lines preserve original IR line numbers.
- Invalid syntax, key, scalar, tags, draft or translation key fails with a
  stable `FRONT_MATTER_*` code, relative source, line, column and suggestion.
- `draft: true` is omitted from full publication and cannot be used as a
  public single-page target.
- `locales.missing_page_policy` is executable: `skip` publishes physical
  owners only; `error` returns `LOCALE_PAGE_MISSING` with locale, route and
  expected Markdown path. Neither mode performs editorial fallback.
- The recommended section owner is flat `<route>.md` with a sibling directory;
  `<route>/index.md` remains one compatible alternative and both together fail
  `PAGE_SOURCE_ROUTE_AMBIGUOUS`.
- Zero-reference `resolveGeneratedBase()` and
  `AuthoredComponentPageIndex` were removed after an action-gate PASS; no
  generated config/page base remains.

## Truthful documentation

Public authoring, localization, component extension and schema guides now
describe physical Markdown owners, `content/<locale>/lang.json`, front matter
and the real missing-page policy. Root packaged links point only to paths that
belong to the release surface. `DOCARA-TZ`, authoring contract and architecture
now distinguish logical responsibilities from actual namespaces/classes and
list actual discovery/IR diagnostics.

## Verification

- focused config/front-matter/locale/docs/build matrix:
  `61 tests, 2098 assertions` — PASS;
- boundary regression after formatter: `7 tests, 42 assertions` — PASS;
- repository JSON parse: `503` files — PASS;
- Pint: PASS;
- `git diff --check`: PASS.

A full suite run reached `383` tests and exposed one architecture-boundary
false positive caused by a generic docblock containing `list<PageSource>`.
The docblock was changed to `PageSource[]` and its exact boundary test passed;
the next semantic-gate checkpoint must rerun the complete suite and is not
allowed to inherit this run as a full PASS.
