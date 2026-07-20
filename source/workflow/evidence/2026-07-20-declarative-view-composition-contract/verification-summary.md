# Verification summary

## Contract and regression

- Full supported-runtime suite:
  `/Applications/ServBay/package/php/8.2/8.2.29/bin/php vendor/bin/phpunit`
  — PASS, 586 tests and 4,811 assertions.
- PHP syntax checks for every changed PHP file — PASS on PHP 8.4.20.
- Pint over every changed PHP file — PASS.
- `git diff --check` — PASS.
- The formerly network-active `PresetScaffoldBuilderTest` now uses a true
  `ProcessRunner` mock; its 10 tests complete in 3.593 seconds without running
  `composer require`.

## Declarative result

The generated plan for `authoring/regions/index.html` proves:

- schema `docara.resolved_render_plan.v2`;
- Layout `docara.docs` uses View Tree `layout.docara.docs`;
- stable Section call `site-header -> docara.header`;
- stable Block call `site-header.branding -> shell.smart`;
- named slot `content`;
- Markdown and `ui.alert` Smart Blocks in the article section;
- diagnostics `COMPOSITION_EXPANDED`, `SAFE_VIEW_TREE_VALIDATED` and
  `AUTHOR_EXECUTABLE_SURFACES: absent`;
- Framework compatibility ID `sf-v5.3.2-7e836d8a-dd786bba`;
- Framework registry SHA-256
  `2c5963276d31af09770fe41cad04826c04b634f7b2d798d9b0e32864517346b7`;
- Larena adapter `semantic_parity: pass`.

## Build and static output

- Two clean production builds produced the identical aggregate SHA-256:
  `6a2a97198b07a30326ad98237c381b2a8600b54a26141e9cba0be155508914e8`.
- The served tree has the same aggregate SHA-256.
- `verify-static` checked 115 HTML pages and 11,258 local references.
- Broken reference list: empty.
- Legacy `src/PortableSite/PortableHtmlRenderer.php` SHA-256 remains
  `a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`.

## Compatibility observation

PHP 8.4 passes the changed-code syntax/style gates and the new declarative
tests. Two inherited Jigsaw snapshots are not cross-version deterministic on
PHP 8.4 because date-only fixture paths shift one day. The complete supported
PHP 8.2 suite is green; no PHP 8.4 readiness claim is made by this workflow.
