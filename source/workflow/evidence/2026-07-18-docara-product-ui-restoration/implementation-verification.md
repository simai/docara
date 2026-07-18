# First product vertical: implementation verification

Date: 2026-07-18
Candidate: `83d677c7eb5f22d9ca2f4ac16990fe16eddbe985`
Candidate tree: `4956ff452b516ace2df744ee34b276881437adb9`
Accepted technical ancestor:
`4a312c1b14cf1e0ed0ad77d32e39b006b2ff9049`

## Product result

The portable Docara engine now renders a real hierarchical documentation
shell instead of flattening every page into one list.

- The semantic navigation tree has no fixed internal depth cap.
- The starter and the Docara documentation both contain real four-level
  fixtures.
- Every page in the active trail remains a native direct link. Branches have
  separate disclosure buttons, `aria-expanded`, and an automatically opened
  active trail.
- Desktop uses the released Simai Framework Core `.sf-menu` contract. Mobile
  uses a native `details` disclosure containing the same Framework menu.
- Branding is inherited through `docara.json`, `_section.json`, and page
  sidecars: `title`, `label`, `logo`, `logo_dark`, and `favicon`.
- Brand files are validated before destination cleanup, published under a
  content-addressed `_docara/brand/` path, deduplicated, and verified after
  copy.
- `logo_dark` without a default `logo` fails before destination cleanup;
  decorative logos no longer duplicate the visible brand name for assistive
  technology.
- Empty presentation branches fail strict schema validation. The documented
  `foo.md` / `foo/index.md` overview forms now share one tested precedence:
  explicit page order, explicit page reset, matching section order/reset,
  then inherited fallback. A child override such as `hidden=false` is not
  misclassified as a reset.
- Initial active-page reveal waits for real sidebar geometry, survives a cold
  mobile-to-desktop resize, changes only sidebar scroll, and stops after the
  first successful reveal so later user scrolling is preserved.
- The shell uses the released Core `.sf-theme-button` and the already bundled
  pinned `<sf-icon>` Smart component. No Framework owner repository changed.

## Immutable Framework pair

- Core: `simai/ui` tag `v5.3.2`, commit
  `7e836d8a9414d5da553fb1ab0404721e5b48769a`.
- Smart: tag `v5.3.1`, commit
  `dd786bbae98391fb21df9b4e1e6cd402ead0614c`.
- Pair: `sf-v5.3.2-7e836d8a-dd786bba`.
- Moving `main`, `master`, and `latest` references are rejected.
- Unreleased `sf-drawer`, `sf-tree`, and administrative `sf-admin-menu` were
  not consumed by this vertical.

## Main implementation surfaces

- `src/PortableSite/PortableNavigationBuilder.php`
- `src/Portable/ConfigurationMerger.php`
- `src/PortableSite/PortableBrandAssetPlanner.php`
- `src/PortableSite/PortableSiteBuilder.php`
- `src/PortableSite/PortableHtmlRenderer.php`
- `resources/schemas/{presentation,site,section,page}.schema.json`
- `stubs/portable/**`
- `docs/site/**`
- `tests/PortableSiteBuilderTest.php`
- `tests/PortableInitCommandTest.php`
- `tests/Unit/PortableConfigurationTest.php`

## Negative and regression coverage

The suite covers schema rejection, inheritance and `$reset`, missing or
unsupported brand files, oversized files, symlinks, generated-output sources,
pre-clean failure, digest verification, base URLs, asset deduplication,
page-plus-directory merging, repeated path segments, all four ordering/reset
precedence layers, recursive ordering, active/open state, four levels, and a
clean seven-page starter build.

The generated documentation build contains 41 HTML pages and 3574 checked
local references with no broken reference. Its final tree digest is:

`94872dc8627fac21cbc5c0fed8f6a9515b7fdb35d7e75580b49656ce4162eccf`

## Verification commands

```text
/Applications/ServBay/package/php/8.2/current/bin/php vendor/bin/phpunit --colors=never
/Applications/ServBay/package/php/8.2/current/bin/php vendor/bin/pint --test
/Applications/ServBay/package/php/8.2/current/bin/php ../../docara build production
/Applications/ServBay/package/php/8.2/current/bin/php scripts/verify-static-build.php docs/site/build_production
git diff --check
```

Final results:

- PHPUnit: PASS, 432 tests and 2031 assertions in 2 minutes 42 seconds;
- Pint: PASS;
- schema and configuration JSON parse: PASS for all six changed contracts;
- static verifier: PASS, 41 pages, 3574 references, `broken: []`;
- `git diff --check`: PASS.

The central repo-hygiene command still reports the already documented policy
conflict for the two federation-required project-memory `CURRENT.yaml` files.
The same failure exists on accepted ancestor `4a312c1`; this batch introduces
no new hygiene path. See
`source/workflow/evidence/2026-07-18-docara-consolidation-and-local-docs/control-plane-hygiene-gap.md`.

## Honest boundary

This candidate accepts only the first product vertical. Search, right TOC,
breadcrumbs, previous/next, reading settings, richer landing sections, and the
complete component catalogue remain later product verticals. No public
release, package publication, default-branch integration, mirror update, or
`docara-mix` retirement is claimed here.
