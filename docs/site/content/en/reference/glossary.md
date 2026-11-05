---
description: A glossary of Docara terms — lock, manifest, preset, Smart component, build receipt, value provenance and other words found in the documentation and in error messages.
tags: [glossary, terms, lock, manifest, preset]
---

# Glossary

Some Docara terms are technical names: that is how they appear in files,
settings fields and error messages. This page collects such words with an
explanation and a link to the page that covers the topic in detail. The
other references are in [Reference](/en/reference/).

## Project and settings

- **Starter (template)** — the set of files from which `docara init` creates
  a project: `minimal` or `showcase`. See
  [Starters and environment](/en/start/templates/).

- **Page metadata (front matter)** — the block between `---` lines at the
  start of a Markdown file: the title, the description, tags and a few other
  keys. See [Markdown](/en/authoring/markdown/#page-metadata).

- **Page file (sidecar, `<page>.page.json`)** — an optional JSON file beside
  a Markdown page with settings for that page only. See
  [Site, section and page](/en/settings/levels-and-inheritance/).

- **Page view (preset)** — `docs` for documentation with a menu and contents,
  or `landing` for a landing page without them. See
  [Layout regions](/en/settings/layout-and-regions/#two-page-views).

- **Reset (`$reset`)** — a key that clears an inherited settings branch. See
  the [inheritance and reset examples](/en/settings/levels-and-inheritance/#inheritance-and-reset-examples).

- **Schema** — the JSON Schema a settings file must match; the `schema` field
  in each file names it. See [JSON schemas](/en/reference/schemas/).

- **Route and slug** — the path of a page on the site. By default the route
  repeats the path of the Markdown file; `slug` in the page file sets another
  one. See [Menu and sections](/en/authoring/navigation/).

- **base_url and site_url** — the path under which the site is placed on the
  server, and the full public address of the site. See
  [Site address and search engines](/en/settings/site-address/).

- **Locale** — a site language given by a BCP 47 tag, for example `ru`, `en`
  or `fr-CA`. A **fallback locale** is the language a missing interface label
  is taken from. See [Multilingual site](/en/authoring/multilingual-site/).

- **Ownership** — the list in `.docara/engine/ownership.json` of which files
  belong to the Docara package (**package-owned**) and which to the project
  (**project-owned**). A Docara update changes only package files. See
  [Updating Docara](/en/build/update/).

- **Redirect** — a page at an old address that leads to the new one. See
  [Redirects](/en/authoring/redirects/).

## Components and interface

- **SIMAI Framework** — the interface library whose components Docara builds
  the site from. Its exact version ships in the Docara package.

- **Framework lock (`simai-framework.lock.json`)** — the pinning file: the
  exact SIMAI Framework version, the component descriptions and the hashes of
  the files the site uses. See [Framework lock file](/en/reference/framework-lock/).

- **Manifest (component description)** — the machine-readable description of
  a Smart component: parameters, states, templates, assets and version. The
  build checks a component call against it. See
  [Smart components](/en/development/smart-components/).

- **Smart component** — a self-contained interface element with a manifest,
  for example `ui.button` or `docara.navigation`. See
  [Smart components ui.*](/en/components/smart-ui/).

- **Typed Docara component** — a Docara block with a fixed syntax and
  parameter checks, for example `:::card` or `:::steps`. An **inline
  component** is the same inside a line of text, for example `:badge[...]`.
  See [How components work](/en/start/component-model/).

- **Props and slots** — the named values of a component call, for example
  `type=info`, and the places for nested content, for example the text inside
  a button. The build checks both against the component description.

- **Namespace and provider** — the prefix of a component name (`ui.`,
  `docara.` or the project name) and the owner responsible for all components
  with that prefix. See [Smart components](/en/development/smart-components/).

- **Renderer** — the part of the build that turns Markdown or a component
  call into HTML.

- **Native Markdown** — plain Markdown without components: headings, lists,
  links, tables, code and footnotes.

- **Component status (lifecycle)** — `supported` for available components;
  `admission_pending`, `framework_gap` or `deferred` for those not yet
  admitted. See [Component catalogue](/en/components/).

- **Component admission** — the check after which a SIMAI Framework component
  can be used in Docara: its description, parameters, templates and examples
  are checked against the pinned Framework version. Before admission the
  component has the status `admission_pending`.

- **Layout** — the scheme of a page: which regions it has and what stands in
  them. **Layout regions** are the five parts of a page: `header`, `sidebar`,
  `main`, `outline` and `footer`. A **section** fills a region, and a
  **block** fills a section. See [Layout regions](/en/settings/layout-and-regions/).

- **View Tree** — a checkable markup tree of a layout or section: allowed
  tags, attributes and SIMAI Framework utilities instead of arbitrary HTML.
  See [Interface composition](/en/development/composition/).

- **View** — a registered output variant of a component or section: a
  template or a markup tree. For example, `docara.navigation` has the views
  `header`, `tree` and `compact`. See
  [Interface composition](/en/development/composition/).

- **Site shell** — the shared parts around the page text: the header, the
  menu, the contents, search and the footer. A project can replace them
  through bindings. See
  [Interface composition](/en/development/composition/#shell-parts).

- **Data binding** — a data source for a shell Smart component, for example
  the menu tree for `docara.navigation`.

- **Atlas** — the list of all layouts, sections, blocks, views and bindings
  available in the project; `docara atlas` prints it. See
  [Interface composition](/en/development/composition/).

- **Hero and Surface** — the first screen of a landing page and a styled band
  around a part of a page. An **overlay** is a darkening layer over a
  background image that keeps the text on it readable. See
  [First screen](/en/components/hero/) and [Surface](/en/components/surface/).

## Build and checks

- **Build environment** — the name after `build`, for example `local` or
  `production`; the result is written to the folder `build_<environment>`.
  Production is the working public site; staging is its test copy before
  release. See [Building the site](/en/build/php-only/).

- **Asset** — a file a page needs besides the HTML: styles, scripts, fonts,
  images. The build copies the assets in use into the result and records
  their hashes.

- **Runtime** — the CSS and JavaScript of SIMAI Framework and of the
  components that run in the reader's browser. Its exact version is pinned in
  the Framework lock.

- **Revision** — an exact version of source code in Git, recorded as a commit
  hash. The Framework lock pins SIMAI Framework revisions.

- **Artifact** — the finished result of one delivery or build step: the
  component files from the Framework delivery, a plan or a page document.

- **Canonical address** — the main address of a page for search engines.
  Docara writes it into every page and, with `site_url`, makes it absolute.
  See [Site address and search engines](/en/settings/site-address/).

- **Build receipt** — a service file beside the result, in the folder
  `build_<environment>.docara/`: what was built and from what. Receipts are
  read by `verify-static`, `build --page` and `inspect`.

- **Resolved page plan** — the final settings of a page after inheritance,
  with their sources. See
  [JSON schemas](/en/reference/schemas/#the-page-plan-after-the-build).

- **Value provenance** — the record of which file set each settings value.
  See [Where a value came from](/en/settings/levels-and-inheritance/#where-a-value-came-from).

- **Composition Recipe and Composition Document** — the page description
  that Docara passes to the SIMAI Framework builder, and the finished page
  tree that it returns. See [Architecture](/en/development/architecture/).

- **verify-static** — the command that checks the finished folder: pages,
  links and anchors, assets, search and receipts. See
  [Checking the build](/en/build/verify/).

- **Fail closed** — the rule that on invalid data the build stops and does
  not try to guess the author's intent. See
  [Security](/en/reference/security-and-errors/).

- **Hash (SHA-256)** — a fingerprint of a file's content. The build uses it
  to notice that a file has changed.

## Development

- **Dry-run and hash-bound apply** — first the command shows a change plan
  and its identifier, then it applies exactly that plan; if the input files
  have changed, the apply is rejected. See
  [SDK for developers and AI agents](/en/development/developer-sdk/).

- **Preview** — a build of one page, region or component into the folder
  `.docara-preview/` for checking. Such a result does not reach the site:
  `verify-static` rejects it. See
  [Interface composition](/en/development/composition/#preview).

- **Fixture** — a checkable example of a component call that tests compare
  the result against.

- **SDK and MCP** — commands for developers and AI agents (`inspect`,
  `schema`, `scaffold`, `validate`, `preview`, `test`, `qa`) and an optional
  adapter that exposes the same commands over the MCP protocol. See
  [SDK for developers and AI agents](/en/development/developer-sdk/).
