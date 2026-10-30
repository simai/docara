:::hero {layout=centered padding=2xl effect=chladni effect_settings='{"speed":100,"transparency":75,"density":30,"palette":"graphite","interactive":true}'}
# A documentation site from Markdown — on PHP and Composer

Write pages in Markdown and settings in JSON. Docara builds a fast static
site from them on SIMAI Framework, with a menu, search and a dark theme. You
can publish it on any host.

Docara 3 · open source on [GitHub](https://github.com/simai/docara) ·
Apache 2.0 license

[Quick start](/en/start/quick-start/)

[See an example](/ru/components/)
:::

## A first site in three commands

Install the package with Composer, create a starter and build the site.
Building needs PHP 8.2+, Composer and Node.js 20+; the finished site needs
only a static web server.

```bash
# 1. Install Docara
composer require simai/docara:^3.0

# 2. Create a starter site
php vendor/bin/docara init .

# 3. Build the site and open it in the browser
php vendor/bin/docara serve production
```

Starters, checking the result and publishing are in the
[full quick start](/en/start/quick-start/).

## Why Docara

:::features
- :icon[package_2]{size=2 container=square variant=tonal scheme=primary} **Composer instead of npm.** Docara installs and updates like any PHP package: no npm dependencies and no theme of your own to maintain.
- :icon[bolt]{size=2 container=square variant=tonal scheme=primary} **Static output.** The build produces plain HTML, CSS and JavaScript. Put them on GitHub Pages or any web server: no PHP or Node.js there.
- :icon[palette]{size=2 container=square variant=tonal scheme=primary} **SIMAI design system.** The interface, the light and dark themes and the components come from SIMAI Framework. The build catches a wrong component call before you publish.
:::

## Documentation out of the box

Docara builds the menu, search and a comfortable reading view itself — no
theme or plugins of your own.

:::features
- :icon[account_tree]{size=2 container=square variant=tonal scheme=primary} **Menu and navigation.** Sections, breadcrumbs, the page outline and links to neighbouring pages come from the file structure. More in [Menu and sections](/en/authoring/navigation/).
- :icon[search]{size=2 container=square variant=tonal scheme=primary} **Site search.** The index is built at build time and runs in the reader's browser: the server needs no database and no external service.
- :icon[tune]{size=2 container=square variant=tonal scheme=primary} **Comfortable reading.** Readers choose a light or dark theme, the font size and the text width.
- :icon[translate]{size=2 container=square variant=tonal scheme=primary} **Several languages.** Each language is its own Markdown folder. Linked pages get a language switcher and `hreflang`; more in [Multilingual site](/en/authoring/multilingual-site/).
- :icon[web]{size=2 container=square variant=tonal scheme=primary} **A landing page from the same files.** This page is Docara too: the `landing` preset drops the documentation menu and keeps the first screen, the blocks and the call to action. How it is built is described [on a separate page](/ru/landing/) (in Russian).
- :icon[verified]{size=2 container=square variant=tonal scheme=primary} **Checked before publishing.** `verify-static` checks the finished site before it goes live: pages, local links, files and the search index. More in [Checking the build](/en/build/verify/).
:::

## Example pages

:::features
- :icon[widgets]{size=2 container=square variant=tonal scheme=primary} **[Component catalogue](/ru/components/)** (in Russian). Every component with a live example, its parameters and code to copy.
- :icon[dashboard]{size=2 container=square variant=tonal scheme=primary} **[Layout examples](/ru/development/examples/)** (in Russian). Pages with different regions, headers and setting inheritance — with their source files.
- :icon[extension]{size=2 container=square variant=tonal scheme=primary} **[Project components](/ru/project-demos/)** (in Russian). A site's own components, added without changing Docara: an install command builder and a product configurator.
:::

## Frequently asked questions

:::details {layout=lines}
### How is Docara different from VitePress and Docusaurus?

VitePress and Docusaurus are npm projects: the site is built from JavaScript
packages, and a theme that the team configures and updates sets its look.
Docara installs with Composer, keeps content in Markdown and JSON and takes
its interface from SIMAI Framework. It suits teams that work in PHP or want
documentation in the same style as SIMAI products.
:::

:::details {layout=lines}
### What does it need?

Building needs PHP 8.2+ with the `ctype`, `dom`, `intl`, `json`, `libxml`
and `mbstring` extensions, Composer and Node.js 20+: the `node` command on
`PATH` or a path in the `DOCARA_NODE_BINARY` variable. The finished site is
static and needs only a web server.
:::

:::details {layout=lines}
### Can I build a landing page?

Yes. The `landing` preset uses the same Markdown, JSON and components but
drops the documentation menu. This page is built that way.
:::

:::details {layout=lines}
### Can I add my own component?

Yes. Try plain Markdown and the ready Docara blocks first. If they are not
enough, add a component of your own through the
[documented contract](/ru/development/#как-развивать-возможности) (in Russian).
:::

:::details {layout=lines}
### How do I update safely?

`php vendor/bin/docara upgrade` prepares the new version separately, builds
and checks the site and only then applies the result. Go back to the previous
version with `upgrade --rollback=latest`.
:::

:::promo
## Build your first site

The quick start takes you from an empty folder to a finished page in the
browser.

[Go to the quick start](/en/start/quick-start/)
:::
