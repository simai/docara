# Changelog

All notable changes to Docara are documented in this file.

## [Unreleased]

### Added

- Project-local `upgrade` resolves and independently verifies a compatible
  stable patch/minor Docara candidate, then promotes dependencies, engine and a
  verified build with stale-input protection and offline compensating rollback.
- `capabilities --json` publishes the exact installed application, schema,
  receipt, tracking and lifecycle contract for CLI and MCP consumers without a
  second hand-maintained command catalogue.
- Upgrade plan, result and journal schemas bind dependency graphs, project
  inputs, verification evidence and interruption recovery.

### Fixed

- `init` accepts a directory containing only a verified project-local Composer
  runtime for `simai/docara`, making the canonical install and upgrade path
  possible without a separate shared engine checkout.
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
