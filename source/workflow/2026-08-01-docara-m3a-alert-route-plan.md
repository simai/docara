# M3-A plan: `/ru/components/alert/`

Date: 2026-08-01

Status: plan checkpoint ready for independent review; implementation is not
started or authorized

Base revision: `f911db16ba07aa6735f09ab2a63370bfd2fa608f`

## Bounded outcome

Move exactly one public route, `/ru/components/alert/`, from the current
catalog projection to the accepted content-first pipeline while preserving its
URL, Russian content, HTML, linked assets, appearance and behavior. The future
implementation must use the existing `PageBuilder`, compiler, document
renderer registry and Smart gateway. It must not add a second engine.

This document is a production/test plan only. It does not create
`docs/site/content/ru/components/alert.md`, modify runtime code or reduce any
legacy boundary.

## Current owner and projection path

The reproducible path at the base revision is:

1. `resources/component-catalog/typed/docara.alert.json` owns the typed catalog
   definition: alias `alert`, renderer `docara.alert.v1`, directive
   `:::alert`, `type` and `variant` props.
2. `resources/component-catalog/source-metadata.json` binds `docara.alert` to
   that definition.
3. `resources/language-packs/ru.json#/components/docara.alert` owns all current
   page prose: title, description, limitations, states, parameter labels,
   descriptions and values. Its `example_ref` points to
   `resources/component-catalog/examples/docara.alert.ru.md`.
4. That example file owns the five rendered Alert examples and their example
   source text.
5. `EffectiveComponentCatalogBuilder` combines the definition and locale
   presentation. `AuthoredComponentPageIndex` finds no physical Alert page, so
   it does not suppress generation.
6. `PortableComponentCatalogProjector` renders the example Markdown through
   `PortableMarkdownRenderer::renderAlert()`, composes the catalog detail
   fragment, and synthesizes page path `content/ru/components/alert.md`.
7. `PortableSiteBuilder` sends that projection to the legacy page/layout path
   and writes `ru/components/alert/index.html`, whose public URL is
   `/ru/components/alert/`.

The route is therefore currently a `generated_projection`, not a physical
Markdown-owned page. The exact owner map is recorded in
`m3a-alert-plan/OWNER-MAP.md`.

`resources/smart/ui.alert/templates/default.php` is not the renderer for this
documentation component and must not be treated as its target template. The
current page emits static `sf-alert` markup. It does not load a route-local
Alert script; `resources/framework/assets/smart/alert/js/alert.js` is only part
of the global copied framework asset set.

## Target ownership and authoring contract

The sole public owner will be:

`docs/site/content/ru/components/alert.md`

That file must contain the complete visible page prose and example source:
title, description, five Alert examples, limitations, states, parameter
documentation and values. No Alert page prose may remain active in
`content/ru/lang.json`, `docara.json`, `section.json`, `.page.json`, a component
manifest or any language pack. `content/ru/lang.json` remains limited to shared
interface labels.

The typed catalog definition may continue to own machine behavior and prop
constraints. Any structural component manifest or template added by the
implementation may contain only identity, schema, renderer/template and asset
metadata—not documentation prose.

## Minimum target IR and rendering delta

The existing typed nodes for headings, paragraphs, tables, code blocks and
examples are sufficient for the article structure. Alert requires one generic
block-component IR capability; it must not introduce an Alert-only document
pipeline.

Minimum proposed node contract:

- `component_block` with canonical component id, typed props, ordered typed
  child nodes and physical source span;
- alias resolution `alert -> docara.alert` in the existing component alias
  registry;
- props `type: clear|info|success|warning|danger` with default `info`, and
  `variant: default|flat|outlined` with default `default`;
- child contract: a heading at level 2–5 plus non-empty supporting content,
  matching the existing fail-closed behavior;
- a renderer-registry entry for `component_block` which delegates each
  resolved call to the content mode of the existing Smart gateway;
- a Smart component registration/template that returns the exact current
  `<section data-docara-block="alert" ... class="sf-alert ...">` artifact,
  plus provenance and its actual asset requirements.

Unknown alias, prop, prop value, child shape, node type or renderer must fail
with file, line and column. The compiler creates the typed `DocumentIr` only in
memory. A test snapshot or `--dump-ir` is permitted evidence; no required page
JSON/JSONL artifact may appear in the public build.

If the existing generic component node can be extended without weakening the
Badge contract, prefer that smaller change. A parallel Alert parser, renderer
or `PageBuilder` is a stop condition.

## Early route selection for isolated builds

M2's accepted limitation is explicit: `PortableSiteBuilder` currently creates
global component/catalog/example projections before applying `--page` at
`$pagesToRender`. M3-A must remove that behavior for the selected route.

The implementation sequence must be:

1. normalize and validate the requested route before content compilation;
2. resolve it through a lightweight typed route/source index that reads paths
   and routing metadata only and does not render pages, examples or catalogs;
3. form the selected `PageSource` set: all routes for a full build, or exactly
   `docs/site/content/ru/components/alert.md` for this isolated build;
4. invoke the same `PageBuilder` once per selected source;
5. derive route-local HTML, outline, metadata, asset requirements and
   diagnostics from those `PageBuilderResult` objects;
6. for an isolated update, retain the current atomic-copy model and reuse the
   already complete destination's unchanged global projections rather than
   recompiling every catalog/example page. Update only artifacts whose
   repository contract makes them route-dependent;
7. for a full build, derive global search/navigation/catalog projections from
   the complete result set.

Tests must spy on compiler/catalog/example projector calls and prove that the
Alert-only build never compiles or renders an unselected route. Full and
isolated modes must instantiate the same `PageBuilder`; only the selected
route set may differ. If correct global artifacts cannot be maintained without
a second pipeline, implementation stops for plan revision.

## Parity gates and exact boundary reduction

No legacy entry changes before all focused, full, static and browser checks
pass and the implementation commit has a one-commit rollback path. After that
evidence, and only in the same bounded implementation checkpoint:

- remove exactly `/ru/components/alert/` from
  `resources/legacy-public-source-allowlist.json`;
- reduce only the Russian `components` maximum from 42 to 41 after removing
  the active `components.docara.alert` presentation from
  `resources/language-packs/ru.json`;
- update the `content-first`, `typed-ir`, `smart-gateway` and `pagebuilder`
  implementation mappings with the Alert code/tests/evidence/deletion gate;
- retain the typed catalog definition while it remains the behavior contract;
- retain the legacy Russian example file as rollback evidence until a separate
  zero-reference deletion gate proves it inactive. It must not remain an
  active page-prose input after parity.

No English entry, other route, component definition, Framework file or global
legacy path is reduced by this slice.

## Test matrix

### Focused

- compiler snapshot for the physical Alert Markdown and source spans;
- five Alert calls, exact prop normalization and exact current content HTML;
- negative alias, prop, enum, child-shape, unknown-node and missing-renderer
  cases with source locations;
- registry/gateway call count and provenance/assets;
- authored-page suppression of the legacy generated projection;
- isolated-route spies proving no unrelated compilation/catalog/example
  projection;
- exact allowlist and Russian language-pack delta, with non-growth guards;
- Badge regression through the same registry/gateway/PageBuilder.

### Full and isolated build

Run from a disposable snapshot bound to the implementation candidate:

```text
php ../../docara build m3a-alert-full
php ../../docara verify-static build_m3a-alert-full
php ../../docara build m3a-alert-single
php ../../docara build m3a-alert-single --page=/ru/components/alert/
diff -qr build_m3a-alert-full build_m3a-alert-single
```

Required results: 103 pages and 321 files unless a separately explained
derived-artifact delta is accepted; zero tree differences; Alert HTML SHA-256
equal to the baseline; identical linked asset URLs/hashes; no public IR dump;
and a trace proving the isolated compiler selected exactly one physical route.

### Static and repository

- full PHPUnit and changed-file formatter/lint;
- `verify-static` with zero broken local references;
- JSON parse, graph validator, graph file refs/anchors and mapping completeness;
- changed Markdown links;
- `git diff --check` and repo hygiene with no forbidden paths;
- compare the candidate to the exact parent and record every changed path.

### Browser

Serve the disposable full build and check Chromium at 1440x1000 and 390x844,
in light and dark themes. Required: title, breadcrumbs, navigation, TOC,
parameter tables and all five Alert examples; `status` semantics for non-danger
states and `alert` semantics for danger; keyboard access to tabs/settings;
no clipping at mobile width; zero console errors and warnings. Store capture
hashes and an accessible-snapshot summary in the implementation evidence.

## Rollback and stop conditions

Rollback is a revert of the single future implementation checkpoint. Until
parity passes, keep the existing language-pack record, example, catalog
projection and allowlist entry untouched. If parity fails after the boundary
reduction, restore those exact entries and rebuild from the same parent before
any further route work.

Stop without implementation-readiness claims when:

- the legacy owner or exact baseline cannot be reproduced;
- the Alert page needs a broad architecture redesign, second renderer or
  second build pipeline;
- the M2 Badge/full-build baseline regresses;
- isolated selection still performs unselected compilation, catalog or
  example projection;
- exact HTML/assets/content/URL or browser semantics diverge;
- a product, dependency, Framework or other-route change is required;
- independent review has not accepted this plan.

## Traceability

- one physical Markdown owner and shared-string boundaries:
  `docs/specification/DOCARA-TZ.md` §§ 5–6 and
  `docs/specification/authoring/AUTHORING-CONTRACT.md`;
- in-memory typed IR, registry and Smart gateway:
  `docs/specification/DOCARA-TZ.md` §§ 7–10 and
  `docs/specification/architecture/UNIFIED-ARCHITECTURE.md`;
- one PageBuilder and route-set-only build difference:
  `docs/specification/DOCARA-TZ.md` § 14,
  `docs/specification/architecture/UNIFIED-ARCHITECTURE.md` and
  `docs/specification/ACCEPTANCE.md`;
- exact parity, rollback and legacy deletion gates:
  `docs/specification/ACCEPTANCE.md`,
  `docs/specification/implementation/ROADMAP.md` M3 and
  `source/handoff/docara-unified-architecture/NEXT.md`;
- M2 accepted state and its isolated-selection note:
  `source/workflow/evidence/2026-08-01-docara-unified-architecture/M2-EVIDENCE.md`
  plus the independent `PASS_WITH_NOTES` assignment that initiated this
  checkpoint.

Baseline, ownership, planned evidence paths and validation results live under
`source/workflow/evidence/2026-08-01-docara-unified-architecture/m3a-alert-plan/`.

## Nonclaims

- no Alert migration or M3 implementation was performed;
- no M3 source-ownership, migration-coverage, release or production gate is
  claimed;
- no runtime, content, dependency, lock, default branch, merge, push, tag,
  release or deploy change is part of this checkpoint.
