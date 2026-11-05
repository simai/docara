---
description: How Docara versions are numbered, what changes in a major, minor and patch, where to read the changelog, how Docara warns about deprecated names and how to update.
tags: [versions, changelog, update, deprecated]
---

# Versions and changes

Docara numbers versions by the rules of [semantic
versioning](https://semver.org/): `major.minor.patch`, for example `3.10.0`.

| Part of the number | What changes | Do you need to change the project |
| --- | --- | --- |
| patch: `3.10.0` → `3.10.1` | Bug fixes | No |
| minor: `3.9` → `3.10` | New components, parameters and commands; old names keep working with a warning | No; fix the warnings before the next minor version |
| major: `2.x` → `3.0` | Changes that are incompatible with earlier projects | Yes, by the migration guide |

Error codes do not change between versions, so a fix you found by its code
stays correct after an update.

## Changelog

All changes of each version are recorded in
[CHANGELOG.md](https://github.com/simai/docara/blob/main/CHANGELOG.md) in the
Docara repository, in the sections "Added", "Changed", "Fixed" and
"Deprecated". The same text is published in the release description on
[Releases](https://github.com/simai/docara/releases). The "Unreleased"
section at the top of the changelog is what goes into the next version.

`composer.lock` and this command show which version your project uses:

```bash
composer show simai/docara
```

## Deprecated names

When a parameter name or a value is replaced, the old one keeps working
until the next minor version. The build does not stop; it reports in one
line how many old names are left, and `docara validate page <address>` shows
every place with its replacement (the code `MARKDOWN_PARAMETER_DEPRECATED`).
The list of replacements is in
[Former parameter names](/en/start/component-model/#former-parameter-names).

Before updating to a new minor version, check that no warnings are left:

```bash
php vendor/bin/docara build production
```

## How to update

Within one major version the `docara upgrade` command does the update: it
selects the new version, builds and checks the site on a copy of the project
and changes files only after a successful check. Moving to a new major
version is manual. Both paths are described in
[Updating Docara](/en/build/update/).

## See also

- [Updating Docara](/en/build/update/)
- [Error codes](/en/reference/errors/)
- [Commands](/en/reference/cli/)
