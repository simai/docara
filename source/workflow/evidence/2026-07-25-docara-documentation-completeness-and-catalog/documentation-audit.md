# Documentation audit

Date: 2026-07-25
Verdict: implementation complete, final publication pending

## Method

The audit compared:

1. the current site, section and page schemas;
2. the accepted workflows from 2026-07-18 through 2026-07-24;
3. current compiler, publisher, Smart manifests and generated artifacts;
4. every Russian user-facing documentation heading and targeted contract term.

Retired Jigsaw/Mix behavior was not used as a product source. References to
`_section.json` remain only as an explicit migration diagnostic. The word
`legacy` remains only where it names the current redirect option or migration
evidence.

## Coverage matrix

| Product area | Public documentation | Result |
| --- | --- | --- |
| Project files and `section.json` | `authoring/project-files`, `configuration` | current |
| Inheritance, reset and provenance | `authoring/inheritance`, `configuration` | current |
| Locale registry, routing, RTL and language packs | `authoring/localization`, `multilingual-site`, `language-packs` | current |
| Layout, container and content gap | `authoring/layout-and-navigation`, `configuration`, `reference/schemas` | corrected |
| Regions and declarative composition | `authoring/regions`, `development/architecture`, `composition-extensions` | current |
| Branding and media | `authoring/branding`, `authoring/markdown` | current |
| Header navigation and mobile projection | `authoring/layout-and-navigation`, `multilingual-site` | current |
| Reading context and mobile contents | `authoring/reading-context` | current |
| Modular reader preferences | `authoring/reader-settings` | current |
| Search | `authoring/search` | current |
| Redirects | `authoring/redirects`, `configuration` | current |
| Documentation and landing presets | `authoring/layout-and-navigation`, `landing` | current |
| Typed landing blocks and full bleed | `landing`, generated component catalog | current |
| Smart-component admission and ownership | `development/smart-components`, `framework-components` | current |
| Rendering pipeline and separation of concerns | `development/architecture` | expanded |
| Build, deterministic output, verification and update | `build/*` | current |
| Generated component catalog | `components`, generated catalog | simplified |

## Corrections in this batch

- Added the inherited `layout.content.gap` contract, valid range, defaults,
  Framework mapping and reset behavior.
- Added the missing `header_navigation` branch to the level matrix.
- Explained the boundary between Markdown, JSON settings, resolved plans,
  layout/section/Smart contracts, trusted templates and publisher.
- Documented automatic component detail pages in the navigation tree.
- Removed obsolete catalog-filter runtime, UI and language strings.
- Changed the catalog to one card per row.

## Current intentional boundaries

- Scrollbar appearance, modal scrim presets and system shadows are Framework
  behavior. Authors do not configure them through Docara, so they are not
  presented as Docara project settings.
- Unavailable catalog entries remain visible as explicit capability gaps, but
  do not receive callable detail pages or menu entries.
- Generated component detail pages are not handwritten documentation; their
  content is derived from owner records, manifests and executable examples.

## Verification

- Focused tests: 46 tests, 1627 assertions — PASS.
- Full tests: 331 tests, 5126 assertions — PASS.
- Pint: PASS.
- Production build: 90 source pages — PASS.
- Static verification: 198 HTML pages, 14237 local references, zero broken —
  PASS.
- Generated `/ru/components/catalog/` resolves `layout.content.gap` to `0`
  from `content/ru/section.json`.
- Catalog output contains one-column lists, no filter UI and generated menu
  links for supported component pages.
