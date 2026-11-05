---
description: "Getting started with developing the Docara engine itself: how to prepare a working copy, which checks to run and how to build the documentation."
---

# Getting started with development

This path is for a maintainer who changes the Docara engine. For an ordinary
site, use the [quick start](/en/start/quick-start/).

## Prepare a working copy

1. Get the repository and go to its root.
2. Make sure you use PHP 8.2 or later.
3. Install the exact dependencies from the lockfile.

```bash
composer install
composer validate --strict --no-check-publish
```

Build checks need Node.js 20+. The pinned SIMAI Framework distribution is part
of the working copy, so you need no separate Framework copy. If Node.js is not
in `PATH`, set `DOCARA_NODE_BINARY`. The optional `DOCARA_SIMAI_UI_ROOT`
variable points the build at another distribution that implements the same
Recipe specification.
The site needs no separate npm dependency install.

## Run the narrow checks

Pick the tests of the owner of the contract you change. For portable Markdown,
components and the static output:

```bash
vendor/bin/phpunit --do-not-cache-result \
  tests/Unit/PortableMarkdownRendererTest.php \
  tests/Unit/EffectiveComponentCatalogTest.php \
  tests/Unit/StaticBuildVerifierTest.php \
  tests/PortableSiteBuilderTest.php
```

After the narrow tests, run the full sequential suite:

```bash
vendor/bin/phpunit --do-not-cache-result
vendor/bin/pint --test
git diff --check
```

## Build the documentation

```bash
cd docs/site
php ../../docara build production
php ../../docara verify-static build_production
php ../../docara serve production --host=localhost --port=8000 --no-build
```

Open the site over HTTP and check the changed task with the keyboard, at 1440,
768 and 390 px, in the light and dark themes. Stop the preview with `Ctrl+C`.

## Prepare the change for acceptance

- do not mix a fix with a release, a publish or a Framework owner write;
- add positive and negative tests before raising the lifecycle;
- do not change canonical JSON to fit the generated HTML;
- check two clean builds for determinism;
- hand over the exact commit SHA and the bounds of the claimed result.

A green test on its own does not mean production, public release or the
readiness of all components.
