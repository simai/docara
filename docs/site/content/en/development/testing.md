---
description: "Testing changes to Docara: which test suites to run before handing over a change and what to check in the built documentation."
---

# Testing

Before you hand over a change, run the checks from narrow to full.

## Building sites

Tests for Markdown parsing, the menu, the page contents, the component
catalogue and the site build:

```bash
vendor/bin/phpunit --do-not-cache-result \
  tests/Unit/PortableConfigurationTest.php \
  tests/Unit/PortableDocumentOutlineBuilderTest.php \
  tests/Unit/PortableMarkdownRendererTest.php \
  tests/Unit/PortableNavigationBuilderTest.php \
  tests/Unit/StaticBuildVerifierTest.php \
  tests/Unit/FrameworkComponentRuntimeTest.php \
  tests/Unit/EffectiveComponentCatalogTest.php \
  tests/Unit/DocumentationContractTest.php \
  tests/Unit/DocumentationExamplesTest.php \
  tests/PortableSiteBuilderTest.php \
  tests/PortableInitCommandTest.php \
  tests/PortableDocumentationSiteTest.php
```

## The whole repository

```bash
vendor/bin/phpunit --do-not-cache-result
vendor/bin/pint --test
composer validate --strict --no-check-publish
git diff --check
```

The generated references are checked by separate commands with `--check`:

```bash
php scripts/capture-component-source-metadata.php --check
php scripts/capture-component-parameters.php --check
php scripts/capture-lang-reference.php --check
php scripts/capture-diagnostic-code-coverage.php --check
php scripts/capture-icon-names.php --check
```

Also check: JSON parsing, creating and building a project from scratch, the
same result from two builds, links in the sources and in the finished pages,
no floating versions (`main`, `latest`), and how the site looks in the
browser — for the documentation and the landing page, in the light and dark
themes, on a wide and a narrow screen.

Run long tests one suite at a time: some older tests share a temporary folder.

## Documentation

```bash
cd docs/site
php ../../docara build production
php ../../docara verify-static build_production
php ../../scripts/verify-page-weight.php build_production --config=page-weight.json
php ../../docara serve production --host=localhost --port=8000 --no-build
```

The built documentation must have:

- one H1 heading on each author page;
- exactly one Markdown file for each public address, and no page created
  without such a file;
- no broken local links;
- `main_source=pagebuilder_document_ir` in every build receipt record, and the
  same HTML from a full build and a single-page build.

The machine component catalogue is a derived build result: it creates no
public pages.

### Page weight and loading

The `build_production.docara/performance.json` receipt shows, for each page,
which styles, fonts and scripts load immediately, the number of requests, the
size and the largest shared files. `verify-static` recalculates it and rejects
outdated sizes and hashes. The weight thresholds are set by
`verify-page-weight.php` — see [Page weight](/en/build/verify/#page-weight).
Tune them for your project from measurements: one shared limit for a
reference, a landing page and a simple site does not fit.

### Reading context

Negative checks are required: heading depths 1 and 7, duplicate `id` values in
HTML, a link to a missing anchor and a wrongly encoded address. In the
browser, check: the contents on the right on a wide screen, the expandable
contents on a tablet and phone, ordinary links, a visible focus outline, and
the “Previous” and “Next” buttons without horizontal scrolling.

## See also

- [Getting started with development](/en/development/getting-started/)
- [Architecture](/en/development/architecture/)
- [Checking the build](/en/build/verify/)
