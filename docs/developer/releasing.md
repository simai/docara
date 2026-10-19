# Releasing Docara

Releases are cut by maintainers from `main`. A release is the tag `vX.Y.Z` on
one commit, the Composer package Packagist builds from that tag, and a GitHub
release with a verified ZIP, its manifest and checksum.

## 1. Prepare the commit

- Set `VERSION` to `X.Y.Z`.
- Add `## [X.Y.Z] - YYYY-MM-DD` to the top of `CHANGELOG.md`. The publish
  workflow copies this section into the GitHub release notes, so write it for
  users.
- For a new major or minor version, update the `simai/docara:^X.Y` examples in
  `README.md`, `docs/site/content/ru/start.md`, `docs/site/content/ru/reference/cli.md`
  and the install-builder demo (a test checks the first three).
- Run `php scripts/capture-component-source-metadata.php`. The component
  catalogue snapshot names the release and links to the cited files under the
  release tag; `--check` (and a test) fails if it is out of date.

## 2. Check it

```bash
vendor/bin/pint --test
vendor/bin/phpunit
(cd docs/site && php ../../docara build production && php ../../docara verify-static build_production)
php scripts/verify-consumer-install.php
php scripts/verify-consumer-install.php --template=minimal --lang=ru
php scripts/verify-consumer-install.php --template=showcase --lang=ru
```

## 3. Build the release package locally

Packaging reads only committed blobs of the exact revision, so run it from a
clean checkout of the commit you will tag. Nothing is tagged or published.

```bash
REVISION=$(git rev-parse HEAD)
VERSION=$(cat VERSION)
php scripts/build-release-package.php \
  --revision="$REVISION" --version="$VERSION" --tag="v$VERSION" --output=build_release
php scripts/verify-release-package.php "build_release/docara-$VERSION.release-manifest.json"
```

The builder writes a deterministic ZIP (the files listed in
`resources/release/package-surface.json`), a manifest with the archive
checksum and the full file list, a `.sha256` file, and a CycloneDX dependency
inventory inside the ZIP. The verifier re-reads all of it and checks the local
links of the packaged Markdown. Building twice from the same revision gives
the same bytes.

## 4. Tag and publish

```bash
git tag -a "v$VERSION" -m "Docara $VERSION"
git push origin "v$VERSION"
```

Then run the **Publish verified release** workflow
(`.github/workflows/publish-release.yml`) with the tag. It checks out the tag,
rebuilds and verifies the package, and creates the GitHub release with the
`CHANGELOG.md` section as notes. Packagist reads the tag on its own.

## The documentation portal artifact

The publish workflow has a second job, `portal-artifact`. It builds
`docs/site` for the simai.io portal path `/ru/docara/docs/` (one locale, no
locale prefix, and the `portal` section, set in the CI checkout's
`docs/site/docara.json`),
runs `verify-static`, packs it with `docara portal-artifact` and attaches
`docara-docs-X.Y.Z-portal.tar.gz`, its `.sha256` and
`docara-docs-X.Y.Z-portal-receipt.json` to the release. The portal pins the
archive by the receipt's sha256, so the job never replaces an existing asset:
a rerun only checks that it rebuilt the same bytes. The job's configuration
step is the exact recipe for a local portal build of the documentation.

## Static site deployment

Publishing a site built with Docara is separate from releasing Docara. See
`scripts/atomic-static-cutover.php` and the hosting pages of the documentation
site.
