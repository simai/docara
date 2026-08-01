# Batch 03: early physical-route selection

Date: 2026-08-01

Verdict: PASS

Parent SHA: `cbfa846f710fe0be4d63504df921e90913ad9623`

Candidate binding: the commit that first contains this evidence file.

## Changed boundary

- `PortableSiteBuilder` normalizes and validates `--page` before any page plan
  is loaded or built;
- `PageSourceLocator` results form the lightweight route/source index;
- when the selected route has a physical Markdown owner, the source set is
  reduced to exactly that file before `PortableConfigurationLoader` and the
  shared `PageBuilder` run;
- component-catalog and declarative-example projectors are not invoked;
- the existing complete build supplies metadata-only identities for unchanged
  navigation and diagnostics, while its global artifacts are retained through
  the existing atomic-copy path;
- the same final rendering loop, `PageBuilder`, declarative layout and
  publisher remain in use.

Generated compatibility routes are deliberately not reimplemented. Until a
route gains its physical owner they retain the old projection path; every
migrated route immediately uses the early path. The M3.3/M3.5 gates remove
this branch after all Russian component routes are physical and zero-reference
proof passes.

## Focused verification

Disposable candidate:
`/tmp/docara-m3-runtime.WXaV0l`.

Commands and results:

```text
php vendor/bin/phpunit tests/PortableSiteBuilderTest.php \
  --filter 'it_selects_a_physical_route_before_other_pages_and_global_projections|it_atomically_rebuilds_one_existing_page_without_rerendering_neighbors|it_requires_a_complete_base_build_for_single_page_rebuilds'
PASS — 3 tests, 10 assertions

php vendor/bin/phpunit tests/PortableSiteBuilderTest.php
PASS — 38 tests, 893 assertions
```

The call-spy records exactly:

```text
page.build content/guides/getting-started.md
```

No `component_catalog.project` or `declarative_examples.project` event occurs
in the isolated physical build.

## Production-site parity

```text
php ../../docara build m3-selector-full
PASS — 103 pages, 321 files

php ../../docara build m3-selector-single --page=/ru/components/badge/
PASS — 1 selected page

diff -qr build_m3-selector-full build_m3-selector-single
PASS — no differences

php ../../docara verify-static build_m3-selector-full
PASS — 206 HTML pages, 18,866 local references, 0 broken
```

Badge HTML remains SHA-256
`faeb6c6a8e075bff9ad5602bcea4b1e019c700aeae74f696c0289e32fbb83f79`.
The full candidate differs from the parent disposable baseline only in
filesystem-derived `updated_at` values in `page-metadata.json`; all other files
are byte-identical. Full/isolated candidates created from the same snapshot are
exactly byte-identical.

## Static/repository verification

- PHP lint: PASS;
- Pint on changed PHP/test files: PASS;
- `git diff --check`: PASS;
- dependency and lock files: unchanged;
- public content/resources: unchanged;
- rollback: revert the single batch 03 checkpoint commit.

## Nonclaims

This batch does not claim generic block IR, Alert migration, early selection
for still-generated legacy routes, M3.2 completion or global M3 readiness.
