---
description: What Docara is, when to choose it, its limits, what the build needs and how it differs from VitePress, Docusaurus and MkDocs.
tags: [docara, overview, comparison, vitepress, docusaurus, mkdocs, requirements]
---

# What Docara is

Docara is a static site generator for documentation. You write pages in
Markdown and keep settings in a few small JSON files, and Docara builds a
finished site from them: a menu, search, a page outline, links to
neighbouring pages, live examples and one consistent interface made of
[SIMAI Framework](https://github.com/simai/ui) components. The result is
plain HTML, CSS and JavaScript that you can publish on any host.

Docara ships as the open-source Composer package `simai/docara` under the
Apache 2.0 license. The same sources build a landing page too: a page has
two ready views — documentation (`docs`) and landing (`landing`).

## When to choose Docara

- The team works with PHP and Composer and does not want to maintain a
  separate frontend build or a theme of its own.
- You need a site built entirely from files in the repository and published
  as a static folder — no database and no server code.
- Strict checking matters: an unknown setting or a wrong component parameter
  stops the build, and [`verify-static`](/en/build/verify/) finds broken links, missing anchors and
  missing files in the finished folder before publishing.
- The site has several languages, including right-to-left scripts: each
  language gets its own page tree, interface labels and address.
- The interface must match other products built on SIMAI Framework.

## Limits

- **Static sites only.** There is no project PHP code on the server: forms,
  sign-in and other server features are not part of Docara.
- **No arbitrary HTML or scripts in pages.** Raw HTML in Markdown is removed,
  and extensions come in as checked components with described parameters,
  not as arbitrary code.
- **One documentation version per build.** For another product version you
  build a separate variant of the site into its own folder; there is no
  built-in version switcher.
- **Simple search.** Search runs in the browser and matches word beginnings,
  without typo correction or stemming. Synonyms are set with page tags.
- **A bounded set of components.** The components in the
  [catalogue](/en/components/) are available; a developer adds a
  new one along a [checked path](/en/development/smart-components/).
- **The `showcase` starter is in Russian only for now.** The `minimal`
  starter creates a site in English or Russian.

## What it needs

On the computer or server that builds the site:

- PHP 8.2 or newer with the `ctype`, `dom`, `intl`, `json`, `libxml` and
  `mbstring` extensions;
- Composer;
- Node.js 20 or newer, available as the `node` command. Commands that do not
  build the site work without Node.js.

A pinned SIMAI Framework release is part of the Docara package; you do not
install it separately. The finished site needs only a web server for static
files: no PHP, Composer or Node.js on the host.

Readers need a modern browser. Docara and SIMAI Framework use web platform
features that all major browser engines support — Chromium, Gecko and
WebKit. Anything only one engine has works as an enhancement: the site works
the same without it.

## Compared with other generators

All four tools build a static documentation site from Markdown. They differ
mostly in the build environment and in how you extend them.

| | Docara | VitePress | Docusaurus | MkDocs |
| --- | --- | --- | --- | --- |
| Build environment | PHP, Composer and Node.js | Node.js | Node.js | Python |
| Page format | Markdown and JSON settings | Markdown with Vue components | Markdown and MDX with React components | Markdown and YAML settings |
| Interface | SIMAI Framework | default Vue theme | default React theme | themes such as Material for MkDocs |
| Extending | checked components with described parameters | Vue components and Vite plugins | plugins and React components | Python plugins |
| Documentation versions | a separate build per version | by hand | built in | third-party plugins |
| Search | built in, in the browser | built in or an external service | an external service or plugins | built in, in the browser |

Choose Docara if the project already lives in PHP and SIMAI Framework, or if
strict build checks matter more than freedom to write pages with your own
JavaScript. If the team writes Vue or React and wants its own interactive
components inside pages, VitePress or Docusaurus fit better; if the team's
main language is Python, MkDocs is more convenient.

## See also

- [Get started](/en/start/) — the whole path from meeting Docara to a first build
- [Quick start](/en/start/quick-start/) — a first site in four steps
- [Project files](/en/start/project-files/) — what a project is made of
- [How components work](/en/start/component-model/) — Markdown and components
