# Contributing to Docara

Thank you for helping. Keep each pull request to one coherent change, and
check the open issues first: the problem may already be known or in progress.
For anything larger than a fix, open an issue to agree on the approach before
you write the code.

By taking part you agree to follow the [Code of Conduct](CODE_OF_CONDUCT.md).
Report security problems privately, as described in [SECURITY.md](SECURITY.md),
never in a public issue.

## Setup

You need PHP 8.2 or newer with the `ctype`, `dom`, `intl`, `json`, `libxml` and
`mbstring` extensions, Composer, Git, and Node.js 20 or newer on `PATH` (the
build runs SIMAI Framework's page composition code with it).

```bash
git clone https://github.com/simai/docara.git
cd docara
composer install
php docara --version
```

`php docara` in the checkout is the same command line users get as
`vendor/bin/docara`.

## Checks

Run these from the repository root before you open a pull request. CI runs the
same ones.

```bash
vendor/bin/pint --test        # code style (Laravel Pint, pint.json)
vendor/bin/phpunit            # the test suite
```

Fix style with `vendor/bin/pint`. The repository does not commit
`composer.lock`, so CI installs the newest allowed Pint; if `pint --test`
fails on code you did not touch, update your dependencies first.

### The test suite

The full suite takes about an hour: many tests build whole sites. Run one
file or one test while you work:

```bash
vendor/bin/phpunit tests/Unit/PortableMarkdownRendererTest.php
vendor/bin/phpunit --filter internal_preview
```

What the suite needs:

- **Node.js** on `PATH` (or `DOCARA_NODE_BINARY`). Site builds fail without it.
- **A Git checkout.** A few tests ask Git for the tracked files (the
  documentation link check, the ignore-rule check); they fail or skip in an
  exported tree.
- **Optional: `SIMAI_UI_ROOT`**, the path to a SIMAI Framework distribution.
  Tests that run the Composition Recipe runtime use the copy Docara bundles;
  with `SIMAI_UI_ROOT` set they run against that distribution instead, which
  is how CI checks that the bundled copy matches the Framework. To do the same
  locally, export the pinned commit (`runtime.ui.commit` in
  `stubs/portable/simai-framework.lock.json`):

  ```bash
  git clone https://github.com/simai/ui.git ../ui
  mkdir -p ../ui-pinned
  git -C ../ui archive <commit> | tar -x -C ../ui-pinned
  export SIMAI_UI_ROOT="$PWD/../ui-pinned"
  ```

### The documentation site

If you change documentation or how sites are built, build the documentation
site and check it:

```bash
cd docs/site
php ../../docara build production
php ../../docara verify-static build_production
php ../../docara serve production --no-build
```

Look at the pages over HTTP, not through `file://`. The site content
(`docs/site/content/ru`) is in Russian.

### The consumer install

`scripts/verify-consumer-install.php` installs the working tree into a
throwaway Composer project the way users get it, then runs `init`, `build`
and `verify-static` there with every Docara and Framework environment
variable removed. It needs network access for Composer.

```bash
php scripts/verify-consumer-install.php                                   # default starter
php scripts/verify-consumer-install.php --template=minimal --lang=ru
php scripts/verify-consumer-install.php --template=showcase --lang=ru
```

Add `--keep --work-dir=DIR` to inspect the project afterwards. With `--ui=DIR`
(a `simai/ui` clone that has the pinned commit) it also re-runs the Framework
sync scripts inside the installed package and reports any file they would
change.

## Guidelines

- The repository is in English: code, comments, commit messages, command
  output and these documents. The documentation site content is Russian.
- Do not edit generated files by hand: `build_*/`, `.docara/`, the bundled
  Framework under `resources/framework/` and `resources/portable/vendor/`, and
  `resources/component-catalog/source-metadata.json`.
- Interface text that Docara writes into sites goes through
  `src/I18n/UiCopy.php`, with English and Russian strings.
- A new component appears in the catalogue through its definition, not just a
  documentation page.
- If you change a file the component catalogue cites (its documentation page,
  definition or manifest), run `php scripts/capture-component-source-metadata.php`.
- Keep secrets, local absolute paths and build output out of commits.
- Spell the Framework's name as "SIMAI Framework".

## The bundled SIMAI Framework

Docara ships the SIMAI Framework release it pins, so sites build without a
Framework checkout. [resources/framework/README.md](resources/framework/README.md)
lists what is bundled and walks through moving to another release. In short:

1. Point `docs/site/simai-framework.lock.json` and
   `stubs/portable/simai-framework.lock.json` at the new `simai/ui` release.
2. Run the sync scripts with a `simai/ui` clone that has the commit. They read
   Git objects at that commit, never the working tree:

   ```bash
   php scripts/sync-framework-rule-registry.php /path/to/ui
   php scripts/sync-framework-smart-runtime.php /path/to/ui
   php scripts/sync-framework-composition-runtime.php /path/to/ui
   ```

3. Remove the previous revision's directories, record the previous lock as
   superseded, and update the tests that pin digests.
4. Run the whole suite, the documentation site build and the consumer
   install.

[docs/developer/framework-locks.md](docs/developer/framework-locks.md) describes
the lock format and product component packages.

## Releases

Maintainers cut releases. [docs/developer/releasing.md](docs/developer/releasing.md)
has the details; the steps are:

1. Set `VERSION`, add the release to `CHANGELOG.md` (`## [X.Y.Z] - YYYY-MM-DD`),
   update `^X.Y` in the install examples, and run
   `php scripts/capture-component-source-metadata.php`.
2. Run Pint, the test suite, the documentation site build and the consumer
   install for every starter.
3. Build and verify the release package from the exact commit:
   `scripts/build-release-package.php`, then `scripts/verify-release-package.php`.
4. Tag the commit `vX.Y.Z` and push the tag.
5. Run the **Publish verified release** workflow with that tag. It rebuilds
   and verifies the package and creates the GitHub release with the notes from
   `CHANGELOG.md`. Packagist picks up the tag.
