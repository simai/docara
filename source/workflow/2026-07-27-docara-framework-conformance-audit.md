# Docara Framework Conformance Audit

Date: 2026-07-27

Mode: read-only audit of product and Framework code; only audit artifacts were added.

Candidate: `ecfc8b72f34a020b1f7374e11eb5b33c0838aabe` plus the current dirty worktree.
Verdict: `CORRECTION_REQUIRED_BEFORE_MIGRATION`.

## Outcome

Docara already follows the correct architectural direction: product-owned Smart components compose SIMAI Framework components and utilities. The `docara.*` namespace is not a defect and must not be moved mechanically into `ui-smart`.

The conformance problem is in the presentation layer:

- 68 of 147 production CSS rules restate existing utility families;
- 15 rules replace behavior already owned by Framework components;
- 34 rules reveal a generic Framework gap or patch Framework internals from product CSS;
- only 30 rules are currently justified as Docara-owned shell, accessibility, responsive or Smart-view contracts;
- 64 `docara-prototype-*` selectors are design-review scaffolding only and must never be copied directly into production.

Thus 117 of 147 production rules (79.6%) are candidates for removal from Docara after their target utility/component/Framework contract is accepted. This is a migration ceiling, not a promise that all 117 can be deleted in one batch.

## Sources and immutable evidence

Production CSS audited:

| File | Rules | Keep | Utility | Component | Framework |
|---|---:|---:|---:|---:|---:|
| `resources/portable/declarative-shell.css` | 94 | 20 | 53 | 11 | 10 |
| `resources/smart/assets/brand.css` | 15 | 9 | 5 | 0 | 1 |
| `resources/smart/assets/navigation.css` | 24 | 1 | 6 | 0 | 17 |
| `resources/smart/assets/preferences.css` | 6 | 0 | 1 | 4 | 1 |
| `resources/smart/assets/toc.css` | 8 | 0 | 3 | 0 | 5 |
| **Total** | **147** | **30** | **68** | **15** | **34** |

Exact source hashes, selectors, declarations, decisions, targets and batches are recorded in:

- `source/workflow/evidence/2026-07-27-docara-framework-conformance-audit/selector-ledger.json`;
- `source/workflow/evidence/2026-07-27-docara-framework-conformance-audit/selector-ledger.md`;
- `source/workflow/evidence/2026-07-27-docara-framework-conformance-audit/framework-targets.json`;
- `source/workflow/evidence/2026-07-27-docara-framework-conformance-audit/verification.md`.

The comparison uses the exact immutable registry:

- repository: `/Users/rim/Documents/GitHub/ui`;
- commit: `b7e8a2e810c0d49e31cb749a7ab34c373dd48bc6`;
- file: `contracts/generated/framework-contract-registry.json`;
- SHA-256: `2c5963276d31af09770fe41cad04826c04b634f7b2d798d9b0e32864517346b7`;
- embedded compatibility ID: `sf-v5.3.2-7e836d8a-dd786bba`.

## Blocking lock inconsistency

`docs/site/simai-framework.lock.json` and `resources/framework/runtime-lock.json` declare registry compatibility ID `sf-v5.3.2-27f8af31-ab896dc7`, but the immutable registry with their recorded SHA-256 contains `sf-v5.3.2-7e836d8a-dd786bba`.

The bytes are identifiable, but the claimed Core/Smart compatibility tuple is not internally consistent. No Framework migration or readiness claim should build on that lock until a reproducible registry for the actual `27f8af31 / ab896dc7` pair is generated and independently accepted, or the lock is corrected to the exact accepted tuple.

## Ownership boundary

Docara keeps ownership of:

- Markdown and JSON parsing;
- locale/version routing and content trees;
- page/region/layout arrays and product presets;
- product Smart components such as `docara.brand`, `docara.navigation`, `docara.toc` and `docara.preferences`;
- semantic `data-docara-*` hooks and product state;
- documentation-specific orchestration and content data.

SIMAI Framework owns:

- tokens, sizes, surfaces, typography, spacing and responsive utilities;
- generic component visuals and interaction states;
- focus, hover, active, disabled and accessibility presentation;
- modal, input, button, breadcrumb, menu, scrollbar, alert, badge and tree primitives;
- generic Smart rendering contracts and reusable views/presets.

Rule: a `docara-*` class may remain as a semantic JS/data hook without owning duplicate presentation. Product CSS must not target private `.sf-*` internals to repair them.

## Findings by decision

### Keep product contract

The 30 retained rules are mostly:

- the documentation two/three-column shell and responsive layout state;
- skip-link behavior and global reduced-motion safety;
- RTL/LTR direction restoration around custom scroll regions;
- full-bleed landing composition;
- product example-grid/source geometry;
- theme-aware brand asset selection and product brand views.

Even these rules should use Framework variables and configurable Docara geometry. `keep` does not authorize raw pixels, duplicated utility declarations or undocumented values.

### Replace with utilities

The 68 rules cover ordinary layout, display, spacing, sizing, border, radius, surface, typography, aspect ratio, object fit, overflow and position declarations. Their semantic classes may remain where JavaScript needs them, but presentation should move into template classes from the immutable registry.

Priority examples:

- media aspect/object/radius rules -> `utility.aspect-ratio` and object-fit utilities;
- reading/sidebar/outline padding -> padding and spacing utilities;
- brand mark/logo/copy layout -> grid/flex/size/overflow utilities;
- header navigation alignment and sizing -> flex/height/padding utilities;
- ordinary TOC list, position and wrapping -> list/position/typography utilities.

### Replace with existing components

The 15 rules duplicate component responsibilities:

- native mobile sheets and their backdrop -> `smart.modal` side-panel composition;
- preferences panel size, surface shadow and body geometry -> `smart.modal` parameters plus utilities;
- search modal surface, trigger and shadow surfaces -> `smart.modal`, `smart.buttons`, `smart.input` and Framework shadows.

Docara may keep `docara.search` and `docara.preferences` as orchestration components. It should stop drawing their generic controls and modal behavior itself.

### Promote or fix Framework

The 34 rules must not simply be deleted. They reveal missing or incomplete reusable contracts:

1. **Documentation menu view.** Seventeen navigation rules deeply override `.sf-menu*` for levels, disclosure, active/ancestor state, hover, compact density and focus. Create an admitted documentation-tree view/preset in the Framework; keep navigation data generation in `docara.navigation`.
2. **Code block.** `component.highlight` exists, but there is no `smart.code` or `component.code` registry entry. A generic code surface must own language header, copy action, syntax highlighting, cross-axis scrollbar and padding.
3. **Outline/scrollspy view.** The registry contains neither `smart.outline` nor `component.outline`. Indentation, active rail and focus presentation should become an admitted reusable view; Docara keeps heading extraction and scrollspy data.
4. **Keyboard hint.** No `component.kbd`/`smart.kbd` entry exists. Add a small generic inline component or documented recipe.
5. **Framework repairs.** Breadcrumb hidden/overscroll behavior, outline button border, radio hover and Smart host display/focus rules must be fixed at their Framework owners, not patched in Docara.

Registry caution: most relevant existing entries are `discoverable` with `safe_to_suggest=false`; only buttons, gap and overflow among the sampled targets are `ready`. A discoverable entry requires an exact browser fixture and independent acceptance before it replaces product CSS.

## Migration batches

### G0 — lock integrity gate

1. Generate/obtain the exact contract registry for the actual Docara Core/Smart pair.
2. Make registry ID, registry SHA, Core revision and Smart revision agree.
3. Verify from an exact archive and record independent acceptance.

Stop condition: no CSS migration begins on an ambiguous compatibility tuple.

### B1 — use what already exists

1. Move purely presentational rules to Framework utilities in templates.
2. Convert native mobile sheets and preferences/search surfaces to existing `sf-modal` composition.
3. Keep semantic `docara-*` hooks only where runtime code requires them.
4. Remove a local rule only after light/dark, desktop/mobile, keyboard and RTL comparison passes.

Expected ceiling: 83 rules (`68 utility + 15 component`), processed by component rather than as a global rewrite.

### B2 — repair existing Framework contracts

1. Fix outline button, breadcrumb, radio and common focus/host behavior at the Framework source owner.
2. Add the documentation-tree view/preset to the existing menu/component family.
3. Build generated Core/Smart distributions through the accepted builder; never edit generated repositories manually.
4. Independently accept the exact tuple, then update the Docara lock.

### B3 — admit missing generic documentation primitives

Prepare separate admission candidates with demos and contracts for:

- code block;
- inline keyboard hint;
- outline/scrollspy presentation.

Do not create an all-purpose “Docara component” inside Framework. Each candidate must be independently useful to at least one other Framework consumer or have a clear reusable contract.

### B4 — delete and prove

1. Remove migrated product CSS.
2. Re-run the selector ledger; no direct private `.sf-*` repair may remain.
3. Rebuild `docara.test`.
4. Run PHPUnit, broken-link checks, static checks and browser matrix.
5. Obtain independent consumer acceptance before merge/release.

## Required browser matrix

- light and dark themes;
- 1920, 1440 and 390 px;
- documentation and landing presets;
- LTR and RTL;
- keyboard navigation, focus, hover, active, disclosure and modal close paths;
- left navigation, breadcrumbs, code, search, preferences and right outline;
- no-JavaScript readability where the current product contract promises it.

For every batch capture before/after screenshots from the same route and viewport. A static PASS is not sufficient to delete visual CSS.

## Nonclaims

- Product and Framework code were not changed by this audit.
- No feature branch, default branch, tag, release or deploy was created.
- This audit does not claim production readiness or readiness of all Framework components.
- The 64 prototype selectors are not accepted implementation.
