# Simai Framework building-block map

Date: 2026-07-19
Mode: exact-revision, read-only

## Immutable pair

- Core: `simai/ui` `v5.3.2` at
  `7e836d8a9414d5da553fb1ab0404721e5b48769a`;
- Smart: `simai/ui-smart` `v5.3.1` at
  `dd786bbae98391fb21df9b4e1e6cd402ead0614c`;
- contract registry source: `b7e8a2e810c0d49e31cb749a7ab34c373dd48bc6`;
- pair: `sf-v5.3.2-7e836d8a-dd786bba`.

The registry contains 331 records: 225 utilities, 60 components, 45 Smart
components and one recipe. Registry presence, physical shipment and admission
to portable Docara are three different states. The registry explicitly does
not claim full compatibility, readiness of every item or production readiness.

## Portable admission today

Portable Docara currently admits only:

- `ui.alert` -> `sf-alert`;
- `ui.button` -> `sf-button`;
- `sf-icon` as their shell/runtime dependency.

Only alert, buttons and icons are present in the portable asset projection.
The full Core CSS and utility CSS are loaded, so Core utilities and presentational
components can be used as recipes without inventing a parallel CSS system.

## Menu decision

The exact Core pin ships the recursive menu component in
`distr/component/menu/`. Its level classes cover levels 1 through 4. The exact
`ui-play` revision `0a393e85f0c6a137ae024f442dd52cc34d5f0508`
contains runnable four-level markup under
`examples/components/menu/default/index.html`.

The Framework loader computes semantic depth and clamps visual level classes
to 1..4. Docara therefore keeps the existing Core menu and applies its level
contract. Replacing it merely to call the result Smart would add risk without
adding a reader capability.

Physically shipped alternatives `sf-tree` and `sf-admin-menu` remain outside
the portable projection. `sf-admin-menu` search covers its labels and ancestor
path only; it is not documentation full-text search.

## Product feature map

| Product need | Exact pin | Portable state | Decision |
| --- | --- | --- | --- |
| Four-level navigation | Core menu shipped | available | keep and use correctly |
| Full-text site search | no dedicated component | gap | Docara static index/controller, Framework UI recipes |
| Heading TOC/scroll context | no dedicated TOC | gap | Docara heading extraction and semantic links |
| Breadcrumbs | Core and `sf-breadcrumbs` shipped | not projected | Docara semantic recipe first |
| Previous/next documents | numeric pagination is not document adjacency | gap | Docara tree-derived recipe |
| Settings dialog/select | `sf-modal` and `sf-dropdown` shipped | not projected | simple semantic Docara controller with Framework primitives first |
| Drawer | only divergent unreleased Smart branch | forbidden | do not consume |
| Landing sections | no hero/features/CTA product layer | gap | Docara recipes from Framework utilities/components |
| Tabs | Core/Smart tabs shipped | not projected | typed composition + keyboard contract before use |
| Code/demo catalogue | highlight/clipboard components shipped but discoverable only | not accepted | explicit code/copy/a11y contract before promotion |

## Safe integration rule

When a shipped Smart component becomes necessary, it enters portable Docara
only through one compatibility transaction: manifest, exact runtime record,
asset projection and hashes, schema, positive/negative tests, clean build and
keyboard/responsive/browser acceptance. A local branch, moving reference or
physically present file is not sufficient evidence.

## Source anchors

- Docara lock: `docs/site/simai-framework.lock.json`;
- lock-derived manifest discovery and byte verification:
  `src/Framework/FrameworkManifestRepository.php`;
- portable projection validator: `src/Framework/FrameworkLock.php`;
- Framework plan: `src/Framework/FrameworkAssetPlanner.php`;
- Core menu exact source:
  `simai/ui@7e836d8a9414d5da553fb1ab0404721e5b48769a:distr/component/menu/`;
- loader contract inspected at the exact read-only revision:
  `simai/ui-loader@5560df691a76ef949b73c68c0414ca58bde676cd:src/component/menu/js/_menu.js`.

## Nonclaims

This map does not promote any discoverable component to ready, change a
Framework repository or claim that all 331 records are supported by Docara.
