# Project files

A new project consists of three JSON files at the root, a folder with the
page texts and a service file where Docara records which files belong to
it.

## Project tree

```text
docara.json
redirects.json
simai-framework.lock.json
assets/
  logo.svg
  logo-dark.svg
  favicon.svg
examples/
  utilities/
    animation-duration/
      index.html
      index.css
      index.js
      assets/
content/
  en/
    lang.json
    section.json
    index.md
    guides.md
    guides/
      section.json
      install.md
      install.page.json  # optional settings of this one page
translations.lock.json  # optional accepted translation state
.docara/
  engine/
    ownership.json
```

## Site, section and page settings

`docara.json` holds the shared settings and names the `redirects_file`.
`redirects.json` keeps old internal addresses and the pages they lead to; if
you do not need address compatibility, you can skip the field and the file.
Each `section.json` applies to its folder and the folders inside it. Do not
create one if a branch needs no settings of its own.

A page file `<page>.page.json` overrides the settings of one page and is
optional too: `install.md` builds without `install.page.json`, inheriting the
site and parent section settings. You need the JSON only to change the slug,
the page view, navigation, layout or another behaviour of that page. Set
`title`, `description`, `tags`, `draft` and `translation_key` in the
Markdown front matter.

The recommended section shape is flat: `content/en/guides.md` owns the
address `/en/guides/`, and the neighbouring folder `content/en/guides/` holds
the child pages and `section.json`. The build also understands
`content/en/guides/index.md`, but both shapes at once are not allowed: the
build stops with `PAGE_SOURCE_ROUTE_AMBIGUOUS`.

Every level uses the same name, `section.json`, without a leading
underscore. If an old project still has `_section.json`, rename it: the build
stops with `SECTION_DESCRIPTOR_LEGACY_NAME`, so settings are not lost
silently.

## What Docara creates

`build_local` and `build_production` are build results, not source files.
The build also creates the `_docara` service folder inside them.

`.docara/engine` holds service files that belong to the Docara package and
their list, `ownership.json`. Next to it the update command keeps a plan and
checked packages for rollback for a while. Do not edit these files by hand.

## Your files

`content`, `assets`, the root JSON files and your `composer.lock` belong to
the project: updating Docara does not change them.

- `examples/` holds the sources of live examples. One example folder can
  serve several pages and languages.
- `translations.lock.json` changes only through the protected command
  `translations accept`; status checks and builds do not overwrite it.
- The root `assets/` is handy for logos and the site icon; images for the
  text can sit next to the Markdown. Brand file paths are set in
  `docara.json` and checked before the previous build is cleared.

Next: [site, section and page settings and their inheritance](/en/settings/levels-and-inheritance/).
