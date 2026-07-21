# Publisher class audit

Date: 2026-07-22
Scope: canonical declarative publisher, portable runtime, search runtime and
component demonstrator. The immutable legacy `PortableHtmlRenderer` is outside
the cleanup scope and remains rollback evidence.

## Decision rule

Use, in order:

1. a shipped Smart-component when the surface owns state and behavior;
2. a shipped Core component when semantic server-rendered HTML is the primary
   contract;
3. exact Framework utilities for atomic layout and presentation;
4. a `docara-*` class only for Docara product geometry, responsive behavior,
   state, accessibility, prose or a stable integration hook.

## Removed duplicates

| Removed or reduced surface | Framework replacement | Evidence |
| --- | --- | --- |
| `docara-header-actions` | `flex-none` | its only rule was `flex: 0 0 auto` |
| `docara-mobile-navigation` class | none | ID and `data-docara-sheet` already own identity and behavior; the class had no consumer |
| `docara-search-input` class | none | `sf-input*` owns presentation and `data-docara-search-input` owns behavior |
| `docara-document-link--previous` | none | only the next modifier has directional presentation; previous had no consumer |
| `docara-feature-grid` | existing grid utilities plus `min-w-0 max-w-none` on items | `data-docara-block="features"` keeps semantic identity |
| `docara-search-result-item` | `min-w-0` | its only rule was `min-width: 0` |
| custom `min-width: 0` on reading/content/search/code/example surfaces | `min-w-0` | exact utility exists in the pinned Framework build |
| custom `overflow: hidden` on code block | `overflow-hidden` | exact utility exists |
| custom `max-width: 100%` on example source | `max-w-full` | exact utility exists |
| custom breadcrumb min-width and overflow declarations | `min-w-0 overflow-x-auto overflow-y-hidden` | exact utilities exist |

## Retained custom classes

| Family | Why it remains |
| --- | --- |
| `docara-docs-layout`, sidebar, outline rail, reading column | Docara-specific three-area geometry and breakpoint composition |
| header and mobile sheet classes | fixed product dimensions, sticky offsets, dialog/backdrop behavior and responsive availability |
| `docara-content`, `docara-prose` | inherited reading width, heading rhythm, anchors and long-form typography |
| search dialog/result/status classes | dialog geometry, result interaction states, excerpts and error state |
| code/example classes | child selectors, syntax presentation and demonstrator preview sizing |
| navigation/pager modifiers | logical-direction and responsive behavior not represented by an exact pinned utility |
| focus selectors and 44 px rules | accessibility protection; no exact min-dimension utility exists without incorrectly fixing width |

## Framework gaps found

The pinned Core breadcrumbs controller sets `hidden` on collapsed items, but
the component's author CSS leaves those items at `display:flex`. Docara carries
one narrow compatibility rule, `.sf-breadcrumbs-item[hidden]{display:none}`,
until the producer fixes that contract.

The same controller creates the ellipsis button with a hard-coded English
accessible label. Docara keeps the component and localizes only that generated
label from its existing language-pack/runtime-copy pipeline. This is an
adapter, not a second breadcrumb implementation.

## Architecture decision

Breadcrumbs remain a server-rendered Core component rather than being replaced
with a client-only `<sf-breadcrumbs>` host. This preserves real links, `nav`,
the current-page state and the complete hierarchy without JavaScript while
still using the shipped Framework controller for progressive disclosure.
