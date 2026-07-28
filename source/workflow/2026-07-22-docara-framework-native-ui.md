# Workflow: Framework-native Docara UI

Date: 2026-07-22
Status: Docara implementation complete; Framework producer correction required
Workflow ID: `2026-07-22-docara-framework-native-ui`
Parent track: `docara-consolidation`
Baseline: `2eea071`
Primary owner: `docara`
Companions: `sf5`, `ux`, `tester`, `ops`

## Goal

Bring the canonical declarative Docara publisher back to the Simai Framework
contract: native semantic tags, Framework utilities, components and Smart
components define visual language and adaptive geometry. Docara CSS owns only
documentation layout, behavior hooks and explicit product composition that the
Framework does not provide.

## Trigger

The previous compact-header batch selected Framework size `1` but retained a
private `44px` minimum. That changed the Framework's nominal size `1` from
36/40 px to 44 px and made the modifier misleading. The same audit found
private heading sizes, line heights, control floors and duplicated button
presentation elsewhere in the canonical surface.

## Source contract

- Simai Framework compiled button, icon-button, input, radio, menu,
  breadcrumbs and typography sources under `/Users/rim/Documents/GitHub/ui`;
- the adaptive sizing audit at
  `/Users/rim/Documents/GitHub/ui-control/source/workflow/2026-07-19-adaptive-sizing-system-audit.md`;
- canonical Docara sources under `resources/publisher`, `resources/smart`,
  `resources/portable`, `src/PortableSite/PortableMarkdownRenderer.php` and
  `src/PortableSite/PortableComponentCatalogProjector.php`;
- the legacy `PortableHtmlRenderer` is immutable rollback/reference evidence
  and is excluded from this migration.

## Rules

1. A Framework component size modifier is the sole owner of its height,
   padding, icon and text geometry.
2. Semantic headings use the Framework's native `h1`-`h6` contract. A product
   hero must become an explicit Smart/component preset, not a global prose
   override.
3. Existing Framework states own focus, hover, border, surface and color.
   Docara adds them only to product-owned native controls or composites.
4. `data-docara-*` is the behavior/data API. A `docara-*` class is retained
   only for layout, a product Smart component, responsive visibility or a
   documented Framework gap.
5. No raw pixel height may override `sf-button`, `sf-icon-button`, `sf-input`,
   `sf-radio-button`, `sf-menu`, `sf-breadcrumbs` or Framework-generated Copy.
6. Public push, merge, tag, package release and production-readiness claims are
   excluded. Local `docara.test` publication requires staging, backup,
   deterministic verification and browser acceptance.

## Audit matrix

| Surface | Finding | Decision |
| --- | --- | --- |
| header search/settings | size `1` plus private 44 px floor | remove floor; Framework owns geometry |
| mobile/search/sheet close buttons | size modifier plus redundant floor | remove floor |
| breadcrumbs | Framework component plus private 44 px floor | remove floor |
| prose headings | private clamp/weight/line-height | remove; native heading contract |
| radio options | Framework component plus private 44 px floor | remove floor |
| highlighted code Copy | Framework generates Smart Copy size `1/3`; Docara enlarges it | remove override |
| CTA | Framework button plus duplicate colors/padding/line-height | retain component and layout utilities only |
| right outline | product Smart component using utilities plus private heights | remove private heights |
| brand | product Smart component | retain structural logo rules; use typography utilities |
| documentation grids/rails/dialogs | product layout not supplied by Framework | retain bounded structural rules |
| catalogue native selects | no shipped select component in pinned Framework | retain one explicit bridge, derive size from Framework UI tokens |
| left navigation | product Smart preset over a known generic-menu relation gap | retain token-based component adapter; audit separately from page CSS |

## Acceptance

- static guard rejects private pixel height floors on Framework components;
- generated headings have no Docara font-size/line-height/weight override;
- header search/settings follow size `1` at desktop and mobile;
- existing navigation, search, dialogs, reader settings, code copy, component
  catalogue and locale behavior remain operational;
- focused and full PHPUnit pass;
- exact build passes static verification and browser checks at desktop and
  mobile widths;
- local publication has a recorded rollback path.

## Result

The canonical Docara surface no longer assigns private control floors,
heading typography or duplicate presentation to Framework components. Static
guards now reject the same regression in future changes. Search, reader
settings, themes, mobile navigation and responsive layout passed browser
acceptance without horizontal overflow or console errors.

The browser check also proved two upstream producer defects at the pinned
Framework revision `7e836d8a9414d5da553fb1ab0404721e5b48769a`:

1. mobile `:root` is `14px`, so all rem-based primitives and nominal UI
   heights are multiplied by `0.875`;
2. the outline button's border is added outside the nominal formula, so
   desktop size `1` is 42 px instead of 40 px.

Docara does not compensate for either defect. The correction belongs to the
Framework producer and must be released as a new immutable revision before
Docara changes its pin. Exact results, measurements and rollback evidence are
recorded in
`source/workflow/evidence/2026-07-22-docara-framework-native-ui/acceptance.md`.

## Kaizen

The previous claim that a universal 44 px floor was "correct" is superseded.
Accessibility remains mandatory, but it must be expressed through the agreed
Framework scale and component semantics rather than silently changing a named
size modifier.

## Navigation disclosure correction

Browser review of the expanded `Миграция` item found one remaining conflict:
the `docara.navigation` template correctly selected
`sf-icon-button--size-1/3`, while product CSS replaced that geometry with
`var(--sf-d1)` and negative margins. The custom geometry was removed. The
Framework icon button now owns its 24 px desktop size, and the Framework Menu
gap owns the 8 px separation from the label.

Smart CSS and JavaScript URLs now include a SHA-256 `docara_v` query. This is
part of the correction because a normal reload otherwise retained the old
unversioned Smart CSS and visually preserved the defect after publication.
The version is derived from the exact source bytes, so it changes only when
the Smart asset changes.
