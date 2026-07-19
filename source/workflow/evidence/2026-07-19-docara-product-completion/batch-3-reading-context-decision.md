# Batch 3 — reading context decision

Date: 2026-07-19
Baseline closure: `f482ced`
Status: implementation-ready
Review mode: `$ux` redesign-refactor and design-system alignment;
`$designer` project-native review.

## Reader outcome

A reader can answer three questions without reopening the global menu:

1. Where am I in the documentation hierarchy?
2. What is on this page?
3. What document comes before or after it?

The implementation follows behavior proven by Docusaurus, VitePress,
Starlight and Retype: global navigation, local page outline, breadcrumbs and
document adjacency are separate visual surfaces but share one content
topology. Their runtimes are not dependencies of Docara.

## Configuration contract

Site, section and page descriptors receive one inherited branch:

```json
{
  "reading": {
    "breadcrumbs": true,
    "toc": true,
    "toc_depth": 3,
    "previous_next": true
  }
}
```

- `$reset: true` uses the existing configuration-merger semantics;
- `toc_depth` is an integer from 2 through 6 and means the maximum heading
  level; the outline always starts at `h2`;
- effective defaults are `true`, `true`, `3`, `true`;
- the `landing` preset ignores all four documentation-reading surfaces;
- interactive personal reading preferences belong to Batch 4 and do not alter
  this authoring contract.

This branch stays separate from `navigation.hidden/order`. Navigation controls
content topology; reading controls which derived context surfaces are shown.

## One canonical topology

`PortableNavigationBuilder` must retain every page in its canonical topology.
A hidden page is excluded only from the menu projection; it is not discarded
before breadcrumbs and adjacency are derived.

The projections are:

- menu: visible nodes only; a hidden overview with visible children remains an
  unlinked section label;
- breadcrumbs: home plus the full path to the current page; an unlinked
  section remains readable text;
- previous/next: depth-first sorted documentation pages of the same locale,
  excluding hidden and landing targets. A hidden current page may still point
  to the nearest eligible neighbours.

The home page omits a one-item breadcrumb. Boundary pages omit the missing
previous or next link; disabled fake controls are forbidden.

## Heading and anchor contract

A dedicated document-outline builder decorates rendered Markdown headings once
before the final page is rendered:

- headings `h1` through `h6` receive deterministic UTF-8 ids;
- ids use lower-case Unicode letters, numbers, marks and hyphens;
- whitespace and punctuation runs become one hyphen;
- punctuation-only text falls back to `section`;
- duplicate ids receive collision-safe `-1`, `-2` suffixes;
- the outline contains only `h2` through the configured maximum;
- skipped heading levels remain a flat semantic list in document order;
- no eligible headings means no desktop or mobile outline surface.

Static verification must reject duplicate ids and unresolved local fragments.

## Simai Framework mapping

No new Smart-component or projection is needed.

- breadcrumbs use the exact pinned Core component classes
  `sf-breadcrumbs`, `sf-breadcrumbs-item`,
  `sf-breadcrumbs-item--link`, `sf-breadcrumbs-item--default` and
  `sf-breadcrumbs-item-container`;
- separators and previous/next arrows use the already admitted `sf-icon`;
- the outline and previous/next controls use native `nav`, `ul`, `a` and
  `details` plus exact Framework utilities for flex, spacing, surface, border,
  radius, text and focus presentation;
- `docara-*` classes are limited to product grid, responsive visibility,
  heading-depth indentation, sticky offsets and stable test hooks;
- Smart/numeric pagination is not document adjacency and remains excluded.

Core breadcrumbs are shipped at exact Core pin
`7e836d8a9414d5da553fb1ab0404721e5b48769a`; its rule declares CSS and JS
autoloading. Docara sets the component-supported `data-max-items` value to the
actual breadcrumb count. The pinned runtime therefore keeps every hierarchy
level visible inside native horizontal scrolling instead of creating its
default English ellipsis control. Runtime autoload and the absence of generated
ellipsis remain exact-browser acceptance gates.

## Responsive composition

- wide desktop: left global navigation, central article, sticky right outline;
- narrower screens: the right rail is replaced by native `details` labelled
  `На этой странице` before the article;
- at the existing mobile breakpoint, the left global navigation remains its
  own `details` surface;
- previous/next links form a pair on wide screens and stack at 390 px;
- primary shell, navigation and touch controls are at least 44 CSS pixels,
  with visible focus and no positive horizontal overflow; inline links and
  embedded pinned Framework components follow their component-level WCAG
  target-and-spacing contract instead of receiving Docara-local overrides.

## Test-first matrix

- schema/inheritance/reset: all three descriptor levels and strict negatives;
- topology: four levels, null-url sections, overview variants, hidden current
  and neighbours, locale/docs filtering, home and first/last boundaries;
- outline: Cyrillic, combining marks, entities, inline markup, duplicates,
  punctuation fallback, depths 2/3/6, skipped levels and empty outline;
- renderer DOM: accessible labels, one current breadcrumb, identical desktop
  and mobile outline data, no empty/disabled surfaces and no reading chrome on
  landing pages;
- verifier: valid fragments, missing fragments, duplicate ids and encoded
  Unicode fragments;
- deterministic build plus desktop/tablet/mobile, keyboard, light/dark,
  sticky, target-size and no-overflow browser acceptance.

## Nonclaims

This decision does not accept Batch 3 implementation, Batch 4 reader settings,
landing, component catalogue, the wider Goal, public release or production
readiness.
