# Larena Docara

Documentation product package for Larena that owns public documentation runtime, documentation content model, search projection and admin contribution surfaces.

The package currently provides persistent localized Pages, assets, menus,
role-aware admin authoring and a public renderer. The bounded Site Settings
adapter lets an Administrator select localized published Pages, public logo /
favicon references and a default locale, then resolves them at `/`.

Docara contributes its page-title field to the shared `SmartRegistry`. The
package-owned manifest reuses the pinned `sf-input` asset graph, exposes
localized developer controls and remains render-only: data binding and effects
are not authorized by the component catalog.

Current status: developer-testable product slice. It does not claim production,
theme builder, full page builder or complete Simai Framework runtime readiness.

Canonical specifications are in `simai/larena-specs`.
