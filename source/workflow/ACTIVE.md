# Active workflow: documentation completeness and component catalog

Date: 2026-07-25
Status: completed
Workflow ID: `2026-07-25-docara-documentation-completeness-and-catalog`

## Current goal

Expose content spacing as a documented Docara setting, simplify the generated
component catalog, include supported component pages in navigation and audit
the public documentation against the current product.

## Workflow

`source/workflow/2026-07-25-docara-documentation-completeness-and-catalog.md`

## Result

- `layout.content.gap` is validated, inherited and rendered with Framework
  `gap-*` utilities.
- Docara's own documentation resolves to `gap-0`.
- The component catalog has no separate filter and renders one card per row.
- All supported generated component pages are visible below the catalog in the
  documentation tree.
- Public configuration, layout, component, schema and architecture docs were
  reconciled with the current implementation.
- Tests, build, static verification and desktop/mobile browser acceptance pass.
- The verified result is served at `https://docara.test/`.

## Evidence

- audit:
  `source/workflow/evidence/2026-07-25-docara-documentation-completeness-and-catalog/documentation-audit.md`;
- acceptance:
  `source/workflow/evidence/2026-07-25-docara-documentation-completeness-and-catalog/acceptance.md`.

`stable_reusable_lessons_or_skip_reason`: generated Framework component CSS
may repeat physical border widths after logical and custom-property
declarations. Browser computed-style acceptance is therefore required for
border-sensitive component states; a product-scoped logical-axis fallback is
acceptable until the immutable Framework distribution carries the generator
fix. Size labels alone are not sufficient browser evidence: the pinned
outline button at Framework size `1` computed to `42px` because its borders
were added around the content box. A standard Framework `h-d0` utility keeps
the Smart component and constrains the contextual header surface to the
documented size scale without a hard-coded pixel value.

The search shortcut refinement keeps the integration equally narrow:
`text-1`, `color-on-surface-variant` and `m-inline-start-1/2` express
typography, tone and spacing through Framework utilities. The outline button
itself exposes its supported `--sf-button--border-color` contract because a
generic border-color utility cannot override the component layer.
