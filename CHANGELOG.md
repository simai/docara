# Changelog

All notable changes to Docara are documented in this file.

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
