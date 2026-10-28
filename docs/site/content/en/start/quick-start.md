---
tags: [install, installation, getting-started]
---

# Quick start

This path starts in an empty folder and ends with a page open over HTTP.
Building needs PHP 8.2+ with the `ctype`, `dom`, `intl`, `json`, `libxml` and
`mbstring` extensions, Composer and Node.js 20+. The finished site stays
static; these tools are needed only on the computer or server that builds
it.

## 1. Create the project

Install the stable Docara package straight into the folder of the future
project. Composer takes it from Packagist.

```bash
mkdir /path/to/my-docara
cd /path/to/my-docara
composer require simai/docara:^3.0
php vendor/bin/docara init .
```

Composer records the exact installed version in `composer.lock`, and
`vendor/bin/docara` always belongs to this project. `init` accepts an empty
folder or one that already holds only `composer.json`, `composer.lock` and a
`vendor/` with `simai/docara`. Service files — `.git/`, `.gitignore`,
`.gitattributes`, `.DS_Store` — and your own `AGENTS.md` do not get in the
way: `init` leaves them alone.

The path can be absolute or relative to the current folder. Without a path,
`init` works in the current folder. Other starters are described on
[Starters and environment](/en/start/templates/).

## 2. Build the production folder

```bash
php vendor/bin/docara build production
```

The result appears in `build_production`.

## 3. Check the static result

```bash
php vendor/bin/docara verify-static build_production
```

Continue only after the command succeeds. It checks the pages, local links
and anchors, files, search, the build receipts, the component catalogue and
the SIMAI Framework files.

## 4. Open the site over HTTP

```bash
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

After the line

```text
Server started on http://127.0.0.1:8000
```

open `http://127.0.0.1:8000` in the browser. It worked if the Docara start
page loads, links work and styles apply. The server holds the current
terminal; press `Ctrl+C` when you are done.

Do not open `build_production/index.html` through `file://`: that view does
not check clean addresses, `base_url`, search or file loading.

## Change a page

1. Open `content/en/index.md` (or `content/ru/index.md`).
2. Change the heading or the text.
3. After a full build has run once, run
   `php vendor/bin/docara build production --page=/`.
4. Run `verify-static` again.
5. Restart the preview, or run `serve production` without `--no-build` so it
   rebuilds the site first.

A plain Markdown page needs no JSON file. Settings for the whole site live in
`docara.json`; add a `section.json` only for settings a folder passes down,
and a `<page>.page.json` next to a page only to configure that one page. The
starter has such files as working examples, not as a required pair for every
Markdown file.

`--page` rebuilds only an existing Markdown page. After adding, renaming or
deleting a `.md` file, or changing routing, the menu, site-wide settings or
the Framework lock file, run a full `php vendor/bin/docara build production`.

The starter sets `default_locale`, an explicit `locales` registry,
`locale_routing` without a language prefix and a `redirects_file`. Adding a
second language is described in [«Мультиязычный сайт»](/ru/authoring/multilingual-site/)
(in Russian). For another documentation version, create a separate site
variant and output folder rather than mixing pages in one build.

## What to keep in Git

Keep everything `init` created in the repository, including
`.docara/engine/`: `docara update` and `docara upgrade` check the project
against these files. Do not keep dependencies or build results. A
`.gitignore` like this works:

```text
/vendor/
/build_*/
/.docara-preview/
/.docara-qa/
```

`build_production.docara/` next to the finished site holds the build reports
for `verify-static`; the `/build_*/` rule excludes it too.

## Read next

- [Starters and environment](/en/start/templates/): what `init` created and
  how to check the installation
- [Project files](/en/start/project-files/)
- [How components work](/en/start/component-model/)
- [Tutorial: a first landing page](/en/start/first-landing/)
- In Russian: [writing pages](/ru/authoring/), [site settings](/ru/settings/),
  [checking the build](/ru/build/verify/), [publishing and rollback](/ru/build/publish/),
  [hosting](/ru/build/hosting/), [CI](/ru/build/ci/)
