---
description: Updating Docara with one upgrade command that checks a candidate and rolls back without the network, the low-level update and the manual move from 2.x to 3.0.
tags: [upgrade, update, rollback, migration-3]
---

# Updating Docara

A normal update runs from the project itself with one command you start
explicitly. Docara does not update in the background and uses no network
during build or serve.

## A normal update

```bash
git status --short
php vendor/bin/docara upgrade
```

Docara automatically:

1. checks the project-local Composer runtime, PHP, ownership, the Framework
   lock and unfinished transactions;
2. picks the highest stable patch/minor version inside the current major and
   the constraint from `composer.json`;
3. assembles a separate candidate under `.docara/upgrades/<id>/`;
4. runs `doctor`, `validate project`, the engine sync, a production build and
   `verify-static` with the candidate on an isolated copy of the project;
5. checks the hashes of all inputs again;
6. replaces the dependencies, the engine and the verified build only after a
   green check.

If any step fails, the original `composer.lock`, `vendor/`, the engine and the
last checked build are restored locally. Markdown, examples, assets, settings,
translations, Smart/design sources and the Framework lock are not edited.

## Checking and an exact plan

```bash
php vendor/bin/docara upgrade --check --json
php vendor/bin/docara upgrade --to=3.6.0 --dry-run --json
php vendor/bin/docara upgrade --apply=<plan-sha256> --json
```

`--to` takes only an exact stable version `X.Y.Z`. A branch, `dev`,
`latest`, a range or a prerelease is refused. The version must be inside the
current major and must satisfy the Composer constraint of the project. Any
change to `composer.json`, `composer.lock`, the engine, the content, examples,
assets, settings, the Framework lock or the old verified build makes the plan
stale.

## Rollback without the network

```bash
php vendor/bin/docara upgrade --rollback=latest
# or the exact rollback_id from the result of apply
php vendor/bin/docara upgrade --rollback=<id>
```

Both dependency sets are saved locally before anything is applied. That is
why a rollback does not depend on Packagist or another outside service. If the
current runtime changed after the apply, the automatic rollback stops so that
it does not overwrite the new work.

## A new major version

A major is not applied automatically: `upgrade` works only inside the current
major version. A request for another major, for example `upgrade --to=3.0.0`
for a project on Docara 2.x, returns `MAJOR_UPGRADE_REQUIRED`. The order of
the manual move is described below, in
[Moving from 2.x to 3.0](#moving-from-2-x-to-3-0).

## An old project without an ownership manifest

An old engine is not deleted and not moved automatically. If the project
already holds documentation but has no `.docara/engine` yet, create the
project-local runtime once and adopt the package-owned engine explicitly:

```bash
composer require simai/docara:^3.0
php vendor/bin/docara capabilities --json
php vendor/bin/docara update --dry-run --adopt --json
php vendor/bin/docara update --apply --json
php vendor/bin/docara update --verify --json
php vendor/bin/docara upgrade --check --json
```

Look through the dry-run operations before `--apply`. The adoption creates
only the package-owned `.docara/engine`; Markdown, settings, examples and
assets do not change. If you run `upgrade` before the adoption, Docara returns
`UPGRADE_ENGINE_ADOPTION_REQUIRED` with this same route. After the adoption
every next compatible update runs through `upgrade`.

## The low-level `update`

`update` is kept to sync only the package-owned `.docara/engine` from an exact
version already chosen, following a checkable ownership manifest:

```bash
php vendor/bin/docara update --verify --json
php vendor/bin/docara update --dry-run --json
php vendor/bin/docara update --apply --json
php vendor/bin/docara update --rollback=latest
```

`update` with no action flag runs `--verify`. It does not start Composer and
does not change the dependency lock. Do not edit
`.docara`, `vendor/` or `build_production` by hand. Its `--dry-run` and
`--apply` are tied together by an immutable hash-bound plan.

`init --update` is not a shortcut for this process: the command is disabled,
it fails and it points to the order `update --verify`, then `--dry-run`, then
an explicit `--apply`.

## Moving from 2.x to 3.0

Docara 3.0 is a new major version, so a project on 2.x is moved by hand with
the steps below. The Markdown, settings, translations and assets of the
project are not rewritten along the way.

### What changed

#### The Framework ships inside the package

Docara 3.0 holds a pinned SIMAI Framework release from the public
`simai/ui` repository (Core from `distr/`, Smart from `distr/smart/` of the
same commit) together with the Composition Recipe runtime: `ui-82fc0ba882ba`
in versions 3.0.0–3.0.5, `ui-25beeb814ae8` in versions 3.1.0–3.5.0,
`ui-32c7a64e4575` in versions 3.6.0–3.9.1,
`ui-7153f7a9c092` from 3.10.0 on. A build no longer needs
a separate Framework copy or environment variables: you can remove
`DOCARA_SIMAI_UI_ROOT` and `SIMAI_UI_ROOT` from the build environment and from
CI. These variables stay optional and are needed only to build with another
delivery that implements the same Recipe specification.

Node.js 20+ is still needed on the build machine: it runs the Recipe
runtime. If `node` is not in `PATH`, set `DOCARA_NODE_BINARY`. The finished
site stays static.

#### The Framework lock of the project

- The `simai-framework.lock.json` lock that shipped with Docara 2.18 builds
  with the release from the package unchanged.
- A lock that names an older Smart revision switches the build to an outdated
  portable copy of Smart. To build the site with the release from the package,
  replace the lock of the project with the one the package ships:
  `vendor/simai/docara/stubs/portable/simai-framework.lock.json`.

#### The `sf-admin-menu` and `sf-table` components

The free SIMAI Framework release no longer holds `sf-admin-menu` and
`sf-table`: they moved to the commercial SIMAI component package. Pages that
use them have to change — for example, replace `sf-table` with an ordinary
Markdown table (see [Markdown table](/en/authoring/markdown/#tables)).

#### A new project and the command line

- `docara init` takes `--template=minimal|showcase` and `--lang=en|ru`.
  The default starter is `minimal` in English: a home page, a page about
  writing pages and a components page, one language, routes without a
  prefix. The component catalogue comes from
  `init . --template=showcase --lang=ru`. More in
  [Quick start](/en/start/quick-start/).
- `docara` with no arguments and `docara list` with no view print the list of
  commands, and `docara --version` prints the version. The full list is in the
  [CLI reference](/ru/reference/cli/) (in Russian).

#### The build result

- Shared assets are published once in `<base_url>_docara/` instead of again
  under every language prefix such as `/ru/_docara/`.
- The built-in strings Docara writes into pages (the table headings of the
  schema reference, the text of the Atlas index, the default
  `internal_preview` heading, the example caption in a starter) follow the
  language of the page: Russian for `ru`, English for the other languages. On
  sites that are not in Russian these strings became English.
- The metadata of the component catalogue (`_docara/component-catalog.json`,
  the `metadata` field of the entries) holds `release` — the Docara version —
  and the `source_url` and `history_url` links to the release tag. The
  `revision`, `author` and `changed_at` fields are gone. If an outside tool
  read these fields, update it.

#### Requirements

The `composer.json` of the package declares PHP 8.2+ and the `ctype`, `dom`,
`intl`, `json`, `libxml` and `mbstring` extensions. Composer refuses to
install Docara 3.0 if one of the extensions is missing.

### The order of the move

Run the steps in the folder of the project.

#### 1. Save the current state

```bash
git status --short
```

Commit or stash unfinished changes and keep the last checked
`build_production` so that you can go back to it.

#### 2. Install Docara 3.0

```bash
composer require simai/docara:^3.0 -W
```

The first command adds the Docara repository on GitHub to the project:
version 3 is installed from there.

`-W` lets Composer update the dependencies of Docara together with it.

#### 3. Sync the engine of the project

```bash
php vendor/bin/docara update --verify
php vendor/bin/docara update --dry-run
php vendor/bin/docara update --apply
```

`--verify` checks the ownership, `--dry-run` shows the exact operations, and
`--apply` updates the package-owned `.docara/engine` atomically by that plan.
The content, `docara.json` and the section and page settings do not change. If
the project has no ownership manifest yet, run the dry-run with `--adopt` —
see [Updating Docara](/en/build/update/).

#### 4. Check the Framework lock

Look at the `runtime.pair_id` value in `simai-framework.lock.json`:

```bash
php -r 'echo json_decode(file_get_contents("simai-framework.lock.json"), true)["runtime"]["pair_id"], PHP_EOL;'
```

The values `ui-7153f7a9c092` (the current release), `ui-32c7a64e4575` (Docara
3.6.0–3.9.1), `ui-25beeb814ae8` (Docara 3.1.0–3.5.0), `ui-82fc0ba882ba` (Docara 3.0.0–3.0.5) and
`ui-4f5e5067afeb-smart-7d932ed51408` (the Docara 2.18 lock)
build with the release from the package. With any other value, replace the
lock:

```bash
cp vendor/simai/docara/stubs/portable/simai-framework.lock.json simai-framework.lock.json
```

#### 5. Find the removed components

```bash
grep -rn "sf-admin-menu\|sf-table" content
```

Change the pages you find.

#### 6. Remove the Framework variables

Delete `DOCARA_SIMAI_UI_ROOT` and `SIMAI_UI_ROOT` from the build environment
and from CI if they pointed at a Framework copy only for the sake of the
build.

#### 7. Build and check

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Open `http://127.0.0.1:8000` and check the home page and a nested page,
search, the theme switch and the pages with components. Then publish the
folder following [Hosting the site](/en/build/hosting/) and
[Publishing and rollback](/en/build/publish/).

The next compatible updates inside 3.x are done by the command
`php vendor/bin/docara upgrade`.

## See also

- [Publishing and rollback](/en/build/publish/)
- [Update error codes](/ru/reference/errors/upgrade/) (in Russian)
- [The upgrade command](/ru/reference/cli/) (in Russian)
