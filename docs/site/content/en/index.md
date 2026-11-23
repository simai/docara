:::hero {space=6 layout=centered padding=2xl effect=text-flow}
# Documentation sites from Markdown

Docara builds a static site from Markdown files: menu, search, several
languages, and a link check before publishing. It runs on GitHub Pages and
any other host.

[Get started](/en/start/quick-start/)

[Documentation](/en/start/)
:::

::::grid {space=7 columns=3 gap=4}
:::card
### Docs as code

```markdown
# Quick start
Install the package and create a site.

1. composer require simai/docara
2. docara init .
```

The text is plain Markdown files next to your project. Edit a file and you
get a page. History lives in GitHub, changes go through pull requests.
:::

:::card
### A ready static site

:icon[check_circle]{scheme=success} Site build\
:icon[check_circle]{scheme=success} Link and search check\
:icon[check_circle]{scheme=success} Publishing to GitHub Pages

The site is built right in GitHub and published to GitHub Pages or any
host. No server and no database, only files: that makes it fast, and on
GitHub Pages also free.
:::

:::card
### Many ready-made blocks

:badge[Steps]{size=1} :badge[Cards]{size=1} :badge[Tabs]{size=1} :badge[Diagrams]{size=1} :badge[Tables]{size=1}
:badge[Alerts]{size=1} :badge[Hero]{size=1} :badge[Columns]{size=1} :badge[Examples]{size=1}

Use them to build references, guides and landing pages.
:::
::::

## How it works

::::grid {columns=3 gap=4}
:::card
### 1. Write Markdown

```markdown
## Installation
Run in the terminal:
`composer require simai/docara`
```

A page is a file in the `docs/` folder. Blocks are Markdown too: `:::steps`,
`:::tabs`, `:::card`.
:::

:::card
### 2. Push to GitHub

```bash
git commit -m "Installation page"
git push
```

`git push` or a pull request, just like code.
:::

:::card
### 3. The site updates itself

```text
✓ Build   ✓ Check   ✓ GitHub Pages
```

GitHub Actions builds and checks the site and deploys it to GitHub Pages.
If the check fails, the previous version stays in place.
:::
::::

:::container {width=full space=7}
A ready GitHub Actions workflow file is in
[Build in CI](/en/build/ci/).
:::

## Blocks that make up a page

Every example below is live: switch the tab to see the Markdown it is built
from.

:::::tabs
### Steps

::::example
```markdown
:::steps {current=2}
1. Create a project.
2. Add pages.
3. Publish the site.
:::
```
::::

### Cards

::::example
```markdown
::::grid {columns=3}
:::card
#### Markdown

Pages are plain text with headings and lists.
:::

:::card
#### JSON

Settings for the site, sections and pages.
:::

:::card
#### PHP

Build and checks come in one Composer package.
:::
::::
```
::::

### Tabs

::::example
````markdown
:::tabs
### Composer
```bash
composer require simai/docara:^3.0
```
### Archive
Download the archive and unpack it into an empty directory.
### Result
The finished site appears in the build directory.
:::
````
::::

### Code

::::example
`````markdown
````php {title="config/site.php" highlight=3 numbers}
return [
    'name' => 'Docara',
    'locale' => 'ru',
];
````
`````
::::

### Terminal

::::example
`````markdown
````bash {layout=terminal}
composer require simai/docara:^3.0
php vendor/bin/docara init .
php vendor/bin/docara build production
````
`````
::::

### File tree

::::example
```markdown
:::tree
- content
  - ru
    - index.md
    - section.json
  - assets
    - logo.svg
- docara.json
:::
```
::::

### Diagram

::::example
```markdown
:::diagram {title="Publishing a documentation page" label="A Markdown draft goes to the site build; if the checks pass, the page is published, otherwise the errors are fixed and the site is built again"}
flowchart LR
  draft[Markdown draft] --> build(Site build)
  build --> check{Checks passed?}
  check -->|yes| publish[Publish]
  check -->|no| fix[Fix errors]
  fix --> build
:::
```
::::

### Details

::::example
```markdown
:::details
#### What if the build stops?

Docara prints the error code, the file and what to fix. Fix the file and run
the build again.
:::
```
::::

### Alert

::::example
```markdown
:::alert {type=info}
#### Useful information

Carry on as usual.
:::
```
::::
:::::

## For teams and large projects

::::grid {space=7 columns=3 gap=2}
:::card
:icon[translate]{size=2 container=square variant=tonal scheme=primary}

#### [Several languages](/en/authoring/multilingual-site/)

Each language is its own folder. Docara sees which translation has fallen
behind the original and can publish only up-to-date pages.
:::

:::card
:icon[search]{size=2 container=square variant=tonal scheme=primary}

#### [Search without a server](/en/settings/search/)

The index is built together with the site. Search runs in the reader's
browser and sends no requests anywhere.
:::

:::card
:icon[dark_mode]{size=2 container=square variant=tonal scheme=primary}

#### [Comfortable reading](/en/settings/reader-preferences/)

Light and dark theme, font size and text width: the reader chooses.
:::

:::card
:icon[fact_check]{size=2 container=square variant=tonal scheme=primary}

#### [Checks before deploy](/en/build/verify/)

Before publishing, Docara checks links, files and search. If something is
broken, the site is not deployed.
:::

:::card
:icon[package_2]{size=2 container=square variant=tonal scheme=primary}

#### [Install with Composer](/en/build/update/)

A regular PHP package, `simai/docara`, with the version pinned in
`composer.lock`. Updating takes one command; on error the previous version
comes back by itself.
:::

:::card
:icon[menu_book]{size=2 container=square variant=tonal scheme=primary}

#### [Full documentation](/en/start/)

Every block with a live example, all settings, build and publishing.
:::
::::

## Works with AI agents

Docara works without AI too. If you write with Claude Code or Codex, connect
the Docara skill: the agent will know the site's blocks and rules and will
check its work with a build.

::::grid {columns=3 gap=2}
:::card
:icon[language]{size=2 container=square variant=tonal scheme=primary}

#### Translates pages

The agent translates, and Docara checks that the page structure is kept and
the translation has not fallen behind the original.
:::

:::card
:icon[drive_file_move]{size=2 container=square variant=tonal scheme=primary}

#### Moves content over

A README, a `docs/` folder and notes become Docara pages with a menu and
search.
:::

:::card
:icon[edit_note]{size=2 container=square variant=tonal scheme=primary}

#### Builds a page from a description

"Add an installation page with a tab for each operating system": the agent
builds it from ready-made blocks.
:::
::::

:::container {width=full space=7}
[How to connect the skill](/en/start/ai-agent/). The agent builds and checks
the site; you decide when to deploy.
:::

## FAQ

:::details {layout=lines}
### Do I need a server and a database?

No. The finished site is static files: HTML, CSS and JavaScript. PHP 8.2+,
Composer and Node.js 20+ are needed only where the site is built: on your
computer or in GitHub Actions.
:::

:::details {layout=lines}
### Can I build more than documentation?

Yes. The same blocks make a landing page, a product page or a section of
examples; there is a ready `landing` preset for the home page.
:::

:::details {layout=lines}
### How do I update Docara?

With `php vendor/bin/docara upgrade`. Docara first builds and checks the
site on the new version in a separate folder and only then replaces the old
one; on error everything stays as it was.
:::

:::details {layout=lines}
### Do I have to use AI?

No. Everything works with regular commands and file edits. The AI agent
skill is an extra option.
:::

:::details {space=7 layout=lines}
### How is Docara different from VitePress and Docusaurus?

Docara installs with Composer, like a regular PHP package, not from npm;
search, a link check before publishing and translation tracking are
already built in.
:::

:::surface {width=full tone=muted padding=xl space=0}
## Start with one sentence or three commands

Hand the site to an agent or install Docara yourself: the site builds on
your computer and in GitHub Actions.

:button[Ask an agent]{href=/en/start/ai-agent/} :button[Quick start]{href=/en/start/quick-start/ variant=outline scheme=on-surface}
:::
