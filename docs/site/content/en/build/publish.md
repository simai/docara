---
description: How to publish only a folder that has passed verify-static, check the release package and roll back to an accepted folder without building again.
tags: [deploy, deployment, publishing, rollout, release]
---

# Publishing and rolling back

Publish only a folder that has passed `verify-static`. These steps do not
depend on a particular host: replace the copy and switch commands with the
ones your infrastructure uses.

## Prepare a checkable package

"Ready for release" does not mean "already published". From a clean working
copy at an exact revision, build the ZIP with
`scripts/build-release-package.php`, then check the paired
`release-manifest.json` with `scripts/verify-release-package.php`. Two
independent working copies with the same revision, version and planned tag
must give a byte-identical ZIP, manifest and checksum.

The manifest records the source revision, the SHA-256 of the archive and of
every file, the pinned SIMAI Framework version, the file ownership list and
the list of components (SBOM). The archive holds no Git, CI, tests, working
reports, local cache or credentials. The value `published=false` is required
until a release decision of its own.

Before an update, keep a copy of the project and its `composer.lock`. Install
the exact ZIP, then run `update --verify`, `update --dry-run` and only after
checking the operations `update --apply`. The texts, the assets, `docara.json`,
the section and page settings and the `lang.json` of each language belong to
the project, and an update does not touch them. On an error run
`update --rollback=<id>` and check the restored engine file hashes and the
lock file.

Stop the release when:

- a revision or a hash does not match;
- the file ownership is broken or unknown;
- the plan is stale or the rollback package is damaged;
- a dependency is not pinned to an exact version;
- the build holds a broken link;
- a browser or a security check has not passed;
- the ZIP does not repeat in a second clean working copy.

## 1. Set the site address

Set `base_url` in `docara.json` before the build:

```json
{
  "schema": "docara.site.v1",
  "preset": "docs",
  "framework_lock": "simai-framework.lock.json",
  "base_url": "/docs/"
}
```

Use `"/"` for the root of a domain and `"/docs/"` for a subdirectory. Do not
fix links by hand after a build.

## 2. Build and check

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
```

If a command ends with a non-zero code, stop the publication.

## 3. Record the fingerprint of the folder

Make a list of the files of the checked folder with their hashes. In an
environment with `shasum`, for example:

```bash
(
  cd build_production
  find . -type f -exec shasum -a 256 {} \; | LC_ALL=C sort
) > build_production.manifest.sha256
shasum -a 256 build_production.manifest.sha256
```

Keep this list with your deployment records, not inside the published folder.
Use the same counting command for the source folder and for the checking one.

## 4. Copy into a new checking folder

Do not overwrite a working folder in place. Create a new folder with the
version in its name, for example:

```text
releases/2026-07-19T120000Z/
```

Copy only the contents of `build_production` there. Pass credentials through
the access tools of your infrastructure; do not add them to Docara sources, a
command history, Markdown or the built site.

After the copy, make the same file list for the checking folder (staging) and
compare it with the source one. A mismatch stops the switch.

## 5. Check the essentials

Before the switch, through the checking address or a local binding, check
that:

1. the home page answers with a successful HTTP code;
2. a nested page opens at its own address;
3. the CSS, the Framework assets, the logo and the site icon load with no 404;
4. the search finds a known page;
5. a link to a `#…` heading scrolls to it;
6. the light, dark and system themes are readable;
7. nothing redirects outside `base_url`.

Check exactly the files of the checking folder whose fingerprint matched.

## 6. Switch in one step

If the host supports release folders, point one link at the new folder. If it
does not, use your host's atomic deployment. Do not delete the previous
successful version until you have finished checking after the switch.

Repeat the short check through the public address right away.

## 7. Rolling back

On a 404, a missing asset or search, a wrong address prefix or another break:

1. stop any further change;
2. point the link back at the previous checked release;
3. check the home and a nested page, the assets and the search again;
4. record the fingerprint of the failed release and the symptom;
5. fix the sources and go through it again: build → check → checking folder.

A rollback does not build the old version again: it brings back a folder that
was accepted and has not changed.

## What these steps do not prove

A local build, a check and a checking folder do not replace what a particular
live site requires of security, accessibility, licences, access and change
windows. The former process on Node and Yarn is no proof of a portable
deployment either.

## See also

- [Checking the build](/en/build/verify/)
- [Hosting the site](/en/build/hosting/)
- [Updating Docara](/en/build/update/)
