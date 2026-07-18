# Batch 0 navigation decision

Date: 2026-07-19
Status: implementation-ready for Batch 1

## Source-backed product decision

The menu remains one recursive semantic tree. We will not create a separate
mobile tree, a hand-authored sidebar registry or a Docara replacement for the
Framework menu component.

Official platform references support the same information model:

- Docusaurus defines the sidebar as an ordered hierarchy and derives both
  breadcrumbs and previous/next navigation from that path:
  <https://docusaurus.io/docs/sidebar>;
- VitePress treats the sidebar as the primary documentation navigation,
  supports nested and collapsible groups and documents up to six visible
  levels:
  <https://vitepress.dev/reference/default-theme-sidebar>.

The references are behavior evidence only. Their React/Vue/Node runtimes are
not dependencies of Docara.

## Exact Simai Framework contract

The pinned Core revision
`7e836d8a9414d5da553fb1ab0404721e5b48769a` already contains the menu component
and its level contract:

- `sf-menu-element--level-1` uses Framework spacing level 1;
- `sf-menu-element--level-2` uses Framework spacing level 3;
- `sf-menu-element--level-3` uses Framework spacing level 4;
- `sf-menu-element--level-4` uses Framework spacing level 5.

Source:
`distr/component/menu/css/menu.css` at that exact revision. Docara currently
omits these level classes, so the first correction is to use the existing
component correctly. Depths greater than four keep their semantic depth but
use the Framework level-4 presentation until a later pinned Framework contract
adds another level; no moving or unpublished asset is consumed.

## Active-trail roles

Each visible item receives exactly one product state:

- `page`: the current URL, with `aria-current="page"`, the strongest weight and
  a primary inline marker;
- `section`: the direct parent of the current page, with a secondary surface,
  medium marker and stronger label;
- `ancestor`: every more distant parent in the active trail, with a quiet
  Framework surface and retained disclosure signal.

The presentation will set documented Framework component custom properties and
theme tokens. Structural indentation, weight and marker thickness ensure that
the result does not depend on color alone.

Collapsing an active ancestor is allowed. Its role remains visible and its
disclosure accessible name states that the branch contains the current page.
Direct section links remain links; disclosure remains a separate native
button.

## Batch 1 acceptance checklist

Automated output:

- all four sample levels contain the matching
  `sf-menu-element--level-{n}` class;
- the active page, direct section and distant ancestors have deterministic
  `data-docara-active-role` values;
- only the active page has `aria-current="page"`;
- all active ancestors start expanded;
- disclosure labels distinguish a branch that contains the current page;
- desktop and mobile render the same recursive tree.

Browser and design:

- hierarchy is readable at desktop and 390px without relying on color;
- current page, current section and ancestor trail are distinguishable in both
  light and dark themes;
- after collapsing the current section or a higher ancestor, the visible row
  still signals that the current page is inside it;
- keyboard focus, direct Enter navigation, disclosure, mobile Escape/focus
  return and active-rail reveal keep working;
- no horizontal page overflow or clipped active row is introduced.

## Nonclaims

This decision does not claim search, TOC, breadcrumbs, previous/next, reading
settings, landing or component-catalogue completion. Those remain later
batches of the same Goal.
