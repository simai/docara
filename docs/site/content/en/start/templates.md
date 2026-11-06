---
description: The minimal, showcase and set starters of init, the files they create, what the build environment needs and how to check the installation.
tags: [init, starter, template]
---

# Starters and environment

The `init` command from the [quick start](/en/start/quick-start/) creates a
project from a starter. This page covers which starter to choose, what
appears in the folder and what the build environment needs.

## Choosing a starter

`init` takes two main options: `--template` (`-t`) picks the starter and
`--lang` (`-l`) the language of its content. The default is
`--template=minimal --lang=en`: a small working site in English. You choose
the starter at the first `init`
([step 1 of the quick start](/en/start/quick-start/#1-create-the-project)):
in a project that already exists, `init` refuses to run.

```bash
# A small site in English (the default)
php vendor/bin/docara init .

# The same site in Russian
php vendor/bin/docara init . --lang=ru

# Component catalogue, landing page, nested guides and Smart examples
php vendor/bin/docara init . --template=showcase --lang=ru

# The site of a SIMAI Framework component set with the id "view"
php vendor/bin/docara init . --template=set --set=view --lang=ru
```

| Starter | Content | Languages |
| --- | --- | --- |
| `minimal` | a home page, a page about writing pages (Markdown, code, a table, `:::example`) and a components page (alert, button, the JSON component `:::ui.button`) | `en`, `ru` |
| `showcase` | a component catalogue, a landing page, nested guides and project Smart examples | `ru` |
| `set` | the site of a SIMAI Framework component set: overview, connection, components, demos and changes | `en`, `ru` |

The `showcase` starter is written in Russian only. The combination
`--template=showcase --lang=en` stops with a clear error and writes nothing.

## A component set's site

The `set` starter is the documentation of a SIMAI Framework component set.
It has the five sections every set's site has, in this order in the menu:

| Section | Page | What to replace with your own |
| --- | --- | --- |
| Overview | `index.md` | the component table, the licence |
| Connection | `connection.md` | the table of releases; the connection line already has the set id |
| Components | `components/` | one page per component instead of the sample `first-component.md` |
| Demos | `demos/` | pages with screens built from the components |
| Changes | `changes.md` | a section for each version of the set |

The `--set` option is the set id from its `set.json`, such as `view`. The
starter writes it into the `data-sf-sets` connection line, the
`SF_BOOT_CONFIG` setting and the titles. Without `--set` it writes
`my-set`; `init` refuses an id with capital letters or spaces and writes
nothing. Instead of the four page-type folders, `docara.authoring.json`
gives these sections their types: the overview is a landing page,
components and changes are reference, connection and demos are articles.

The line about the set's release at the foot of every page comes from the
`shell.set-passport` block in the `footer` region, once the set's package
is pinned in `simai-framework.lock.json`; see [A component set's line in the footer](/en/settings/layout-and-regions/#a-component-set-s-line-in-the-footer).

## Build environment

Docara includes a pinned SIMAI Framework release together with the
Composition Recipe runtime. You need no separate Framework copy and no
environment variables for it.

Node.js must be available as the `node` command. If it is installed
elsewhere, set `export DOCARA_NODE_BINARY=/path/to/node` in the build
environment, including automated checks and Docara updates.

Two commands check the installation without building: `capabilities`
reports the version and features of the installed Docara, and `doctor` the
state of the project contracts.

```bash
php vendor/bin/docara capabilities --json
php vendor/bin/docara doctor --json
```

## Updating

Docara updates with one command, `php vendor/bin/docara upgrade`, and goes
back to the previous checked version with
`php vendor/bin/docara upgrade --rollback=latest`. How it works and what it
checks is described in [Updating Docara](/en/build/update/).

## Which files the starter creates

The `minimal` starter with `--lang=en` creates these files:

```text
docara.json
docara.authoring.json
AGENTS.md
composer.json
composer.lock
vendor/
redirects.json
simai-framework.lock.json
assets/
contracts/
.docara/
  engine/
content/
  en/
    section.json
    lang.json
    index.md
    index.page.json
    writing.md
    writing.page.json
    components.md
    components.page.json
    getting-started/
    how-to/
    reference/
    explanation/
```

With `--lang=ru` the content lives in `content/ru/`. The site has one
language and routes without a language prefix: `/`, `/writing/`,
`/components/`.

The four folders are a documentation skeleton by page type: tutorial,
how-to, reference and explanation. Each holds a short example page of its
type: replace it with your own text. `docara.authoring.json` turns on page
type checks, and `AGENTS.md` tells AI assistants the same standard. How it
works is described in [Page profiles](/en/authoring/page-profiles/).

## See also

- [Project files](/en/start/project-files/) — what each file and folder is for
- [CLI commands](/en/reference/cli/) — all options of `init`,
  `capabilities` and `doctor`
