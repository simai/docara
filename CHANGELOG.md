# Changelog

All notable changes to Docara are documented in this file.

## [Unreleased]

### Added
- Reader preferences offer a font size (Small, Normal, Large) and a page
  width (Normal, Wide). Font size sets the root size to 87.5% or 112.5%;
  Framework type, spacing, controls and container steps are rem-based, so the
  header, navigation, outline, breadcrumbs and content scale together (16px
  body text becomes 14px or 18px). Wide format uses the widest Framework
  container step (`--sf-container-8--size-max`, 112rem) for the header, docs
  layout and footer and lifts the reading measure of prose paragraphs and list
  items. Both are restored before first paint like the theme.
- Theme, font size and page width are the bundled reader controls and are
  shown even when a site lists its own fields: listed fields keep the group
  and order the site gives them, and an unlisted bundled control is appended
  to its group. The new `reader_preferences.hidden_fields` list opts a
  control out; naming a field both there and in a group fails the build.
- Two toggles next to the breadcrumbs: one hides the outline rail, the other
  turns on reading mode, which keeps only the content column with a floating
  exit control and Esc to leave. The state persists per site under
  `docara.reading.<site-hash>.v1`, falls back to memory without storage, and
  is restored before first paint. The outline toggle uses `dock_to_left`.
- The header menu marks the item of the section that contains the current
  page with `aria-current="true"` and the active surface, chosen at build
  time from the navigation ancestry or the longest route prefix; the site
  home is current only on itself.
- Docara ships built-in Russian defaults for its optional interface strings
  beside the English ones: code copy and wrap, the responsive example
  viewer, font size, page width and the reading toolbar. A locale gets the
  defaults of its language (`ru`, `ru-RU`) or English otherwise, and any key
  in the site's `content/<locale>/lang.json` still wins. The required shell
  strings stay site-owned, so a locale without its own `lang.json` still
  fails the build.

### Changed
- Reader preferences no longer offer modal backdrop blur or control corner
  radius. A configuration that still lists `appearance.modal_blur` or
  `appearance.ui_radius` keeps building and hides them, and their stored
  values are pruned. `settings.modal_blur` and `settings.ui_radius` remain
  author settings. Their `reader.modal_blur_*` and `reader.ui_radius_*`
  strings are no longer required in `lang.json`.
- The preferences panel is titled "Settings" and shows each setting as its
  label above a Framework button group used as a segmented control: outline
  segments, the selected one filled primary, each wrapping a native radio so
  the group is a keyboard `radiogroup`. The header sits on `--sf-surface-1`
  and the settings on `--sf-surface-0`. The group heading, intro, field and
  option descriptions and the footer are gone; a `restart_alt` icon button
  in the header resets every preference. The title and reset label come from the new
  optional `reader.panel_title` and `reader.reset_all` strings with built-in
  English and Russian defaults; `reader.title`, `reader.reset`,
  `reader.appearance`, `reader.appearance_description`, `reader.help` and the
  `reader.theme_*_description` strings are no longer required.
- The responsive example viewer's width switcher is a Framework segmented
  button group with icon-only segments (`desktop_windows`, `tablet`,
  `smartphone`), radio semantics and arrow keys; the checked segment uses the
  filled primary variant, which reads in both themes.
- The search status line uses body text and the key hints small body text,
  with key chips at the same size, instead of the smaller label token.
- Docara's own CSS lengths use Framework size tokens; only 1px hairlines,
  media query breakpoints and the .2em underline offset stay literal.
- Reading mode exits through a round tonal Framework icon button
  (`radius-rounded`) pinned to the window corner instead of a boxed copy of
  the toolbar.

### Fixed
- The active outline bar sits on the rail divider. Its offset used `calc()`
  with the unitless `--sf-0` token, which invalidated the expression and left
  the bar 22px inside the divider. It now starts on the divider, is 2px wide
  with square ends over the 1px track, and follows the inline-start side in right-to-left
  pages, where the divider also moves to the inline-start edge.
- A docs page no longer loads the 3,964,532-byte full outlined icon font for
  the shell's own icons. The shell icon subset lacked `wrap_text` and
  `format_text_overflow` (code wrap toggle), `devices` (example viewer),
  `data_object` and `folder_open` (file trees), so every page with a code
  block pulled the full font. The subset is regenerated with ui-builder
  `sf-icons build` from the same pinned source and generator, adding those
  five, `open_in_new` (the open-file action) and the new `dock_to_left` and
  `restart_alt`, and the viewer's `desktop_windows`, `tablet` and
  `smartphone`: 78 icons, 247,824 bytes instead of 67 icons and 244,368
  bytes. An icon whose name is still empty, such as a Framework checkbox
  mark before it is checked, now stays on the subset instead of pulling the
  full font.
- The docs layout and footer use the header row's one-step inline padding, so
  the sidebar starts at the logo and the outline ends at the last header
  action; they were 32px further in on wide screens.
- An icon frame no longer receives the full Material Symbols font up front.
  It gets the icon subset with its first icon, and the full fallback font
  (3,964,532 bytes) only when the Framework icon runtime inside the frame
  meets an icon name the subset lacks, the same moment the page itself would
  start loading that font. The runtime knows it runs in a frame
  (`data-docara-example-frame`) and asks the shell instead of fetching from
  the site. The icon faces declared in the linked stylesheets (418,700 bytes)
  are no longer sent where the subset styles every icon, since the page never
  uses them either. A frame showing only subset icons now receives 311,372
  font bytes instead of 4,694,604.
- The shell stylesheet no longer changes Framework components inside
  examples. It is unlayered and is also sent into sandbox frames, so six of
  its rules reached Framework markup in inline examples and frames. Five of
  them repeated what the pinned Framework (`4f5e5067`) already does and are
  removed: `sf-alert{display:block}`, the success alert icon colour, the
  outline button side borders, and the breadcrumb `overscroll-behavior-inline`
  and `[hidden]` rules. `sf-button{display:block}` is kept for Docara's own
  content only (`body[data-docara-shell] sf-button` outside
  `.docara-example-inline`): before upgrade it turned an inline `sf-button`
  into a block, so a sentence with a button wrapped where a plain Framework
  page keeps one line. The shell's own pages are pixel-identical before and
  after.
- A sandboxed example frame receives icon fonts only when it shows icons.
  It used to receive every Material Symbols font (subset, full, and the
  shell's own face, about 4.6 MB), because the page's icon-ready script made
  every frame count as an icon frame. Icons named in the example's own sources
  mark the frame at build time (`data-docara-example-icons`). A Smart component
  that renders an icon internally is reported by the frame itself once the
  icon appears, and the icon styles follow. On the own site's example page the
  font bytes sent into frames drop from 75.1 MB to 0.13 MB.
- Font bytes and boot state go to a frame once. Later environment updates on
  theme or size changes carry only what the frame does not have yet; the
  frame announces a fresh document (`docara:example-ready`), and the shell
  then sends everything again. Font transfers wait for the page's own font
  loads and read through the HTTP cache (`cache: 'force-cache'`), so on a
  server without caching headers each Inter file is requested from the
  network once instead of twice.
- The frame document has a `<title>`, the example's label, and takes the
  page's `lang` and `dir`. An example without a label is named with the new
  optional UI copy `examples.example` ("Example"; "Пример" on the own site),
  which also names its tab and the iframe's `title`.

## [2.15.0] - 2026-10-05

### Changed
- Bundle Framework pair `ui-4f5e5067afeb-smart-7d932ed51408` (Core `4f5e5067`,
  Smart `7d932ed5`, contracts `6dff03e4`), built from ui-source `2284e9c5`.
  Smart elements keep their place: an element a template declares keeps its
  rendered position, and a re-render writes only what changed. Every Smart
  bundle now carries that shared base, so 45 of the 62 projected Smart files
  have new bytes. The button emphases each get their own look: `--ghost`
  reads as `--link`, and there is a new tonal primary and calm loading
  stripes. Buttons and icon buttons gain readable density classes. The page
  field keeps its number under a host's own reset, the open admin-menu search
  panel stands over the menu and can carry the host's title, and a dropdown
  holding tags is as tall as one holding text. The runtime projection keeps
  1386 files; the Inter faces in `core.css` are unchanged, so the preloads
  resolve to the same files. Smart attributes, `requires` and the derived
  eager set (alert, buttons, close, icon-buttons, icons, modal) are unchanged.
  CI and the consumer-install job check out simai/ui at `4f5e5067` and
  simai/ui-smart at `7d932ed5`.
- The `26434c2b` runtime packet is no longer carried. A site whose lock names
  it stays admitted through `superseded_framework_locks` until it repins.

### Added
- `scripts/verify-consumer-install.php` installs the package into a throwaway
  Composer project from `git archive` of the checkout, with no `vendor/` of
  its own, as a tag's zipball would. It runs both Framework syncs from
  `vendor/simai/docara`, starting from a project lock that still carries the
  old four-file eager Smart list, and then builds and verifies the site. The
  release-readiness workflow runs it in a new `consumer-install` job against
  the ui and ui-smart commits pinned in `resources/framework/runtime-lock.json`.
  It fails on both the 2.14.0 autoload fatal and the 2.13.0
  `FRAMEWORK_ASSET_PROJECTION_CLOSURE_MISMATCH`.

### Fixed
- `scripts/sync-framework-rule-registry.php` accepts a `ui` checkout that is a
  linked Git worktree, whose `.git` is a file, as the Smart sync already did.

## [2.14.1] - 2026-10-04

### Fixed
- `scripts/sync-framework-smart-runtime.php` loads the consumer's Composer
  autoloader when Docara is installed as a dependency. Since 2.14.0 the script
  needs Docara classes and required only `vendor/autoload.php` beside the
  package, which does not exist under `vendor/simai/docara`, so a project's own
  runtime materializer stopped with a PHP fatal. Without any autoloader the
  script now fails with `FRAMEWORK_SYNC_AUTOLOAD_UNAVAILABLE`.

## [2.14.0] - 2026-10-04

### Changed
- Bundle Framework pair `ui-26434c2bab11-smart-f0b1097df368` (Core `26434c2b`,
  Smart `f0b1097d`, contracts `82e326c2`), built from ui-source `4675ff9c`.
  Core ships Inter again: `core.css` declares seven "Inter Variable" faces
  (weights 100 to 900, one per script, `font-display: optional`) and an
  "Inter Fallback" face with metric overrides, and the family tokens lead
  with them. The data view table gains count on request, column moves and
  pinned-column labels, a row tint and a page row that holds the window
  bottom; the pagination renders a short wait; the admin menu searches from a
  panel. An anchored context menu keeps each item on one line at the
  viewport edge and is placed again once the font has arrived, and the page
  number field shows its number instead of clipping it. `sf-admin-menu`
  declares `search-hint`, `search-keys-hint`, `search-label`, `search-mode`
  and `search-shortcut`. `sf-pagination` declares `can-count`,
  `count-known`, `counting`, `counting-label`, `first-label`, `has-next`,
  `page-field-label` and `show-count-label`, and now requires `sf-input` and
  `sf-spinner`. `sf-table` declares `pin-column-label` and
  `unpin-column-label`. CI checks out simai/ui at `26434c2b`.
- The unreleased pair `ui-128601792cf9-smart-e235dc4f7627`, bundled on main
  before this one, is superseded and never reaches a release: its admin menu
  sized the search panel's waiting rows with the undeclared `--sf-f0` and
  spaced a sheet with `--sf-space-1/6`. A lock that names it stays admitted
  through `superseded_framework_locks` until it repins.
- Every page preloads the Inter face for Latin script, and a page whose
  language is written in Cyrillic (`lang="ru"` and other Cyrillic-script
  languages) also preloads the Cyrillic face. The faces are read from the
  pinned runtime `core.css`, never from a list: the family named first in
  `--sf-text--family`, and the `@font-face` whose `unicode-range` covers A-Z
  and a-z, or А-я. Each `href` is exactly the URL the stylesheet's `url()`
  resolves to, so the browser reuses the preload for the face instead of
  fetching it again. Latin-ext, Cyrillic-ext, Greek and Vietnamese faces are
  not preloaded, and a pair whose `core.css` declares no faces gets no
  preload. The preloads are planned assets with digests, so build receipts
  record them. With `font-display: optional`, a first visit now renders in
  Inter instead of the fallback.
- The runtime projection carries the files the foundation stylesheets name
  through `url()`: the seven hashed Inter woff2 files beside `core/css/`. The
  sync reads them from `core.css` rather than from a list, resolves each one
  relative to the stylesheet, and refuses a reference that leaves
  `distr/core/`. The upstream guard covers them. The verbatim copies under
  `core/fonts/inter/` are not projected. The projection grows from 1379 to
  1386 files, and a built site gains 7 files per locale.
- The Smart sync derives the eager Smart projection (`asset_projection.files`)
  instead of taking it from the lock's existing list. It uses the closure the
  admission preflight checks: the shell tags and the tag of each admitted
  component manifest, expanded through `requires` by the function the asset
  planner itself uses (`FrameworkRuntimeClosure`), mapped to each component's
  stylesheet and script. A list already in the lock is ignored, so a lock
  that still names only alert, buttons, icons and modal is rewritten to the
  seven files the closure reaches. For this pair those are alert, buttons,
  close, icon-buttons (stylesheet and script), icons and modal.
- Design tokens for a documentation source without a contract pointer are
  read from the runtime projection's `core.css`.
- The `ed549e72` runtime packet is no longer carried. A site whose lock names
  it stays admitted through `superseded_framework_locks` until it repins.

### Removed
- The bundled typography packet (`vendor/simai-framework/typography/`:
  edition 5.4.0 and its seven Inter fonts, 4,078,074 bytes). The runtime
  projection's `core.css` is now the single source of the foundation and its
  faces. A lock that still pins edition 5.4.0 (or the earlier 5.8.0), the way
  larena-doc once did, is recorded in `superseded_typography_projections` and
  takes the runtime foundation instead; any other pinned packet fails closed
  with `FRAMEWORK_TYPOGRAPHY_ASSET_MISSING`.

## [2.13.0] - 2026-10-04

### Added
- A reusable project example whose content is HTML only now renders inline
  when its markup passes the inline policy, the same rule examples written in
  the page follow, so dropdowns, context menus, datepickers, tooltips and
  modals open over the page instead of inside a frame. An example with
  `index.css` or `index.js` stays sandboxed, and `preview=inline` on it still
  fails with `MARKDOWN_EXAMPLE_INLINE_NOT_ADMITTED`. The `reusable_example`
  preview reason is gone; receipts and `inspect page` report the real reason.
- The inline policy admits `data-*` attributes (not `data-docara-*`, and no
  `javascript:`, `vbscript:` or `data:` value), kebab-case `id` attributes
  with at least one hyphen, `slot` with a plain kebab-case token, and `form`
  without `action`, `method`, `target`, `enctype` or `name`. On a Smart element it admits the attributes the
  project's Framework lock declares for that tag. `on*`, `href`, `src`,
  `srcdoc`, `style`, `action`, `formaction`, the `form` attribute,
  `autofocus` and `contenteditable` stay forbidden even when declared.
- The site builder checks every finished page: an id inside an inline example
  that appears more than once on the page fails the build with
  `MARKDOWN_EXAMPLE_INLINE_ID_DUPLICATE`, naming the id, the example and the
  page.
- The shell cancels any form submission that starts inside an inline
  example, so a demo form never navigates or reloads the documentation page.

### Fixed
- Sandboxed example frames render text in the page's Framework typography
  instead of Arial. The frame runtime no longer forces `--sf-text--family`,
  `--sf-heading--family` and `--sf-display--family` to `Arial,sans-serif`
  (since 2.3.0); the frame takes the page's families with the other tokens.
  Where the page declares web faces (a pinned typography packet's
  `core.css`), the shell already sent that stylesheet into the frame with its
  font files as bytes, so no host CORS is needed. It now sends only the faces
  whose `unicode-range` covers characters in the frame's source: two of the
  seven Inter Variable faces (67,004 bytes) for Latin and Cyrillic text, where
  all seven used to be sent. A same-origin face whose file cannot be sent is
  dropped, so the family falls back to its local faces (`Inter Fallback`).
- Sandboxed examples boot the Framework: the frame now receives Core together
  with the storage fallback, boot configuration and preloaded registry, so
  components bind and state written into the markup is shown.
- An example below the fold is sized before it scrolls into view, so the page
  no longer jumps when the reader reaches it.
- Outward focus rings in the shell, brand, navigation and outline keep the
  shared `--sf-focus--offset` gap.

### Changed
- Each Smart runtime component's `requires` is now derived from the bundled
  Framework contract registry instead of a map kept by hand in the Smart sync
  script. 24 components declare dependencies instead of 3. `sf-table` gains
  `sf-badge` and `sf-spinner`. `sf-admin-menu` drops `sf-modal`, which its
  sources do not use.
- The shell's eager Smart projection grows from four files to seven:
  `sf-close` (`smart/close/js/close.js`) and `sf-icon-button`
  (`smart/icon-buttons/css/icon-buttons.css` and `js/icon-buttons.js`) join
  alert, buttons, icons and modal. With the complete graph, `sf-alert` needs
  `sf-icon-button`, which needs `sf-close`, so the admitted eager closure
  reaches them. They are projected rather than the closure check being
  relaxed. The three files were already published in the dynamic projection,
  so a built site carries no new files. Its pages reference the same assets
  as before; only the cache version changes.
- Bundle Framework pair `ui-ed549e7282ef-smart-6a58ac134bdb` (Core `ed549e72`,
  Smart `6a58ac13`, contracts `c434bbc2`), built from ui-source `0993cfc8`.
  Over `c6f98eb4` the pair brings: the rule registry loads every component a
  component is built from and every element its template renders; ARIA where
  assistive technology reads it and names for unnamed controls; floating
  panels (dropdown, datepicker, tooltip) stay aligned with fields near the
  viewport edge, and the dropdown list gets the context menu's surface and
  shadow; component stylesheets read only tokens the theme declares; the
  data view table gains sorting, row state and interaction, pinned columns,
  a resize guide, a page switcher and a bulk panel over the page. The runtime
  projection keeps its 1379 files, with new bytes, and the Smart projection
  keeps 47 components. `sf-dropdown` now declares `options`, and
  `sf-pagination` declares `action-choose-label`, `actions-region-label` and
  `clear-selection-label`. CI checks out simai/ui at `ed549e72`.
- The `c6f98eb4` runtime packet is no longer carried. A site whose lock names
  it with the 1379-file packet stays admitted through
  `superseded_framework_locks` until it repins.
- The runtime projection sync fails when the packet it rebuilds holds anything
  the pinned revision does not: `FRAMEWORK_RUNTIME_UPSTREAM_MISSING` for a
  manifest entry the revision lacks, `FRAMEWORK_RUNTIME_UPSTREAM_MISMATCH` for
  bytes that differ from it, and `FRAMEWORK_RUNTIME_UNMANIFESTED_FILE` for a
  file on disk with no manifest entry. The packet is seeded from the one it
  replaces, so a component the Framework removed used to survive into the new
  packet with a freshly computed digest. The Smart sync, which writes every
  file from the revision, now reports the leftover files it prunes.
- Inline HTML examples sit inside a `.docara-example-inline` root. Shell prose
  rules (paragraph measure, Smart element margins, heading scroll margins) no
  longer reach into it, so the markup is styled as it is in a frame.
- The Smart sync script derives each runtime component's `attributes` from the
  pinned ui-smart revision's `smart/<name>/smart.manifest.json`: the keys of
  `inputs.properties`, plus `template` and `root-class` for a tag that declares
  any input. A tag without a manifest or without inputs keeps an empty list.
  The bundled runtime lock, the site lock and the stub lock carry the derived
  lists; 46 of 47 components now declare attributes (`sf-inline-editor` has no
  inputs).
- Bundle Framework pair `ui-c6f98eb4cb29-smart-bb8f3cc5c329` (Core `c6f98eb4`,
  Smart `bb8f3cc5`, contracts `1c40964b`), built from ui-source `c18b4b4a`. The
  runtime projection goes from 908 to 884 files: the 27 files of the Font
  Awesome Pro icon component leave, the new `flag` component's stylesheet and
  script arrive, and the third-party notices join. The Smart projection stays
  at 47 runtime components.
- Every projected runtime carries the Framework's third-party notices
  (`distr/core/contracts/third-party-notices.v1.json`: the Framework's own MIT
  licence and the notices for Lit, Floating UI, Material Symbols and
  flag-icons), so a built site publishes them under
  `_docara/vendor/simai-framework/runtime/<revision>/distr/`. Nothing loads the
  file, so the projection names it explicitly.
- The runtime projection carries the flag catalogue, `component/flag/flags/`
  (`index.json` and 494 SVGs, no `.gz`), which `sf-flag` fetches from a URL it
  builds from `sfPath`; without it a built site showed emoji instead of flags.
  The projection grows from 884 to 1379 files; the 884-file packet stays
  admitted through `superseded_runtime_projections`.

### Removed
- Font Awesome Pro 5.15.3 is no longer bundled. The `f0b69761` runtime
  projection packet shipped its commercially licensed fonts and stylesheet as
  `distr/component/icon/`; the Framework has removed that component and Docara
  no longer carries the packet. A site whose lock still names `f0b69761` stays
  admitted through `superseded_framework_locks` until it repins.

### Upgrade notes
- A project that writes its own Framework lock (a custom runtime
  materializer) must list `smart/close/js/close.js`,
  `smart/icon-buttons/css/icon-buttons.css` and
  `smart/icon-buttons/js/icon-buttons.js` in `asset_projection.files` with
  2.13.0, or the build fails with `FRAMEWORK_ASSET_PROJECTION_CLOSURE_MISMATCH`.
  From the next release the Smart sync derives this set from the closure.

## [2.12.0] - 2026-09-30

### Changed
- Bundle Framework pair `ui-f0b69761c5f6-smart-2eb98f0e62a3` (Core `f0b69761`,
  Smart `2eb98f0e`, contracts `a50da72f`), built from ui-source `62ac6ba9`. The
  bundled pair had stood on the 24 September build, so every new portable site
  was born without `light-dark()`, without the neutral alpha ramp, without the
  `surface` and `ghost` appearances, and with a focus ring that read 1.4:1
  against a light surface where an indicator needs 3:1. All of that arrives
  with this pair. The runtime projection grows from 899 to 908 files and the
  Smart projection from 44 runtime components to 47.
- The Smart component manifests name the bundled Smart runtime as their
  upstream revision again, and their recorded digests match their bytes.

### Removed
- The superseded `56cd91e1` runtime projection packet. A site whose lock still
  names it — larena-doc, as of 2.11.0 — stays admitted through
  `superseded_framework_locks` until it repins.

## [2.11.0] - 2026-09-30

### Changed
- A Framework distribution is accepted by its specification rather than by its
  bytes, so a distribution that satisfies the Recipe contract is admitted even
  when its byte layout differs from the one first seen.
- The runtime projection carries the Framework foundation
  (`distr/core/css/core.css` and `distr/core/css/utility.full.css`), and the
  asset planner serves the foundation from the projection whenever a site does
  not pin a typography package.
- A site publishes only the Framework packets its own lock names. Docara carries
  every packet any consumer may pin — several runtime pairs and several
  typography editions — and each site used to receive all of them.
- The own documentation site and the portable stub take their foundation from
  the runtime projection instead of pinning typography package `5.8.0`. While
  the stub pinned a package, every new portable site was born with its
  foundation on the pair the package was cut from, while everything else moved
  with the pinned pair — and the cache key still carried the current pair, so
  the drift was invisible.
- Canonical Composite Smart Component terminology is documented, and the Smart
  component terminology guide ships inside release packages.

### Changed (Framework pairs)
- Bundle Framework pair `ui-56cd91e1d7a3-smart-903ad66c4f4f` (Core `56cd91e1`, Smart
  `903ad66c`, contracts `2d5ebda3`): the data view declares its
  host port, and a product may name its own components.
- Bundle Framework pair `ui-cb1cda301648-smart-81741eac168d` (Core `cb1cda30`, Smart
  `81741eac` unchanged, contracts `a8e60bb3`): Framework contracts
  declare their own version, so a pinned reference cannot name two byte sets.
- Bundle Framework pair `ui-bc8dfd7e4bdb-smart-81741eac168d` (Core `bc8dfd7e`, Smart
  `81741eac`, contracts `76c74e94`): a composition type carries
  the data, intents and settings of its Smart component.
- Bundle Framework pair `ui-d81ccde2bdd5-smart-a916bbadf3aa` (Core `d81ccde2` unchanged, Smart
  `a916bbad`, contracts `d7d5536c`): the data view raises a
  delete intent, isolates its instances and declares its data state.
- Bundle Framework pair `ui-d81ccde2bdd5-smart-004424a4b2e9` (Core `d81ccde2` unchanged, Smart
  `004424a4`, contracts `d6b68e93`): the data view and admin
  menu manifests move to smart-component-manifest v2.
- Bundle Framework pair `ui-d81ccde2bdd5-smart-d448fb5563cc` (Core `d81ccde2` unchanged, Smart
  `d448fb55`, contracts `a6cc2015`): the pagination
  action-for-all option follows the checkbox state.
- Bundle Framework pair `ui-d81ccde2bdd5-smart-b100a5faf6a2` (Core `d81ccde2`, Smart
  `b100a5fa`, contracts `ae77c3ec`): keyboard filter chips,
  stylesheet width caps in shared positioning and bubbling Modal events.
- Bundle Framework pair `ui-1f1c9d42d964-smart-6c5d313aca4d` (Core `1f1c9d42` unchanged, Smart
  `6c5d313a`, contracts `7c8a7659`): Smart Table host intents,
  badge filter options and anchored table menus; bubbling Drawer events.
- Bundle Framework pair `ui-1f1c9d42d964-smart-121e8882d016` (Core `1f1c9d42`, Smart
  `121e8882`, contracts `405d9e96`): shared anchored positioning
  also for the country code, menu flyouts, tooltips and the Admin Menu flyout.
- Bundle Framework pair `ui-5b4289bda9a9-smart-89e4e531ea81` (Core `5b4289bd`, Smart
  `89e4e531`, contracts `fabe4f6e`): shared anchored positioning
  on Floating UI (`SF.Position`) for Dropdown and Datepicker, which also position
  inside isolated example frames.
- Bundle Framework pair `ui-44c4ecc09ba0-smart-bda8a0a90339` (Core `44c4ecc0`,
  Smart `bda8a0a9`, contracts `f9f08647`): editor surfaces are on-demand Smart
  components (`sf-composition-overlay`, `sf-sortable`, `sf-inline-editor`), so
  the dynamic Smart projection grows from 50 to 56 files. The Recipe renderer
  lock stays on Framework `4665ecb`.

### Fixed
- Full-screen example viewer frames fill the viewing area, so modal drawers,
  dialogs and other fixed overlays are shown at full height.
- Align build, quick-start and developer documentation with mandatory Recipe,
  Node.js and the exact Framework execution lock.
- Restore neutral breadcrumb link tones from the reviewed documentation branch.

### Removed
- Unreferenced superseded bundled Framework projection `d813107a`.
- Superseded bundled Framework runtimes `8c22fe2b`, `44c4ecc0`, `5b4289bd` and
  `1f1c9d42`, Smart runtimes `400d80e5`, `bda8a0a9`, `89e4e531`, `121e8882` and
  `6c5d313a`, `b100a5fa`, `d448fb55`, `004424a4` and `a916bbad`; Framework runtime
  `d81ccde2`.
- Bundled typography package `5.8.0`, which no lock names any more. The seven
  fonts in the typography root and edition `5.4.0` stay in the package: a
  consumer still pins them.

## [2.10.0] - 2026-09-16

### Added

- Every portable page now passes through the exact Simai Framework Composition
  Recipe runtime. Markdown and inherited JSON remain the authoring surface,
  while build receipts expose the Recipe, Document and dependency digests.
- A single managed Node session resolves all Recipe documents in one build,
  including automatically projected Markdown pages and explicit file-backed
  Recipe pages.
- Standalone file-backed Composition Recipe builds can be saved as complete
  immutable snapshots, activated with revision comparison and rolled back
  without depending on Larena services or storage.

### Changed

- The declarative renderer now receives its layout, regions, sections and
  blocks from the resolved Composition Document. The former render plan remains
  an internal typed projection for PHP renderers rather than the composition
  authority.
- The page pipeline requires the Composition Recipe resolver explicitly;
  there is no silent fallback to the former page assembly path. Production
  builds require PHP, Node.js and the exact pinned Framework distribution.

### Fixed

- Clean CLI installation tests preserve the exact Node.js executable even
  when testing with a minimal PATH. Quality and release-readiness jobs provide
  the supported Node.js runtime and pinned Framework dependency.

## [2.9.1] - 2026-09-14

### Changed

- The bundled SIMAI Framework projection is refreshed to the Loader-fixed
  `v5.7.0` / Smart `v5.5.0` commit pair while retaining exact immutable locks.
- Framework locks produced from the canonical `ui-source` repository identity
  are accepted alongside the existing `ui` identity.

### Fixed

- Large documentation builds release generated Framework CSS as pages are
  rendered, reuse identical preliminary plans and stream the final diagnostic
  receipt, avoiding memory exhaustion without changing deterministic output.
- Documentation navigation keeps disclosure controls after their labels and
  safely refreshes Framework icons; code and Example headers now share the
  intended alignment and spacing.

## [2.9.0] - 2026-09-10

### Added

- The portable documentation package now carries the exact generated
  Framework contract registry and documentation-source projection alongside
  the immutable runtime lock.
- Framework projection discovers and includes local component CSS dependencies
  and the finite syntax-highlighting chunks used by Docara sites.

### Changed

- The bundled runtime is updated to SIMAI UI Core `v5.7.0` and SIMAI UI Smart
  `v5.5.0`, including the completed SF5 utility, sizing and Smart contracts.
- Utility planning evaluates Loader rules against complete class tokens,
  matching the browser Loader and preventing accidental substring matches.
- The accepted SF5 foundation decisions, evidence matrix and release plan are
  retained as one linked, portable strategy packet.

## [2.8.3] - 2026-09-08

### Changed

- The portable Framework runtime is pinned to SIMAI UI Core `v5.6.4` with
  SIMAI UI Smart `v5.4.1`, including the complete restored Utility/Loader
  contract and exact generated registry.

## [2.8.2] - 2026-09-07

### Fixed

- Example source tabs hide the fullscreen entry action while preserving the
  exit action for an Example that is already fullscreen.
- Soft wrapping now removes the source element's intrinsic `max-content`
  width, so long highlighted lines visibly wrap instead of being clipped.

## [2.8.1] - 2026-09-07

### Fixed

- The Example fullscreen action now remains available on source tabs, matching
  the whole-surface fullscreen contract documented in 2.8.0.

## [2.8.0] - 2026-09-07

### Added

- Example surfaces can expand to the browser fullscreen area while preserving
  access to both the rendered result and every source tab.
- Example source tabs and standalone code blocks provide optional CSS-only
  soft wrapping without changing copied source bytes.
- Fullscreen and wrapping availability can be configured at site, section or
  page level and overridden for an individual Example block.

### Changed

- Source wrapping uses one persisted reader preference across Example sources
  and standalone code blocks, with responsive enablement on narrow screens.
- Example and code toolbar actions expose keyboard-only visible focus and
  state-specific labels and icons.

## [2.7.5] - 2026-09-06

### Fixed

- Sandboxed Example previews install their transferred local font styles before
  external Framework stylesheets, preventing transient opaque-origin WOFF2
  requests and their CORS errors.

## [2.7.4] - 2026-09-06

### Fixed

- The local full-font fallback uses a distinct family name, preventing an
  unreachable Framework font face with the legacy family name from rejecting
  an otherwise loaded sandbox blob.

## [2.7.3] - 2026-09-06

### Fixed

- Sandboxed Example previews receive the exact local full outlined font as a
  transferable blob alongside the prepared subset, so per-icon fallback works
  offline without an opaque-origin network request.

## [2.7.2] - 2026-09-06

### Fixed

- Unknown outlined icons now opt into the local full-font fallback individually,
  so one dynamic icon no longer overrides the prepared subset for every icon in
  the page or in an isolated Example preview.

## [2.7.1] - 2026-09-06

### Changed

- The package-owned SIMAI Framework runtime is updated to immutable UI Core
  `v5.6.2` and Smart `v5.4.1` releases.
- New projects and rebuilt sites receive the corrected Admin Menu and Context
  Menu runtime without changing project-owned content or configuration.

### Fixed

- Admin Menu grows to its natural content height unless its host supplies a
  finite height, where the component takes explicit ownership of overflow.
- The Framework projection preserves discovery for all 63 public components,
  including the bounded `file-preview` and `link` compatibility entries.

## [2.7.0] - 2026-09-02

### Added

- Example previews use deterministic `auto` placement by default, with
  fail-closed `inline` and forced `sandbox` overrides. Build receipts and page
  inspection expose the requested mode, resolved mode and decision reason.

### Fixed

- Sandboxed Example previews now inherit the active semantic Framework tokens
  from the documentation shell and keep a stable inline size while their
  automatic height follows interactive content.
- Result tabs expand sandboxed examples to their measured content height
  without nested scrollbars, an artificial height cap or a second shell
  animation that could temporarily change the available inline size.
- Reference indexes may contain up to 128 bounded Docara and Smart directive
  openings, so a complete component catalog is validated without weakening
  the parser's source budget.

### Changed

- The package-owned SIMAI Framework runtime is updated to immutable `v5.6.1`,
  including base Accordion typography, keyboard-only visible focus, joined
  focus geometry and the optional plus/minus indicator.
- Projects pinned to the exact Framework 5.6.0 lock remain admitted while new
  projects receive the 5.6.1 runtime and source-backed documentation contract.

## [2.6.1] - 2026-09-02

### Changed

- The package-owned SIMAI Framework runtime is updated to immutable `v5.6.0`,
  including the accessible Accordion contract, independent/single-open modes
  and filled, outline and flush surfaces.
- Existing Docara projects pinned to the exact Framework 5.5.0 lock are
  admitted through the bounded compatibility ledger and upgraded without
  editing project-owned documentation or configuration.

## [2.6.0] - 2026-09-01

### Added

- Production builds preload a package-owned, content-addressed Material
  Symbols subset for the documentation shell and lazily fall back to the exact
  local full font when previously unknown icons appear.
- Framework build receipts and `verify-static` bind the icon subset manifest,
  CSS, WOFF2, source font and fallback contract without a new project lock.

### Fixed

- Pre-manifest projects with a project-local Composer runtime but no
  `.docara/engine` now receive `UPGRADE_ENGINE_ADOPTION_REQUIRED` and the exact
  hash-bound `update --dry-run --adopt` route instead of a generic invalid
  project error.

## [2.5.0] - 2026-08-31

### Added

- Project-local `upgrade` resolves and independently verifies a compatible
  stable patch/minor Docara candidate, then promotes dependencies, engine and a
  verified build with stale-input protection and offline compensating rollback.
- `capabilities --json` publishes the exact installed application, schema,
  receipt, tracking and lifecycle contract for CLI and MCP consumers without a
  second hand-maintained command catalogue.
- Upgrade plan, result and journal schemas bind dependency graphs, project
  inputs, verification evidence and interruption recovery.

- Production builds publish a deterministic, report-only
  `.docara/performance.json` receipt with per-page initial request counts,
  transfer sizes, inline CSS/JavaScript sizes and the largest shared local
  resources. `verify-static` recomputes and hash-binds the receipt without
  imposing project-specific performance budgets.

### Fixed

- `init` accepts a directory containing only a verified project-local Composer
  runtime for `simai/docara`, making the canonical install and upgrade path
  possible without a separate shared engine checkout.
- The AI release gate accepts one exact package-owned bootstrap record for the
  transition from Docara 2.4.1, which had no `capabilities` command, while
  rejecting malformed evidence or reuse by later AI contract versions.

- Ordinary Markdown images are constrained by the prose column while
  preserving their intrinsic aspect ratio. Linked, figure and picture images
  no longer overflow narrow content, and small images are not stretched.
- Image, figure, media and embed ratios now compile to the real Framework
  `aspect-*` utilities, so declared proportions are applied instead of
  producing inert `ratio-*` classes. The documented 21:9 ratio remains exact.

## [2.4.1] - 2026-08-30

### Fixed

- Treat the exact project-owned Framework `documentation_source` pointer as
  source-tracking metadata rather than as a mutation of the bundled runtime
  identity. The referenced contract remains independently schema- and
  SHA-256-verified before use.

## [2.4.0] - 2026-08-30

### Added

- Optional `documentation_tracking` connects Markdown pages and reusable
  examples to exact public source contracts without creating a second product
  catalogue. Neutral JSON contracts and the built-in SIMAI Framework provider
  share deterministic `list`, `inspect`, `validate`, `scaffold`, status and
  hash-bound acceptance services across CLI and MCP.
- Documentation status distinguishes current, new, changed, missing,
  missing-example, unverified, orphan and excluded entities. Report mode
  writes a stable `.docara/documentation-status.json` but never blocks a build,
  calls AI or edits Markdown and lock files.
- SIMAI Framework 5.4.1 is pinned with 226 utility families, 63 ordinary
  components, 43 Smart Components and a neutral 334-entity documentation
  contract. The semantic roles of compact-control and large-surface radius
  tokens are now explicit.

### Changed

- Source-aware page scaffolding can derive a minimal reference draft from a
  verified provider template while retaining create-only filesystem and stale
  plan protections.
- Reusable Example sandboxes receive the exact planned Framework styles,
  component scripts and local icon fonts needed by the example. Dynamic
  `data-sf-require` declarations are honoured without treating prose code
  fragments as runtime requirements.
- `docara.steps` is explicitly embeddable in `Surface`, allowing step timelines
  to retain their numbered markers inside full-width content bands.

### Fixed

- Example result height is measured from real content bounds after styles and
  fonts settle, so short results no longer inherit document-height whitespace.
- Example icon and component assets remain local, theme-aware and confined to
  the existing sandbox contract.

## [2.3.0] - 2026-08-29

### Fixed

- Docara now consumes the common SIMAI Framework production Asset Planner.
  Final HTML is analysed through the existing Loader rule registry, exact
  first-frame CSS is emitted as content-hashed stylesheets, scripts keep
  dependency order, and truthful `SF_PRELOADED` data hands late content back to
  the dynamic Loader. Exact builds no longer include `utility.full.css` or a
  Docara-owned component preload list.
- Static verification recalculates every page asset plan from final HTML and
  rejects stale receipts, missing or changed generated CSS, duplicated or
  reordered modules and false preload claims.
- Inter and the active Material Symbols font are preloaded from their exact
  local projections, and fenced code emits a geometry-compatible server
  header before interactive highlighting, preventing late font and code-tool
  upgrades from shifting the page.
- The Framework typography projection uses a metric-compatible local Inter
  fallback with `font-display: optional`, so cold font loading does not reflow
  header, navigation, or page columns and Docara does not need fixed shell
  heights.
- Fenced code uses the generic Framework static-highlight chrome contract:
  syntax highlighting no longer replaces the server-rendered header, scroll
  surface, or copy control after the first paint.
- Framework icon and menu hydration now preserves the server-rendered geometry:
  undefined icons reserve their final size, and Menu registration reuses the
  existing label instead of inserting an additional flex item.
- The documentation shell now remains visible while Framework becomes ready,
  mobile documentation navigation hydrates on first open, static navigation
  labels no longer trigger speculative utility requests, and optional icon
  fonts load only when their variants are used.
- Previous/next page links now keep their text and arrows together, use a
  quieter footer treatment, and preserve clear hover, focus and mobile states.
- Documentation content with the default zero gap now uses normal block flow,
  so adjacent vertical margins collapse instead of accumulating inside a flex
  column. Explicit non-zero content gaps retain the vertical stack behavior.

## [2.2.0] - 2026-08-26

### Added

- Six optional authoring profiles for landing pages, articles, tutorials,
  how-to guides, reference pages and explanations.
- Optional `docara.authoring.json` path rules and per-page `profile` overrides
  without introducing a separate knowledge store or status engine.
- Page-aware SDK operations in the existing `list`, `inspect`, `validate` and
  `scaffold` command families, including stable JSON results shared by CLI and
  MCP consumers.
- Hash-bound page scaffold plans and a unified page inspection payload with
  source, route, effective configuration, examples, links, translations,
  revisions, provenance and diagnostics.

### Changed

- Project validation can aggregate measurable page-profile signals while
  keeping semantic editorial judgment explicitly review-only.
- Projects without `docara.authoring.json` retain their existing build and
  authoring behavior.

### Security

- Page scaffolding rejects traversal, existing targets, symlinks, hardlinks,
  case conflicts, unknown locales/profiles and stale apply plans.

## [2.1.0] - 2026-08-26

### Added

- Project-owned reusable examples under `examples/<id>/` with automatic
  Result, HTML, CSS, and JavaScript tabs, confined assets, dependency-aware
  partial builds, and deterministic example receipts.
- Non-blocking translation tracking for Markdown pages and locale dictionaries,
  including stable human/JSON status reports and hash-bound review acceptance.
- Section-scoped secondary navigation and compact language selection for sites
  that use top-level product navigation.

### Changed

- Example previews report their rendered height to the parent shell so short
  results no longer reserve a large empty frame and source tabs size naturally.
- Fenced code blocks share the same visual language, syntax highlighting, and
  copy control as Example source tabs.
- Hero spacing, step layouts, navigation controls, and related documentation
  components have clearer responsive defaults.

### Security

- Reusable example IDs, source files, assets, links, encodings, sizes, symlinks,
  hardlinks, and case collisions are validated before publication.
- Translation acceptance plans are invalidated whenever an input page,
  dictionary, or lock file changes.

## [2.0.0] - 2026-08-25

### Added

- Standalone PHP compiler for documentation and landing sites authored with
  Markdown and validated JSON.
- Typed Document IR, one PageBuilder, declarative layouts and regions, native
  components, project-owned Smart components, and admitted design artifacts.
- Multilingual routing, navigation, search, backlinks, component catalogues,
  resolved configuration provenance, and static build receipts.
- Full and guarded single-page builds, HTTP preview, static verification, and
  deterministic release packaging with a CycloneDX dependency inventory.
- Developer and AI SDK commands for discovery, inspection, schemas,
  scaffolding, validation, testing, QA, preview, and optional MCP access.
- Transactional engine updates with hash-bound plans, project ownership
  protection, rollback packages, and fail-closed diagnostics.

### Changed

- Docara 2 replaces the former Jigsaw/Mix product with one portable,
  PHP-only static-site architecture.
- SIMAI Framework and admitted Smart assets are exact-pinned and published
  locally; generated sites do not depend on a runtime CDN.
- Site, section, and page settings use schema-validated inheritance instead of
  executable project configuration.

### Fixed

- Deterministic package consumers no longer derive page metadata from archive
  extraction times.
- Build, asset, Smart, locale, redirect, fragment, and static-output checks now
  fail closed on ownership or integrity violations.

### Upgrade

- Docara 1.x projects are not updated in place. Create a new Docara 2 project
  and migrate project-owned content and configuration deliberately.
- For an existing Docara 2 candidate project, update the Composer dependency,
  then run `update --verify`, `update --dry-run`, and `update --apply` before a
  complete build and `verify-static`.

[2.0.0]: https://github.com/simai/docara/releases/tag/v2.0.0
[2.1.0]: https://github.com/simai/docara/releases/tag/v2.1.0
[2.2.0]: https://github.com/simai/docara/releases/tag/v2.2.0
[2.3.0]: https://github.com/simai/docara/releases/tag/v2.3.0
[2.4.0]: https://github.com/simai/docara/releases/tag/v2.4.0
[2.4.1]: https://github.com/simai/docara/releases/tag/v2.4.1
[2.5.0]: https://github.com/simai/docara/releases/tag/v2.5.0
[2.7.1]: https://github.com/simai/docara/releases/tag/v2.7.1
[2.7.0]: https://github.com/simai/docara/releases/tag/v2.7.0
[2.6.1]: https://github.com/simai/docara/releases/tag/v2.6.1
[2.6.0]: https://github.com/simai/docara/releases/tag/v2.6.0
