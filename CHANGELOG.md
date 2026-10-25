# Changelog

All notable changes to Docara are recorded here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and Docara uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Changed

- **Example frames ask for the Framework scripts all at once.** A sandboxed
  example frame used to request each Framework script the page sends it only
  after the one before it had loaded, and the frame's opaque origin shares no
  cache with the page, so every frame paid one round trip per script before
  Core started (about 2.7 s of the 3–3.4 s a component-package example such
  as `sf-property-view` took on ui.simai.io). The frame now inserts them
  together with `async=false`: they download in parallel and still run in
  the page's order. With a 100 ms delay per request in a local build of the
  SIMAI Framework documentation, `sf-property-view` renders in its frame in
  2.3 s instead of 3.3 s, and `sf-table` in 1.1 s instead of 3.5 s. Frames
  keep receiving the page's boot config, including the component-package
  declaration in `SF_BOOT_CONFIG.packages`; a test now pins that
  ([#35](https://github.com/simai/docara/issues/35)).
- **"Композиция интерфейса" is plain Russian.** The page keeps every fact
  and is split into sections by task: the three levels, the path from file
  to page, compatibility checks, the atlas, the shell parts, data bindings,
  project layouts and blocks, project shell contributions and preview, with
  a "См. также" list. It drops two atlas listings that repeated the first
  one and names the class that places regions into the layout,
  `DeclarativePageRenderer`, instead of a `LayoutComposer` that does not
  exist. The quality report measures the page at 1921 words, 8% of them
  Latin (it was 3860 words, 82% Latin). The glossary gains "Представление
  (view)", "Оболочка сайта (shell)" and "Предпросмотр (preview)".
- **The Design Atlas index shows identifiers as code, in the page
  language.** Package names, namespaces, origins, providers, authoring kinds,
  capabilities and container rules are code, and their labels are build
  strings in English and Russian (`atlas.namespace`, `atlas.origin`,
  `atlas.provider`, `atlas.authoring`, `atlas.capabilities`,
  `atlas.container`).

## [3.9.0] - 2026-10-25

### Added

- **Starters carry the documentation standard.** Every `docara init`
  template and language (`minimal` in English and Russian, `showcase` in
  Russian) now ships `docara.authoring.json` and a skeleton organised by page
  type: `getting-started/` (tutorial), `how-to/` (how_to), `reference/` and
  `explanation/`, each with one short example page of its type in the
  starter's language. The example pages meet the structure of their types.
- **Docara infers a page profile when none is set.** When a page gets no
  profile from front matter, a path rule or `default_profile`, `docara
  validate page` guesses one from the path and the Markdown: `index.md` is a
  landing page, a path segment such as `how-to/` or `reference/` names the
  type, three or more numbered steps make a how-to guide, tables, lists and
  definitions on at least 60% of the lines make a reference page, and
  anything else is an article. The guess is reported as the advisory
  `PAGE_PROFILE_INFERRED`, its structural misses are advisory too, and the
  build never counts or fails on them. Inference runs only when
  `docara.authoring.json` exists; `"infer_profiles": false` switches it off.
  Explicit profiles always win.
- **`AGENTS.md` for AI assistants.** `docara init` writes an `AGENTS.md` at
  the project root: what the project is, the page types and their required
  parts, the writing rules, how to create a page of a type and the commands
  to run before finishing. An existing `AGENTS.md` is never overwritten:
  `init` accepts a directory that holds one and writes the guide to
  `docs/AGENTS.docara.md` instead. The same standard is the
  `documentation_standard` field of `docara capabilities --json` and the
  read-only MCP tool `docara_authoring_standard`, which also returns the
  project's `AGENTS.md` text. The AI contract version is now 1.2.0.
- **Page templates by type.** `docara scaffold page --type=how_to
  --title="..."` creates a page with the sections of its type, a
  `description` placeholder, `profile` and `draft: true`, written in English
  or Russian after the locale (other locales use English). `--type` is the
  same as `--profile`, `--locale` defaults to the site's default locale, and
  without a route the page goes into its type's skeleton directory with a
  slug made from the title. On the command line a page is created in one
  step; `--dry-run` keeps the reviewed plan-and-apply flow, which Smart and
  design scaffolds still require.
- **Documentation quality report.** `docara qa report` scores a built site
  from 0 to 100 without a network and without AI. Twenty-seven checks with
  stable `QUALITY_*` codes fall into six groups: completeness (profile parts,
  description, stubs, scaffold leftovers, links to drafts), freshness
  (documentation tracking), findability (menus, search, inbound and related
  links, tags), readability (sentence and page length, subheadings, heading
  depth, Latin share in non-Latin locales, glossary terms), accessibility
  (alt text, generic link text, heading order and levels, table headers,
  `lang`) and links (broken links and anchors, by the rules of
  `verify-static`). A failed check costs its group 25 points (warning) or 10
  (advice) once per page; the page score is the weighted mean of the groups
  (completeness 25, freshness 10, findability 20, readability 15,
  accessibility 15, links 15; a group that does not apply is left out) and
  the site score the mean of the pages. The command prints the score, the
  group scores, a "fix first" list ranked by the site points each fix gains
  and the biggest gains by check, and writes
  `build_<env>.docara/quality-report.json`; `--html` adds a standalone page
  next to the build that is never published. `--min-score` or
  `quality.min_score` in `docara.authoring.json` makes it exit 1 for CI;
  `quality.glossary` and `quality.exclude` name the glossary page and pages
  left out. Pages with `noindex` are not scored.
- **Review packets for the semantic review.** `docara qa review-packet
  [route]` writes, for one page or the whole site, the questions only a
  reader can answer (accuracy against the sources, clarity for the audience,
  does the page solve the task, do the examples work) with the page text,
  its profile and editorial checklist, its tracked sources and the findings
  of the last report, as JSON and Markdown in
  `build_<env>.docara/review-packets/`. Docara never answers them itself. The
  MCP adapter exposes both commands as `docara_qa_report` and
  `docara_qa_review_packet`, the documentation standard lists them, and the
  `AGENTS.md` that `docara init` writes asks AI assistants to run them.
- **Search finds pages by their front matter `tags`.** A tagged page's
  search document carries a `tags` field (left out for an untagged page, so
  existing indexes do not change), and `search.js` ranks a tag match below a
  title match and above heading and body matches. A tag may now be lowercase
  letters of any script, digits, `.`, `_` and `-` (`tags: [деплой, i18n]`);
  uppercase letters and spaces are still refused with
  `FRONT_MATTER_TAG_INVALID`. The documentation tags its install, publishing,
  hosting, icon, effects, localization and branding pages with the words
  readers search for ("деплой", "иконки", "анимация", "i18n", "логотип").
- **Documentation: icons, effects, landing blocks and Smart components.**
  - The icon page explains Material Symbols (outlined, rounded and sharp,
    three weights, fill), the static catalogue and when the full variable
    font loads (`thin`, `bold`, a custom grade or optical size), and lists
    all 3,926 icon names, generated from the bundled catalogue by
    `scripts/capture-icon-names.php` and kept current by a test.
  - A new Background effects page covers `effects.catalogue`, choosing an
    effect on effects.simai.io, `effect_theme`, `effect_settings`, the pause
    button and reduced motion, what a Content-Security-Policy must allow and
    the diagnostics; the hero page links to it.
  - A new Landing blocks page documents `features`, `showcase`, `promo`,
    `columns` and `cta` with live examples.
  - A new Smart components page explains `ui.*`, how
    `simai-framework.lock.json` admits a component and refuses values, with
    live `ui.button` and `ui.alert` examples.
  - The components overview gives every component a one-line description.
- **Documentation: diagram gallery.** A new Components > Diagram gallery
  page shows all 22 Mermaid diagram types checked in a browser with the
  bundled Mermaid 11.16 (flowchart, sequence, state, journey, class, ER,
  requirement, mindmap, treemap, block, packet, C4 context, architecture,
  Gantt, timeline, gitGraph, kanban, pie, XY chart, quadrant, sankey and
  radar), each with a use case from documentation work, a live diagram and
  its source. The Diagram page is rewritten: how to insert a diagram, that
  `:::diagram` accepts any Mermaid type, the types grouped by purpose, the
  label rules (`sankey-beta` renders only Latin labels; `architecture-beta`
  and `xychart-beta` need non-Latin labels in double quotes; `erDiagram`
  takes Russian names as aliases), that strict mode ignores `click`, that
  ZenUML is not bundled, and four live examples. The gallery has its own
  page-weight budget of 6.9 MB, like the Diagram page: Mermaid loads once
  for all its diagrams.
- **`scripts/verify-mermaid-gallery.mjs`** renders every diagram of the
  Diagram page and the gallery with the bundled Mermaid in headless Chrome,
  with the site's options, using Node built-ins only; it exits non-zero when
  a diagram does not render. `MermaidDiagramGalleryTest` keeps the gallery's
  list of types in step with the detectors in the bundled Mermaid, so a
  Mermaid upgrade that adds a type fails until the gallery shows it or lists
  it as not shown.
- **`AGENTS.md` covers diagrams.** A short Diagrams section tells AI
  assistants which Mermaid type to pick for which purpose and the
  `sankey-beta` and `architecture-beta`/`xychart-beta` label rules.

### Changed

- **Updated Docara logo.** The blue logo shipped in 3.8.0 is replaced by the
  updated one: the wordmark "docara" with a red ring (`#E81123`) as the
  first "o". Docara's own documentation shows it in the header (a light and
  a dark file, still 24 px high), uses the red ring as its SVG favicon and
  has a redrawn share image. The starter templates (`docara init`), the
  component catalogue examples and the documentation's image examples use the
  red ring as `docara-mark.svg`, and the starter `favicon.ico` is redrawn from
  it (16, 32 and 48 px); file names are unchanged. The "Made with Docara"
  credit draws the new wordmark in two gray tones of the credit colour, as in
  the owner's gray logo files: the letters
  `color-mix(in srgb, currentColor 65%, transparent)` and the ring
  `color-mix(in srgb, currentColor 60%, var(--sf-on-surface))`
  (`--docara-credit-wordmark`, `--docara-credit-mark`), one inline SVG for
  both themes; `branding.credit: false` works as before.
- **`docara build` prints one documentation quality line.** When the project
  has `docara.authoring.json`, the build ends with
  `Documentation quality: 82/100, 5 page(s) need attention (docara qa report).`
  instead of the page authoring count. A project without that file sees
  nothing new, and the build never fails on quality. `docara qa` without a
  kind now points to `qa report` in its `QA_DRY_RUN_REQUIRED` message.

- **The documentation site has nine sections, and each topic lives on one
  page.** The menu now runs Начало работы, Написание страниц, Компоненты,
  Настройка сайта, Сборка и публикация, Справочник, Решение проблем, Миграция
  and Разработка Docara, every page ordered with `navigation.order`, and has
  83 entries instead of 114.
  - The pages that the writing, settings, reference and design sections
    repeated are merged: configuration, inheritance and the levels page
    become one settings page; branding, regions, search, reading context and
    the reader panel each have one page under Настройка сайта; the
    schema-derived field tables live only in the reference (docara.json,
    section.json and page.json, lang.json, Framework lock); the menu,
    hierarchy and navigation pages become Меню и разделы, and the locale
    pages join the multilingual site guide.
  - The five native Markdown component pages join the Markdown page, the
    build output and determinism pages join the build page, and the 2.x
    upgrade guide joins the update page.
  - The design section becomes one composition page under Разработка
    Docara, the layout examples overview moves there too, and the example
    stands stay built but leave the menu and the search index.
  - New pages: Что такое Docara (when to choose it, limits, requirements and
    a comparison with VitePress, Docusaurus and MkDocs), the init templates
    page split out of the quick start, and a glossary of the terms that stay
    English.
  - Menu titles are Russian ("Бренд и тема", "Области макета", "Публикация
    и откат"), and every page that had no link in its text ends with a
    "См. также" list.
  - Every old address still works: `redirects.json` redirects the 41 moved
    pages under `/ru/` and under the unprefixed address the locale
    publisher made for them, and a test checks every HTML output of the
    site built before the restructure against the new pages and redirects.
  - The component catalogue cites the new pages (`docara.internal_preview`
    the composition page, `docara.schema_reference` the docara.json
    reference), and the README links the new addresses.
- **An unknown icon name stops the build.** `:icon[setings]` used to render
  the letters of the name instead of an icon. The build now checks the name
  against the bundled icon catalogue and stops with
  `CONTENT_COMPONENT_ICON_UNKNOWN`, naming the file and line. Names in HTML
  examples and Smart component props are not checked.

- **Two quality checks report fewer false findings.**
  - `QUALITY_TOPIC_UNCLEAR` compares the title with the description and the
    first paragraph together (before, the first paragraph counted only when
    there was no description). It splits hyphenated words
    ("Smart-компоненты"), ignores words shorter than four letters and
    matches word forms by a light prefix rule: two words match when they
    share five letters, or four letters with at most three more in each
    ("ошибки" and "ошибок"). A title with no word of four letters or more
    ("Код") is not reported.
  - `QUALITY_TERM_NOT_IN_GLOSSARY` no longer counts everyday English words
    such as source, target, output or title: they keep their dictionary
    meaning and are not terms, while `QUALITY_LATIN_HEAVY` still counts
    them. A plural and its singular ("assets", "asset") are one term, and a
    glossary entry in either form, or a hyphenated entry such as
    "hash-bound", defines them.
  - The score formula is unchanged. On Docara's own docs the two checks
    went from 29 and 23 pages to 23 and 15 before any text was changed.
- **Docara's own documentation scores 100 instead of 93.**
  - Every page has a description that says what the reader gets.
  - The short component pages (code from file, isolated HTML, kbd, logos,
    math, media) list their parameters, defaults, limits and errors.
  - The glossary defines asset, runtime, revision, artifact, admission,
    props and slots, namespace and provider, renderer, route, layout,
    ownership, overlay, canonical, production and staging, as a list.
  - Nine developer and reference pages use Russian words where a natural
    one exists; the composition guide is still mostly English.
  - The hidden example stands under `content/ru/examples/` are left out of
    the score with `quality.exclude`: they are hidden from the menu and the
    search on purpose, so scoring them only reported that intent.

### Fixed

- **Builds no longer depend on the libxml version.** libxml before 2.14
  wrote an attribute value that contains a double quote in single quotes,
  2.14 and later in double quotes with `&quot;`, so the same page built to
  different bytes on different machines. Docara now writes such values in
  double quotes with `&quot;` on every libxml version.

## [3.8.0] - 2026-10-23

### Added

- **Settings tables explain each field.** `:::schema_reference` tables show
  the field, what it does and its default; type, level, validation rule and
  schema pointer move into a "Technical details" disclosure under each row.
  Russian descriptions for all 180 `docara.json`, `section.json` and
  `page.json` fields ship in `resources/schema-descriptions/ru.json`, keyed by
  schema pointer; other languages show the schema's English `description`.
  Defaults are the values the build applies, taken from the new
  `ConfigurationDefaults` (the loader's `@defaults` layer and the fallbacks for
  `settings.*`, `branding.mode`/`size`/`credit`, `navigation.scope`, `title`
  and `documentation_version` now read from it). Pattern keys such as
  `<^[a-z][a-z0-9_.-]+$>` show as `<region>` or `<locale>`, with the exact
  path in the details.
- **`schema_reference` takes `fields=`**, a comma-separated list of top-level
  fields, so a long reference can be split under headings; an unknown field
  stops the build with `MARKDOWN_SCHEMA_REFERENCE_FIELDS_INVALID`.
- **Documentation: error code reference.** A new Reference > Error codes
  section gives a cause and a fix for 375 codes and all 31 `verify-static`
  markers: pages and Markdown, site settings (redirects, locales, `lang.json`,
  translations, search, effects, 404 page, `site_url`, theme, brand, portal)
  and upgrade. Troubleshooting, security and verify pages link to it. A test
  collects the codes from `src/` and fails when the docs name a code Docara
  no longer raises or when a code of the common families has no entry;
  `docs/diagnostic-code-coverage.json` lists the codes not documented yet.
- **Documentation: every `lang.json` string.** Authoring > Language packs
  lists all interface strings with where they show and their English and
  Russian text, generated from `UiCopy` by
  `scripts/capture-lang-reference.php`. A new page documents
  `docara.authoring.json` and the six page profiles.

- **A Markdown link that opens search: `[text](#docara-search)`.** Any page,
  including a site's own `404.md`, can now link to the search dialog the way
  the built-in 404 page does. The reserved fragment works in paragraphs,
  lists, hero actions and CTAs. The build turns the link into a link to the
  page locale's home page marked `data-docara-search-open`, which
  `_docara/search.js` opens the dialog for; without JavaScript it leads home.
  On a page with `search.enabled: false` it is an ordinary link to the home
  page. Documented on the search authoring page and in the 404 section of
  build/hosting.

### Changed

- The starter templates (`docara init`), the component catalogue examples and
  the documentation's image examples use the new blue Docara mark instead of
  the old red one, and the starter `favicon.ico` is redrawn from it (16, 32
  and 48 px). File names are unchanged, so sites and links need no edit.
- **Documentation: settings pages are split into titled groups.** Site, page,
  section and Framework lock settings show their field tables under H2
  groups instead of one long table.
- **A brand in `logo` mode keeps the logo's proportions.** The header brand
  box was square in every mode, so a wide logo (mark and wordmark) shrank to
  fit the square. In `branding.mode: "logo"` the box now takes the logo's own
  width and `size` sets only its height (`small` 24 px, `medium` 32 px,
  `large` 40 px); square logos look the same as before. The other modes are
  unchanged.
- **The documentation home page has no screenshots.** The site will be
  translated, so the home page drops the images that carry text: the hero
  relies on its background effect, the two showcase blocks become a
  "Documentation out of the box" features grid with Material Symbols icons,
  the feature cards use icons instead of PNG pictures, and the "Made with
  Docara" showcase is gone (the documentation itself is built with Docara).
  The five unused screenshots and three feature images are deleted.
- **The "Made with Docara" credit shows the Docara logo.** Under the sidebar
  navigation the credit now reads "Made with" (Russian: «Сделано на») followed
  by the small Docara logo (14 px high) instead of the word "Docara". The
  logo is an inline SVG drawn with `currentColor`: the wordmark takes the
  credit's `on-surface-variant` colour and the mark a lighter mix of it
  (`--docara-credit-wordmark`, `--docara-credit-mark`), so one logo serves
  the light and the dark theme. The whole line is one link to
  <https://docara.simai.io/> with the accessible name and tooltip
  "Made with Docara", no underline, a darker colour on hover and a visible
  focus ring. `branding.credit: false`, the pinned sidebar footer and the copy
  in the mobile navigation sheet work as before.
- **Docara's own documentation uses the new Docara logo.** The header shows the
  full logo (`mode: "logo"`, 24 px high, a light and a dark file), the favicon
  is the SVG mark, and the share card shows the new logo.

### Fixed

- **The 404 page has no lone reading toolbar button.** The button reported as
  the 404 page's contents toggle is the reading mode toggle (the `article`
  icon): the outline toggle is already rendered only for a page with outline
  entries, on the 404 page and on any page without headings (checked on the
  documentation build: 107 pages have the toggle, none of them without an
  outline). Reading mode on the 404 page would hide the header with the
  search, so that page no longer renders the reading mode toggle; the exit
  button stays for a reader who arrives in reading mode, and
  `declarative-shell.js` now binds it when the page has no toggles. Ordinary
  pages without headings keep the reading mode toggle.
- **A hero with a background effect keeps its height again.** 3.7.0 gave the
  effect's pause button its own row under the hero content, so such a hero
  grew by 48 px on a phone and 56 px on a wide screen. The button now sits in
  the hero's bottom padding at the inline end (left in RTL), below the content
  and media. A `padding=2xl` hero (3rem and more) holds it as it is and keeps
  its height: the button keeps `--sf-space-1` from the bottom, or the middle
  of the padding where it is barely taller than the button. An `lg` or `xl`
  padding (16 to 32 px) is lower than the 36 to 40 px button, so a hero that
  shows the button gets a bottom padding of the button plus `--sf-space-1/3`
  above and below it: 52 px on a phone and 56 px from 960 px, 24 to 28 px
  more than without the button instead of 48 to 56 px. The padding is kept
  while the button waits for the effect to start and is not grown with
  reduced motion, where the button never shows. Measured in headless Chrome
  at 390, 768, 1440 and 1920 px, light and dark, LTR and RTL: no overlap with
  the hero content or media.
- **A features card can open with an icon.** In a `:::features` item, an
  `:icon[...]` placed first (where an image would go) becomes the card's icon:
  a row of its own above the bold title, like a feature image, instead of an
  inline glyph inside the text. An icon later in the text stays inline, and
  other inline components (a badge) are never lifted. Material Symbols icons
  replace raster images that only served as icons.

## [3.7.0] - 2026-10-21

### Added

- **A 404 page.** The build now writes `404.html` at the deployment base: a
  "Page not found" page in the default locale with the site's normal header,
  navigation, search and theme, a short text, a link to the home page and a
  link that opens search (`a[data-docara-search-open]`, handled by
  `_docara/search.js`; without JavaScript it leads home). The text comes from
  the new `error.not_found.*` strings, built in for English and Russian and
  overridable in the default locale's `lang.json` (`error` is a new
  `lang.json` group). A site replaces the content with its own `404.md` at the
  root of the default locale's content, which is published as `404.html`
  instead of `<locale>/404/`. `docara.json` `"not_found": {"enabled": false}`
  turns the page off, and the build is then what it was before. Everything
  the page links to is addressed from the site root with `base_url`, and a
  relative URL in a site's `404.md` is resolved from `<locale>/404/`, so the
  page works at whatever missing path the host serves it. It is `noindex`, has
  no `hreflang` alternates, and is left out of the navigation, backlinks,
  search index, `sitemap.xml`, `llms.txt` and `_docara/page-metadata.json`;
  `resolved-page-plans.json` lists it with `"not_found": true`, and
  `verify-static` checks it like any page. A portal build still has no
  `404.html` (the portal owns it), and `not_found.enabled: true` there stops
  the build with `PORTAL_NOT_FOUND_PAGE_FORBIDDEN`. Sites with several
  locales get one root `404.html` in the default locale. The documentation's
  hosting page shows how to serve it on nginx (`error_page 404 /404.html;`),
  Apache (`ErrorDocument 404 /404.html`) and GitHub Pages (automatic).

### Fixed

- **A landing hero with `padding=2xl` keeps its tall padding on narrow
  screens.** On a landing page a narrow-screen rule (`max-width: 800px`) set
  every section container's vertical padding to `--sf-space-2`, and it
  outranked the hero's fluid `clamp()`, so up to 800 px a `:::hero
  {padding=2xl}` got 16 px instead of the clamp: at 390 px a measured hero
  was 324 px tall instead of 388 px (48 px of padding each side), at 768 px
  228 px instead of 336 px. The narrow rule now skips `docara-padding-2xl`
  containers, so 2xl follows its clamp at every width; other landing
  sections and `lg`/`xl` hero padding still tighten to one spacing step on a
  narrow screen, as before.
- **Docara's own breakpoints are in rem, like the SIMAI Framework's.** The
  shell stylesheet, the shell script and the brand stylesheet used pixel
  media queries. They now use the same widths in rem: 72rem and 60rem (the
  Framework's xl and lg steps, formerly 1152px and 960px), 50rem, 43.75rem,
  40rem and 37.5rem (formerly 800, 700, 640 and 600px). At the default 16 px
  browser text size the layout changes at exactly the same widths; with a
  larger browser text size the breakpoints scale with it. The script now
  closes the navigation and outline sheets with the exact complement of the
  stylesheet's query (`not all and (max-width: ...)`), so the two can no
  longer disagree at a fractional width such as 1152.5 px.
- **Each relative image in an example frame is fetched once.** A project
  example's frame document names its folder with a path-only
  `<base href="/_docara/examples/<id>/">`, already first in `<head>`.
  Chromium's preload scanner does not apply a path-only base in an
  `about:srcdoc` document, so it fetched every relative image against the
  documentation page first (a 404, then `ERR_BLOCKED_BY_ORB` in the
  sandboxed frame) and the parser fetched it again from the right folder; 44
  pages of ui.simai.io did this. The shell now makes the base absolute with
  the page's origin when it moves the document into `srcdoc`, and the
  scanner honours it. Built pages are unchanged; a page opened from
  `file://` keeps the path-only base.

- **A page showing the source text marks that text with the source
  language.** Under `translation_tracking.mode: "publish-current"` a held-back
  page shows the source page with a notice, but only `<html lang>` named a
  language, the page locale's: a screen reader read the Russian text of an
  English URL with an English voice. The article now carries the source
  locale's `lang` (and `dir` when the source direction differs), and so do the
  outline entries, which are the source page's headings, in the outline rail
  and the mobile contents sheet. The notice itself carries the page locale's
  `lang` and `dir`, and the interface around the article stays in the page
  locale. Navigation, breadcrumb and previous/next titles of held-back pages
  are not marked yet.

- **The hero effect's pause button no longer covers the hero media.** It was
  placed absolutely in the hero's bottom corner, so wherever the media reached
  that corner the button sat on top of it: on a 375 px phone the 36 x 36
  button covered the corner of the home page screenshot, and at 1440 px it
  still overlapped the image edge. The button now takes its own row under the
  hero content, at the inline end (left in RTL), with the same
  `--sf-space-1` inset; the hero grows by that row (48 px on a phone). The row
  is kept while the button waits for the effect to start, so nothing shifts
  when it appears, and is dropped with reduced motion, where the button never
  shows.
- **Declarative view utilities name real Framework classes.** The allow-list
  for View Tree and `docara.shell` utilities
  (`resources/framework/view-utilities.json`) listed `border-bottom` and
  `surface-0`, which the bundled SIMAI Framework does not define, so a
  section or element using them silently got no border or background. The
  list now has `border-bottom-1`, `border-bottom-solid` and `bg-surface-0`.
  The old names stay accepted: they render as `border-bottom-1
  border-bottom-solid` and `bg-surface-0` (in place, without duplicates), and,
  as with a deprecated Smart alias, each use is recorded in the page plan
  (`deprecated_utilities` in the section provenance or the element block
  data). Unknown names are still refused with
  `DECLARATIVE_VIEW_UTILITY_NOT_ALLOWED`. The documentation examples use the
  Framework names, and a test keeps the allow-list and the documentation's
  layout files to classes the bundled Framework defines.
- **Page authoring validation of Composition Recipe pages.** A Recipe page
  keeps its Markdown body empty, and the build compiles a placeholder for it;
  page validation compiled the empty body itself, so every build of a site
  with such a page printed `Page authoring contract warning:
  [DOCUMENT_IR_INPUT_INVALID] Cannot compile [...]` (the documentation site
  since 3.0.0, for `examples/composition-recipe-compact.md`), and
  validation stopped there, leaving the pages after it unchecked. Validation
  now compiles the same placeholder as the build. With every page checked
  again, two more findings surfaced and are fixed: a link written as inline
  code (`` `![Scheme](/ru/guide/scheme.png)` ``) was checked as a page link,
  and the build counted the advisory editorial checklist
  (`PAGE_EDITORIAL_REVIEW_REQUIRED`, attached to every page with a profile)
  as an item to review. Code spans are now skipped like fenced code, and the
  build reports only items that need a fix (missing structure, links or
  assets); `docara validate page` still shows the checklist.
- `translations status --kind=page|lang` now filters the report by kind. The
  option was accepted but ignored, so the report always listed both pages and
  `lang.json` keys. Without `--kind`, `status` still lists both kinds;
  `accept` keeps `page` as its default. An unknown kind fails with
  `TRANSLATION_KIND_INVALID`.
- The hero effect's pause button labels (`effects.pause`, `effects.play`) can
  be overridden in `content/<locale>/lang.json`, as 3.6.0 announced. The
  `lang.json` schema and namespace allowlist had no `effects` group, so such
  a file failed validation. A test now checks that every built-in interface
  string can be overridden.

### Documentation

- The documentation site no longer contradicts the code. `reference/cli.md`
  shows that `qa --dry-run` needs `--page`, how `translations status --kind`
  filters, and what `init --update` prints; `build/update.md` uses a 3.x
  `upgrade --to` example and says that `update` without an action runs
  `--verify`. Corrected: the `ui_radius` values (`default`, `medium`,
  `large`), the badge's default `size` (`1/3`), how a diagram draws (the
  bundled Mermaid on pages that have one), the embed address rules per
  provider, the `lang.json` groups (`examples` and `effects` are allowed) and
  an example key that Docara reads, the search error
  `SEARCH_COMPONENT_CALL_INVALID`, receipt paths under
  `build_<environment>.docara/`, the Framework pair shipped since 3.6.0, the
  portal page (Docara's own docs have had no portal artifact since 3.5.0),
  the front matter keys (`noindex`, `profile`, `composition_recipe`), a
  Framework example limited to components in the site's lock and a landing
  page that promised descriptions which do not exist. Navigation: Settings
  and Design get an order and sit before Troubleshooting, the example stand
  pages leave the sidebar but keep their addresses, and the landing and
  project-demo pages are left out of the sitemap, `llms.txt` and search. The
  quick start numbers its steps, builds once and moves the template choice,
  environment, `capabilities`/`doctor` and rollback notes to the end; the
  home page's "three commands" are three.

## [3.6.0] - 2026-10-21

### Added

- **Pause for hero background effects.** While a hero effect runs, an icon
  button in the hero's bottom corner pauses and resumes it (WCAG 2.2.2). Its
  label is localized (`effects.pause`, `effects.play`; built-in English and
  Russian, overridable in `lang.json`), and the choice is kept for the site in
  `localStorage` (`docara.effects.paused`) when storage is available. With
  reduced motion the Framework draws one still frame and the button is
  hidden. Docara places the hero's effect itself through
  `SF.Effects.placeEffect` with the same `data-sf-effect*` attributes, before
  the Framework's declarative scan, because only that call returns the handle
  whose `pause()` and `resume()` the button uses.
- **Hero `padding=2xl`.** A taller first screen: the vertical padding grows
  with the viewport, from 48 px (`--sf-d2`) up to a 400 px viewport to 112 px
  (`--sf-e4`) from about 1470 px, with no breakpoint
  (`clamp(var(--sf-d2), var(--sf-c2) + 6vw, var(--sf-e4))` in the shell
  stylesheet, since the Framework has no fluid spacing utility). It works with
  `media=background` and with a background `effect`, whose pause button stays
  in the hero's bottom corner clear of the text. `lg` and `xl` are unchanged.
- **"Made with Docara" line under the sidebar.** Every page with a left
  navigation ends the sidebar with one line of small secondary text,
  «Сделано на Docara» on Russian pages and "Made with Docara" elsewhere, where
  "Docara" links to <https://docara.simai.io/> in the same tab. It is pinned
  to the bottom of the sticky sidebar under a hairline `outline-variant`
  border and stays visible while the menu scrolls above it, however long the
  menu is; the mobile navigation sheet pins the same footer under its
  scrolling content. Pages without a sidebar, such as landing pages, do not
  get it. `"branding": {"credit": false}` in `docara.json` turns it off for
  the whole site, footer and border included; `section.json` and
  `*.page.json` cannot set it (`BRANDING_CREDIT_SITE_ONLY`).
- **Page-weight check.** `scripts/verify-page-weight.php <build-dir>` adds up,
  per page and de-duplicated, the HTML and the stylesheets, scripts,
  preloads, images and CSS fonts and backgrounds the page makes the browser
  fetch. An `@font-face` counts only when a rule whose classes occur on the
  page names its family, directly or through a document-wide custom property,
  and, with `unicode-range`, the page has a character in range; of a family's
  weights only the one nearest 400 counts. It fails a page over
  `max_page_bytes` (default 3 MB), an image over `max_image_bytes` (default
  1 MB), a page with only shell-subset icons that fetches a full Material
  Symbols font, and an `<img>` whose bitmap is more than 2.5× its
  `width`/`height`. Budgets come from a JSON file with per-page overrides.
  The Quality workflow runs it on the documentation site with
  `docs/site/page-weight.json` (4.5 MB per page, 1.3 MB per image, and
  explicit overrides for the icon and diagram pages).
- **Documentation site.** The home page hero draws the `constellation-net`
  background effect, from the effects catalogue pinned to simai/ui-effects
  `ad508268dbf322ecaaf2820a06da8dc8285d4ec1`. The site sets
  `branding.share_image` to a 1200×630 WebP card (48 KB, the Docara mark and a
  screenshot of the start page), so its pages published with `site_url` carry
  `og:image`, `twitter:image` and `twitter:card: summary_large_image`.

### Changed

- **Bundled SIMAI Framework `ui-32c7a64e4575`** (simai/ui `32c7a64e`, npm
  `@simai-studio/ui` 5.20261019.2041; registry bound in `efccc737`) in place
  of `ui-25beeb814ae8`. It ships `SF.Effects`, so hero background effects now
  draw with the bundled Framework. Sites whose lock names `ui-25beeb814ae8`
  (Docara 3.1.0–3.5.0) build with the new release without editing the lock.
  Other changes a Docara site sees: effect areas take their theme's text
  colour, dimmed text on container fills reads in the dark theme, the Button
  Group gains a segmented appearance, the Inter fallback has a bold face, and
  the Composition Recipe runtime implements specification edition 1.1.0
  (already accepted since 3.3.1), so plan hashes that cover the contract
  digest change. The Core packet stays at 1385 files and Smart at 75; the
  Recipe runtime has 23 files.
- A hero with a background effect takes `--sf-surface-0` as its background
  and `--sf-on-surface` as its text colour in the theme the effect draws, so
  its text stays readable while the effect loads or when it draws
  translucently.
- **Icon catalogue instead of the full icon fonts.** A page with a Rounded or
  Sharp icon, a light, medium or filled icon, or an outlined name outside the
  shell subset no longer downloads a 3.5–5.3 MB variable Material Symbols
  font. Docara now bundles a catalogue under
  `_docara/vendor/docara/icon-catalog/<revision>/`: 18 static instances, one
  per family (outlined, rounded, sharp), weight (300, 400, 500) and fill (0,
  1), each holding all 3,926 icon names at about 0.26–0.45 MB. They are
  declared as faces of one CSS family per family and fill, told apart by
  `font-weight`, so the browser fetches only the instance an icon's computed
  weight matches. The documentation's icon page drops from 13.07 MB of icon
  fonts to 1.60 MB; pages that only use the shell subset load exactly the same
  fonts as before.
  - The icon runtime picks a face from the icon's computed axes. Weights 300,
    400 and 500 with fill 0 or 1, grade 0 and optical size 24 use the
    catalogue; any other axis value (`thin`, `bold`, a custom grade or
    optical size) still loads the family's full variable font, as before.
  - The shell subset draws every name written only with the characters of
    its own names (3,468 of 3,926), not just the 78 names it lists, so only
    the remaining names (with `q`, `z` or a digit) leave the subset for the
    catalogue.
  - An example frame receives only the catalogue faces its icons ask for, and
    a full variable font only for an icon whose axes no instance draws.
  - Rounded and Sharp catalogue files ship only with a site that uses that
    family, like the full Rounded and Sharp fonts.
  - `scripts/build-icon-catalog.cjs` regenerates the catalogue with
    ui-builder's icon subset generator (the one that made the shell subset),
    checks every outline of every instance against the source font to within
    one font unit, proves the subset's character rule by shaping every name,
    and refuses to overwrite changed output.
- A page description derived from the Markdown no longer stops at a lead-in
  that ends with a colon. Docara keeps the sentences before the lead-in
  sentence or, for a one-sentence lead-in, takes the next paragraph of the
  introduction; front matter and page `description` still take precedence.
- The outline marks a heading as current once its top passes 35% of the
  window height (and never above the heading's scroll margin), instead of a
  fixed 96px. A heading reached through an outline link stays current while
  it rests near the top, until the reader scrolls by hand.
- Headings inside inline example previews move one level down (`h6` stays
  `h6`) and keep their look through Framework heading and text size classes,
  so an inline sample no longer adds a document `<h1>` or joins the page
  outline. The Source tab shows the markup as written; sandboxed examples are
  unchanged.
- Reading mode sets the content column, code blocks and tables included, on
  the prose measure and centres it; the reader wide format still uses the
  full width.
- **Install from Packagist.** Packagist lists simai/docara 3.x, so the README
  and the documentation (home page, quick start, CLI reference, update and
  3.0 upgrade pages) install Docara 3 with `composer require
  simai/docara:^3.0` alone, without a `repositories` entry. Troubleshooting
  now points a "found 2.x only" error at a stale Composer cache or an old
  `repositories` entry.
- **Documentation site home page.** The home page, which is also Docara's
  product page, now says who Docara is for and what sets it apart: a hero
  with a real screenshot of a Docara 3 documentation page, a three-step first
  site, three reasons to choose Docara, two showcases (the documentation
  interface and this landing on desktop and mobile), links to example pages,
  a FAQ in `details` blocks (including how Docara differs from VitePress and
  Docusaurus, and the PHP, Composer and Node.js 20+ requirements) and one
  closing call to action. The page uses only typed Docara components.
  - Before the closing call to action, a *Сделано на Docara* block shows two
    real sites built with Docara, each with a short caption, a link and a
    light-theme WebP screenshot of an inner page: the SIMAI Framework
    documentation (ui.simai.io, about 700 pages) and this documentation
    (docara.simai.io).
  - Its images are real WebP screenshots (about 240 KB for the three main
    ones, plus about 106 KB for the two showcase sites) instead of decorative
    3D renders (about 4 MB). `docara-navigation.png` and `docara-reader.png`,
    used only by the old home page, are removed.
  - The old FAQ answer that a site build needs no Node.js is corrected.
  - The build-receipt note moved from the home page to *Статический
    результат* (build/static-output), and the limits note to *Безопасность и
    ошибки* (reference/security-and-errors), without the outdated line about
    public release readiness.

### Fixed

- `:::tabs` renders as tabs again: labels with padding, the page's text size,
  a divider under the tab list and an underline on the selected tab, in both
  themes. The tab buttons used utilities the bundled SIMAI Framework does not
  define (`p-inline-1`, `p-block-1/3`, a width-less `border-bottom`) and the
  class `sf-tab`, which belongs to the Framework's `nav > ul > li` tab
  component and has no styles for a button; they now use `p-x-2 p-y-1/2`,
  `border-bottom-1 border-bottom-solid` and Docara's own `docara-tab` styles.
- Other renderer output that named non-existent utilities now gets the
  spacing, borders and type it asked for: the `:::details` summary, the
  diagram caption, the source line of an attributed quote, the component and
  Atlas indexes, inline `kbd`, block math, the `muted` and `contrast` surface
  tones, the embed load button, the tree folder toggle and the code blocks'
  screen-reader labels (`sr-only`). A unit test now fails when a renderer
  emits a class the bundled Framework CSS does not define.
- `:::details` shows a disclosure chevron (the Framework accordion's
  `chevron_right`, turned down when open) in both views, and its summary has
  a visible keyboard focus ring. A flex summary had dropped the native marker,
  so `view=lines` items looked like bold lines; that view also used border
  and padding utilities the Framework does not define, so it now draws its
  bottom rule and vertical padding with `border-bottom-1`, `p-y-1` and
  `p-top-1`.
- Inline code, links and emphasis in a `:::features` card stay in the card's
  text instead of each becoming its own row: the card keeps its icon and
  leading bold title on their own lines and sets the rest as one text run.
- An outlined icon with `weight=light`, `weight=medium` or `filled=true`
  whose name is in the shell subset was drawn regular and unfilled, because
  the subset is a static regular, unfilled instance. It now gets the matching
  catalogue instance.
- The sticky sidebar and the outline column start under the header and end at
  the bottom of the window. They assumed a 56 px (sidebar) or 72 px (outline)
  header while the header is 73 px, so at the top of the page the last 17 px
  of the sidebar, including its last menu item, sat below the window. The
  shell now measures the header into `--docara-header-block-size` (on load
  and whenever the header resizes) and both columns use it; until the script
  runs, the stylesheet's estimate (73 px) applies. The mobile navigation sheet
  lays out its header and content as a column, so the content no longer
  assumes a 56 px header and its end stays inside the window.
- In the documentation navigation, a parent row and its first child are
  spaced like sibling rows (1px) instead of 8px apart, on the desktop tree and
  in the mobile sheet.
- Outline and other in-page anchors stop one spacing step below the sticky
  header instead of flush beneath it. The offset follows the tokens the header
  is built from at every breakpoint and drops the header in reading mode.
- The reader settings dialog is named by its panel heading through
  `aria-labelledby` instead of the generic Framework label; the search dialog
  drops that label too.
- `EFFECTS_OUTSIDE_CATALOGUE` no longer says that SF.Effects runs only
  catalogue effects: since Framework `ui-a9636b60d3e3` a foreign effect runs
  in a sandboxed frame (`SF_BOOT_CONFIG.effects.foreign`: `sandbox` by
  default, or `refuse`). Docara still builds only effects under
  `effects.catalogue`; the message and the hero page now say so.
- **Documentation site.**
  - The documentation no longer says that building a site needs no Node.js
    (`development`, the product Smart demonstrator result and the agent
    journey), and the build requirements on the start, hosting, build,
    upgrade and development pages name Node.js 20+.
  - 34 pages whose description was taken from a long first paragraph
    (171-386 characters) have a concise front matter `description` of at
    most 160 characters.
  - The demonstrator result pages are marked `noindex`, so they leave
    `sitemap.xml` and `llms.txt`, and the configuration page has a proper
    description.
  - The home page screenshots `home-docs-page.webp` (hero) and
    `home-landing.webp` (desktop and mobile showcase) no longer show the old
    start-page install that added a `repositories` VCS entry before
    `composer require`. They are re-taken from the live site, which installs
    from Packagist, at the same 1600×900 size and file names (84 KB and
    100 KB, down from 89 KB and 104 KB).

## [3.5.0] - 2026-10-19

### Added

- **Hero background effects.** `:::hero` takes `effect` (a catalogue id such
  as `aurora-curtain`, or the https address of an `effect.json` in the
  catalogue), `effect_theme` (`auto`, `dark` or `light`) and
  `effect_settings` (a JSON object, written in single quotes). Docara writes
  them, escaped, as the SIMAI Framework declarative mount `data-sf-effect`,
  `data-sf-effect-theme` and `data-sf-effect-settings` on the hero section;
  the Framework Loader (SF.Effects) mounts the effect and gives the hero the
  theme it draws, so the hero text follows that theme. An effect and
  `media=background` are refused together.
- **Effects catalogue.** `docara.json` takes `effects` with `catalogue` (an
  https base pinned to a commit), and optional `index` and `index_sha256`.
  The build writes them to `SF_BOOT_CONFIG.effects` as
  `{catalogue, index, indexSha256}`. A page with a hero effect needs the
  catalogue (`EFFECTS_CATALOGUE_REQUIRED`), and an effect named by address
  must lie under it (`EFFECTS_OUTSIDE_CATALOGUE`).
- Directive attributes may hold braces inside a quoted value, such as
  `{effect_settings='{"speed": 80}'}`. Every directive line that parsed
  before parses to the same attributes.

### Changed

- The **Publish verified release** workflow no longer builds or attaches the
  documentation site's portal artifact. The documentation site is published
  on GitHub Pages only. The `portal` section and `docara portal-artifact` stay
  available to projects.

### Requires

- Effects draw only with a SIMAI Framework pair that ships SF.Effects:
  `ui-08c07b3e938a` or later. The bundled Framework stays at
  `ui-25beeb814ae8`, which does not: with it the attributes stay in the
  markup and the hero renders without an effect. A site opts into a later
  pair through its Framework lock.

## [3.4.0] - 2026-10-19

### Added

- **Portal builds.** A `portal` section in `docara.json` builds a site for
  one path of a larger portal, such as `/ru/docara/docs/` on
  `https://simai.io/`. Its keys are `product` (the portal product id),
  `home_url` (the portal home, absolute https) and an optional `home_label`
  (default: the host of `home_url`). A portal build needs `site_url`, exactly
  one locale and `locale_routing.strategy: default_unprefixed`, because the
  portal path carries the locale. It then:
  - gives every page an absolute canonical address under `site_url`;
  - starts the header with a link to `home_url`, and marks the header and
    footer `data-portal-shell="header"` / `"footer"`;
  - writes `llms.txt` (without a sitemap link) but no `sitemap.xml` or
    `robots.txt`, and stops with `PORTAL_FILE_COLLISION` when the content
    would publish `CNAME`, `.nojekyll`, `robots.txt`, `sitemap.xml` or
    `404.html`;
  - resolves content links written for a prefixed site (`/ru/start/`) to the
    unprefixed pages, so the same content builds for the portal.

  `verify-static` checks `llms.txt` and the absence of those files for a
  portal build. A site without `portal` builds exactly as before.
- **`docara portal-artifact`.** Packs a portal build into the three release
  files a portal assembles: `<product>-<version>-portal.tar.gz` (with
  `index.html` at the top), its `.sha256` and a `simai.io.portal-artifact`
  version 1 receipt (product, release, repository, commit, locale, base path,
  archive, sha256, file count, Framework pair and `docara_version`). The
  archive is reproducible: sorted entries, mtime 0, owner root, fixed modes
  and a fixed gzip header. Before it writes, the command checks that the build
  holds no links and none of the portal's files, and that every page has the
  portal locale in `<html lang>`, a title, a description, a canonical address
  under `site_url`, the link back to the portal home and one Framework pair.
- The documentation site is published for the portal as well: the **Publish
  verified release** workflow builds `docs/site` for `/ru/docara/docs/` and
  attaches `docara-docs-<version>-portal.tar.gz`, its `.sha256` and its
  receipt to the release. An asset that already exists is never replaced.
  The Pages site at https://simai.github.io/docara/ is unchanged.

### Unchanged

- Builds without `portal` produce the same files as 3.3.1; only the schema
  reference of the documentation site lists the new key.
- The bundled Framework stays at `ui-25beeb814ae8`.

## [3.3.1] - 2026-10-19

### Fixed

- **Framework releases with Recipe 1.1.0 build.** 3.3.0 accepted only the
  Composition Recipe specification edition 1.0.1, so a site built against a
  SIMAI Framework release that declares edition 1.1.0, such as
  `ui-7493766fe39e`, stopped with
  `docara_composition_recipe_specification_edition_mismatch`. Recipe 1.1.0
  lets the five recipe formats and the canonical-json profile be named without
  a version; recipes in the older form resolve to the same Documents and
  digests. Docara now accepts the editions it is proven compatible with, 1.0.1
  and 1.1.0, each only with its exact contract digest, which is still
  recomputed from the distribution's normative files. Any other edition, or a
  known edition with another digest, is refused as before. The runtime lock
  `resources/contracts/composition/runtime-lock.json` lists them under
  `contract.editions` (schema `docara.composition_runtime_lock.v3`).
- The execution contract of every resolved page records the digest of the
  edition the distribution implements. With the bundled Framework (edition
  1.0.1) this is the digest 3.3.0 recorded, and the output is unchanged; built
  against a 1.1.0 release, a site differs only in that digest and the plan
  hashes that cover it. A distribution replaced by another edition during a
  build is refused.
- The bundled Framework stays at `ui-25beeb814ae8`.

## [3.3.0] - 2026-10-18

### Added

- **Site URL and discovery files.** `docara.json` takes `site_url`, the
  site's absolute public address (origin plus `base_url`; the two must
  agree). With it the build writes, under the deployment base:
  `sitemap.xml` with every published indexable page as an absolute URL and
  `xhtml:link hreflang` alternates for translated pages; `robots.txt` that
  allows all crawlers and names the sitemap; and `llms.txt` after the
  llmstxt.org convention: the site title and description
  (`discovery.description`, else the home page's), the default locale's
  top-level sections, the other languages' home pages and the machine-readable
  files (`discovery.llms_links`, such as `ai.json`, the sitemap and
  `_docara/page-metadata.json`). Drafts, pages with the new front matter key
  `noindex: true`, redirects, error pages (a route ending in a 4xx or 5xx
  code) and held-back translations are left out. `verify-static` checks the
  three files: well-formed, every URL under `site_url`, every sitemap URL a
  built indexable page and every such page listed, every `llms.txt` link a
  file of the build. Content that already publishes one of these files at the
  root stops the build (`DISCOVERY_FILE_COLLISION`).
- **Absolute canonical, hreflang and share tags** with `site_url`: pages name
  absolute `canonical` and `hreflang` addresses and carry Open Graph and
  Twitter card tags (`og:title`, `og:description`, `og:url`, `og:type`,
  `og:locale` with alternates, `og:site_name`, `twitter:card`). A new
  `branding.share_image` (PNG, JPEG or WebP, inheritable per section and
  page) adds `og:image` and `twitter:image`.
- **`noindex: true` front matter** publishes a page with
  `<meta name="robots" content="noindex">` and keeps it out of the sitemap.
- **Strict translation mode.** `translation_tracking.mode: "publish-current"`
  publishes a target-locale page only while its translation is `current` (or
  `excluded`). A `stale`, `unverified` or `structure_mismatch` page shows the
  source page at the target URL, in the target locale's shell, under a notice
  in the target language that links to the original (wording from
  `language.fallback_notice` in `lang.json`); such a page is `noindex`, has
  its canonical on the original, and stays out of `hreflang`, site search and
  the sitemap. An `orphan` target page is not published; a `duplicate_key`
  stops the build; missing pages keep following `locales.missing_page_policy`.
  Stale or unverified `lang.json` strings show the source locale's string.
  The build lists what it held back and writes
  `build_<environment>.docara/translation-publication.json`. Report mode is
  unchanged.
- **Accepting a reviewed set of translations.** `docara translations accept
  --dry-run` takes a repeated `--key`, a key `--prefix` (a section such as
  `guide/`) or a `--status` filter and writes one hash-bound plan with the
  accepted `unverified`/`stale` items in `accepted` and the rest of the
  selection in `skipped`; `--apply=<plan-sha256>` applies it as before.

### Fixed

- **A page titled like the site is not named twice:** the home page's
  `<title>` was "Docara — Docara"; it is now "Docara". This changes the output
  of such pages on every site, with or without `site_url`.
- **Framework core chunks are fetched once.** Core's own preload of
  `core-rules.js`, `core-system.js` and `core-loader.js` repeats the query of
  `core.js`, while the chunk imports carry none, so with `?sf_v=` on `core.js`
  each chunk was downloaded twice (about 106 KB transferred per page on the ui-doc site).
  `core.js` is now linked by its commit-addressed path without a query. This
  changes one script URL on every page.
- **Breakpoint utilities cascade in breakpoint order.** The Framework rule
  registry lists a utility's breakpoint variants by name, so the first-frame
  stylesheets carried `lg:` rules before `md:` rules and `md:grid-col-2` beat
  `lg:grid-col-4` at every width from `md` up: a four-item `:::features`
  block (`grid-col-1 md:grid-col-2 lg:grid-col-4`) showed two columns at
  1440 px. The planner now orders a utility's variants `default`, `sm`, `md`,
  `lg`, `xl`, `xxl` and leaves every other module in place. This changes the
  stylesheets of pages that use breakpoint utilities.
- **A full-width band's text starts at the page's content edge.** In
  `:::surface {content_width=container}` (and the semantic blocks framed the
  same way) `padding` replaced the container's inline padding, so at 1440 px
  the band's text started at 32 px while the page's text started at 48 px;
  and under 800 px the landing shell padded every container 16 px on all
  sides, against the page's 12 px. Inside a container `padding` now sets the
  top and bottom only (`p-top-N p-bottom-N`), and the narrow-screen rule sets
  `padding-block` only. On a landing page the band's container also keeps the
  container's stepped maximum width instead of `max-inline-size: 100%`, so
  between 640 and 960 px it is as wide and as centred as the page's
  container. Measured on a landing page, the band's text now starts where the
  page's text starts at 390, 700, 800, 900, 961, 1024 and 1440 px. With
  `content_width=full` `padding` still applies on all sides.
- **Example frames load lazily.** Browsers load a `srcdoc` frame with the page
  even when it is marked `loading="lazy"`, so inline-example and HTML-block
  frames now carry `loading="lazy"` and keep their document in
  `data-docara-srcdoc`; the shell moves it to `srcdoc` when the frame comes
  within 600 px of the screen (a frame in a hidden tab waits until the tab is
  shown; without IntersectionObserver every frame loads at once). The frame's
  own message still sets its height once it loads. Asset planning and vendor
  selection read either attribute.

## [3.2.1] - 2026-10-18

### Fixed

- **Media under a sub-path load.** 3.2.0 put `base_url` in front of links and
  preview frames that authors write from the site root, but not in front of
  media: on a site built for, say, `/ui-doc/`, a Markdown image
  `![…](/ru/guide/x.png)` was published as `/ru/guide/x.png`, which the
  browser fetched outside the site and `verify-static` reported broken. The
  build now rebases root-relative media the same way: `img` `src` and every
  `srcset` candidate, `source` `src` and `srcset` (inside `picture`, `video`
  and `audio`), `video` `src` and `poster`, `audio` and `track` `src`, and the
  `href` of `link` icons and manifests. External, protocol-relative,
  `data:` and relative addresses, and addresses already inside the base, are
  left as written, and a site built for `/` is byte-identical. Docara does not
  rewrite CSS `url()` in page or inline styles. `verify-static` now also
  checks `poster` and each `srcset` candidate.

## [3.2.0] - 2026-10-18

### Added

- The Docara documentation site is published on GitHub Pages at
  <https://simai.github.io/docara/> by a new `Documentation site` workflow
  (`.github/workflows/pages.yml`): on every push to `main`, and on demand, it
  builds `docs/site` the way the Quality workflow does, with `base_url` taken
  from the Pages configuration, runs `verify-static` and deploys the result.
  README links the published pages and keeps the Markdown sources as a
  fallback.

### Changed

- **Smaller built sites.** The build publishes the optional parts of
  `_docara/vendor/` only when the finished pages use them: mermaid
  (3.4 MB) for a page with a diagram, KaTeX with its fonts (0.6 MB) for a page
  with a formula, the Rounded (5.3 MB) and Sharp (3.5 MB) Material Symbols
  faces for an icon whose markup, example script or site stylesheet asks for
  that family, and the Framework's all-in-one `utility.full.css` (8.7 MB),
  which the exact production plan never links, for a page that links it.
  A library's licence travels with it. The icon subset and the full Outlined
  face, the fallback for icons Smart components choose in the browser, are
  always published. A minimal three-page site goes from 49.4 MB to 28.7 MB,
  the Russian showcase from 53.9 MB to 42.2 MB, the Docara documentation site
  from 73.3 MB to 65.0 MB. A one-page rebuild removes a group the site no
  longer uses. `verify-static` recomputes the choice from the pages and
  reports `@vendor-asset-selection` when a needed file is missing or differs
  from the package.

### Fixed

- **Sites under a sub-path link correctly.** With `base_url` set to, say,
  `/docara/`, links that authors write from the site root (`[Start](/start/)`
  in Markdown, `href` in `header_navigation`, the frame of an internal page
  preview) were published without the prefix and pointed outside the site.
  The build now puts the base in front of every such link that is not
  already inside it. A site built for `/` keeps its links; the one change
  there is that an internal preview frame whose address is a legacy
  unprefixed route now follows the locale route mapping, as links already
  did.

## [3.1.0] - 2026-10-18

### Added

- **Site colours from one colour.** `docara.json` takes
  `"theme": {"color": "#0a7d4f"}` (owner, 2026-10-10). At build time Docara runs
  the bundled Framework's own theme palette (`SF.ThemePalette`, Google HCT) on
  Node.js (the runtime that already resolves Composition Recipes) and
  publishes `themeToCss`'s rule as one content-addressed stylesheet,
  `_docara/docara-theme.<sha256>.css`, linked by every page after its
  first-frame stylesheets. The 128 `--sf-<palette>-<tone>` primitives and
  their 98 translucent steps (`--sf-<palette>-<tone>--alfa-<n>`) change, so
  every semantic token follows in light and dark, container fills, tonal
  buttons and hover or selected backgrounds included, and example frames take
  them with the page's other tokens. Optional `hue` (0-360) and `chroma`
  (0-150) override how the colour is read, as in `sf-theme-generator`.
  A colour that is not `#rgb` or `#rrggbb` stops the build with
  `THEME_COLOR_INVALID`; hue and chroma give `THEME_HUE_INVALID` and
  `THEME_CHROMA_INVALID`; a lock whose release has no palette gives
  `THEME_FRAMEWORK_UNSUPPORTED`. Without the setting the published site is
  byte-identical to before.
- The build receipt (`resolved-page-plans.json`, `build.theme`) records the
  input, the hue and chroma used, the Framework release and the SHA-256 of the
  palette code it ran, and the stylesheet. `verify-static` checks the
  stylesheet's bytes, that it holds only the tones and their translucent
  steps (each step its tone at its opacity), and that every
  page links it once after its shell stylesheets (or that none does). A
  one-page rebuild after the colour changed stops with
  `PORTABLE_INCREMENTAL_THEME_CHANGED`; `docara preview` rebuilds its cache
  whole instead.

### Changed

- **Framework release `ui-25beeb814ae8`.** Docara bundles the SIMAI Framework
  release `ui-25beeb814ae8` (`simai/ui` 25beeb81, ui-source 94b497f5b,
  contract registry bound in 719ceb39) in place of `ui-82fc0ba882ba`. It is
  the first consumable release with the theme palette's translucent steps
  (`ui-45384c434d3f` introduced the palette; `ui-1cb0739a8423` added the steps
  but its Recipe runtime imported a module its distribution lacked). Bundled:
  the Core runtime packet, the Smart
  entrypoints (75 files, 54 components, `sf-theme-generator` among them), the
  Composition Recipe runtime, the generated contracts and the starter and
  documentation-site locks. A project lock that pins `ui-82fc0ba882ba` (Docara
  3.0.0 to 3.0.5) builds with the bundled release without editing it.
- `scripts/sync-framework-composition-runtime.php` follows relative imports
  to core modules beside the composition directory (`../schema-name.mjs`);
  ui-25beeb814ae8 ships the module inside the directory.
- The first-frame planner follows the Loader on lazy relations
  (`"lazy": true`, new in this release): a lazy relation is fetched after the
  page is ready, so it is not part of the first-frame closure and does not
  keep the module that names it dynamic (`sf-tabs` and `sf-context-menu`).

## [3.0.5] - 2026-10-16

### Changed

- **`init` in a fresh Git repository.** A directory that holds only `.git`,
  `.gitignore`, `.gitattributes` or file-manager files (`.DS_Store`,
  `Thumbs.db`, `desktop.ini`) counts as empty, so `git init` before
  `docara init` works. The starter never writes those names, and other files
  still stop `init` with no change.
- **`init` prints the commands to run next** the way the project runs Docara:
  `php vendor/bin/docara ...` for a Composer install, with `cd <path>` when the
  site was created in another directory.
- **A build without Node.js says how to fix it:** install Node.js 20 or newer,
  or set `DOCARA_NODE_BINARY`. The error code is unchanged
  (`COMPOSITION_RECIPE_RUNTIME_REQUIRED`).
- The documentation site keeps the project demo page (`/ru/project-demos/`)
  out of the menu, like the demonstrator results; the extensions example still
  links to it. Its text no longer uses internal terms.

### Documentation

- README and the quick start say what to commit, with a `.gitignore` to copy,
  and list `contracts/generated/` in the project layout.
- Troubleshooting covers Composer finding only 2.x, a missing PHP extension,
  `init` refusing a directory, and Node.js not found.

### Development

- `composer.json` `archive.exclude` also leaves out `docs/site/.gitignore`, as
  `.gitattributes` does; a test now fails when the two lists name different
  tracked paths.
- `actions/setup-node` is pinned to a commit, like the other CI actions.
- Tests and one fixture named after internal project phases (`Goal3*`,
  `GoalC*`, `FrameworkPortableWave`, `Sf5CrossHost`, `m5-multilingual-site`)
  are renamed after what they check.
- The surface runtime test removes the build receipts it wrote into
  `docs/site` (about 40 MB per run).

## [3.0.4] - 2026-10-16

### Fixed

- **Smart components that name each other build.** A component's `requires`
  lists the Smart elements it may render, which the Loader resolves lazily, so
  two components may name each other: from Framework release ui-7cbf3a19a7a7
  a context menu renders tabs in its panel head and tabs render a context menu
  for their overflow. The runtime closure stopped the build with
  `FRAMEWORK_RUNTIME_DEPENDENCY_CYCLE`; it now reaches every tag of the cycle
  once, in a stable order, and projects the same files.

## [3.0.3] - 2026-10-15

### Changed

- Install from this repository. The README and the documentation site add
  the repository to the project before `composer require simai/docara:^3.0`
  (a one-line `composer.json` for a new project, `composer config
  repositories.docara vcs https://github.com/simai/docara.git` for an
  existing one): the Packagist entry for simai/docara lists only 2.x until
  its maintainership moves, so a bare `composer require` would install 2.x.

## [3.0.2] - 2026-10-14

Fewer stylesheet requests. A page links a few shared first-frame stylesheets
instead of one per Framework source. Pages look and behave the same.

### Changed

- **Grouped first-frame stylesheets.** 3.0.1 published each source
  stylesheet of a page's first-frame closure as its own
  `docara-shell.<sha256>.css`, so a page linked about fifty stylesheets. Once
  every page is built, Docara now groups the sources of all pages into shared,
  content-addressed stylesheets: the common core of the site is one file,
  frequent combinations get their own files, and a page links only stylesheets
  whose sources it needs, nothing more. On the SIMAI Framework documentation
  (922 pages) a page links 3–18 stylesheets, 4.5 on average, instead of 48–64;
  on Docara's own documentation 5–11 instead of 46–62. The published
  stylesheets stay within twice the bytes of the distinct sources.
- **The cascade is unchanged.** A stylesheet is the exact bytes of its sources
  one after the other. Two sources change places only where their order cannot
  decide any declaration: they declare no common property (shorthands,
  logical/physical twins and vendor aliases count as one) in a common cascade
  layer and share no name-keyed rule such as `@keyframes`, `@property` or
  `@font-face`. The layer order comes from the Framework `core.css`, which
  every page links first. A source the analysis does not understand stays in
  place.
- **Receipts.** The Framework shell receipt is now
  `docara.framework_asset_plans.v2`: each page's plan still lists its sources
  in cascade order, and the new `stylesheets` section
  (`docara.shell_stylesheets.v1`) lists every published stylesheet with its
  sources and each page's stylesheets in link order. `verify-static` checks
  that each stylesheet is exactly its sources, that a page links exactly its
  stylesheets in that order, that their sources are the page's closure with
  every order-dependent pair kept, and that no other shell stylesheet is
  published.
- **`build --page`** keeps the other pages' stylesheets: it reuses the
  published stylesheets and adds new ones only for a changed closure. A
  complete build groups the whole site again.

### Upgrade notes

- Run a complete build once after upgrading: `build --page` needs the 3.0.2
  receipt of a complete build.

## [3.0.1] - 2026-10-14

A smaller built site. The published folder no longer carries build receipts,
the first-frame stylesheets are shared between pages, and each page carries
its navigation tree once. Pages look and behave the same.

### Changed

- **Shared first-frame stylesheets.** Docara published one
  `_docara/docara-shell.<sha256>.css` per distinct page closure: every page
  whose set of Framework utilities and components differed got its own copy
  of the same CSS, so a large site carried hundreds of near-identical 1 MB
  files. Each source stylesheet of the closure is now published once, as its
  own content-addressed `docara-shell.<sha256>.css`, and a page links the
  files of its closure in the same order as before. The bytes and their order
  are those of the former single file, so the cascade is unchanged; the files
  are shared and cached across pages. The asset-plan receipt lists one
  generated stylesheet per source, and `verify-static` checks that a page
  links exactly its stylesheets in receipt order.
- **Build receipts are no longer published.** Resolved page plans,
  performance, examples, redirects, locale routes, backlinks, translation and
  documentation status and the other projections describe how a site was
  built; a visitor never needs them. They now live next to the output, in
  `build_<environment>.docara/`, instead of `build_<environment>/.docara/`.
  `build --page`, `verify-static`, `preview` and `inspect` read them there.
  `verify-static` still reads the in-tree `.docara/` of a folder built by
  3.0.0, takes `--receipts=DIR` for a deployed copy, and reports
  `@build-receipts-in-output` when a folder whose receipts live outside also
  carries a `.docara/` directory.
- **The navigation tree is in the page once.** The mobile documentation sheet
  held a second, id-stripped copy of the sidebar navigation in a `<template>`.
  The shell runtime now fills that template from the sidebar before any other
  script runs, so the server-rendered copy is gone; the opened sheet is the
  same as before.

### Upgrade notes

- A project script that reads or writes `build_<environment>/.docara/…`
  (for example the resolved page plans, or a `standalone-examples.json`
  receipt written after the build) must use
  `build_<environment>.docara/…` instead.
- Deployment needs no change: publish `build_<environment>/` as before. Do not
  publish `build_<environment>.docara/`.
- After `docara upgrade` from 3.0.0, run `build production` once: 3.0.0
  promotes the verified folder without the receipts that now live next to it.
  From 3.0.1 on, `upgrade` promotes and rolls back the receipts with the build.

## [3.0.0] - 2026-10-14

Docara 3.0 is the first public release of Docara: a static site generator
that builds documentation sites and landing pages from Markdown and small
JSON settings files, with the interface, components and page composition of
SIMAI Framework. The package is licensed under Apache-2.0.

What you get:

- `docara init` creates a working site, `build` writes a static folder,
  `verify-static` checks it and `serve` shows it over HTTP.
- Navigation, search, page outlines, breadcrumbs, previous/next links,
  reader settings (theme, font size, width), live examples and a component
  catalogue, built from the Markdown files themselves.
- Several languages in one site, each from its own `content/<locale>/`, with
  optional translation freshness reports.
- Settings inherited from `docara.json` through `section.json` to
  `<page>.page.json`, checked against published JSON schemas.
- A developer SDK (`list`, `inspect`, `schema`, `validate`, `scaffold`,
  `preview`, `test`, `qa`, `atlas`, `capabilities`) with `--json` output, and
  an optional MCP adapter in `tools/mcp-docara/`.
- `upgrade` and `update` for moving to newer compatible releases with a
  verified build and rollback.

### Changes for projects built with Docara 2.x

- **Framework bundled, no checkout needed.** Docara bundles the public SIMAI
  Framework release `ui-82fc0ba882ba` (repository `simai/ui`, Core and Smart
  from one commit), including the Composition Recipe runtime. A build needs
  PHP and Node.js, but no Framework checkout and no `DOCARA_SIMAI_UI_ROOT` or
  `SIMAI_UI_ROOT`; these variables are now only an optional way to use
  another distribution.
- **Framework locks.** A project lock that pins the lock Docara 2.18 shipped
  builds with the bundled release. A lock naming an older Smart revision falls
  back to the legacy portable Smart copy; replace it with
  `vendor/simai/docara/stubs/portable/simai-framework.lock.json`.
- **Components removed from the free Framework release:** `sf-admin-menu` and
  `sf-table` (they moved to a commercial SIMAI component package).
- **`init` options.** `--template=minimal|showcase` and `--lang=en|ru`. The
  default is a small English starter (`minimal`, `en`). The starter of 2.x,
  the component catalogue with project Smart demos, is
  `--template=showcase --lang=ru`.
- **Command line.** `docara` with no arguments and `docara list` without a
  kind list the commands; `docara --version` prints the Docara version in a
  checkout too.
- **Build output.** Shared assets are published once under `_docara/` at the
  site root, not again under every locale prefix (for example
  `/ru/_docara/`). The documentation site's output dropped from 220 MB to
  139 MB.
- **Interface language.** Text Docara writes into pages (the schema reference
  table, the Atlas index, the default internal preview title, scaffolded
  example labels) follows the page language: English, or Russian on Russian
  pages. Sites in other languages get English.
- **Component catalogue metadata.** Entries in `_docara/component-catalog.json`
  carry `release` and links under the release tag in place of `revision`,
  `author` and `changed_at`.
- **Composer package.** Requires the PHP extensions it uses (`ctype`, `dom`,
  `intl`, `json`, `libxml`, `mbstring`) and resolves stable dependencies only.

To move a 2.x project: `composer require simai/docara:^3.0 -W`, then
`php vendor/bin/docara update --dry-run` and `--apply`, then build and run
`verify-static`. `docara upgrade` does not cross major versions. The Russian
documentation site has a step-by-step page.
