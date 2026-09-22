# Changelog

All notable changes to Docara are documented in this file.

## [Unreleased]

### Changed
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
  `6c5d313a`, `b100a5fa` and `d448fb55`.

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
