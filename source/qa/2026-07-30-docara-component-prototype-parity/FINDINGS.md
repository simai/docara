# Findings register

Status: accepted local candidate.

Findings will be recorded as one of:

- `implemented_correctly`;
- `implemented_partially`;
- `prototype_only`;
- `runtime_only_obsolete`;
- `native_or_generated_capability`;
- `intentional_non_component`.

No completion verdict is allowed while a prototype row remains unclassified.

## Prototype-to-runtime matrix

| Accepted scenario | Current state | Finding | Required correction |
| --- | --- | --- | --- |
| Badge | public inline component | `implemented_correctly` | accepted Framework variants and size examples are executable |
| Icon | public inline component | `implemented_correctly` | shape, weight, size and use in text/cards are covered |
| Button | public inline component | `implemented_correctly` | compact inline action model is covered |
| Kbd | public inline component | `implemented_correctly` | keyboard input remains distinct from inline code |
| Inline code | native Markdown | `native_or_generated_capability` | no duplicate Docara component is advertised |
| Alert | public block component | `implemented_correctly` | semantic variants, icons and concise calls are covered |
| Quote | native Markdown | `native_or_generated_capability` | no duplicate component is advertised |
| Details / FAQ | public block component | `implemented_correctly` | disclosure and FAQ presentations are covered |
| Tabs | public block component | `implemented_correctly` | executable tabs and keyboard semantics are covered |
| Steps | public block component | `implemented_correctly` | numbered/current/completed states are covered |
| Tree | public block component | `implemented_correctly` | file/folder semantics and nested expansion are covered |
| Image / Figure | public block plus native image | `implemented_correctly` | ratios, captions and responsive media are covered |
| Diagram | public block component | `implemented_correctly` | Mermaid source, runtime asset and fallback are covered |
| Math | public block component | `implemented_correctly` | TeX source, runtime asset and fallback are covered |
| Download | public block component | `implemented_correctly` | explicit downloadable resource is covered |
| Example with source | public block component | `implemented_correctly` | Markdown and HTML/CSS/JavaScript source sets are executable |
| Code | native fenced code plus public wrapper | `implemented_correctly` | inline and external-file sources share one visual renderer |
| Table | native Markdown | `native_or_generated_capability` | accepted comfortable table presentation is the default |
| Links, footnotes and sources | native Markdown | `native_or_generated_capability` | links, footnotes and source references are covered |
| Automatic metadata | generated build feature | `native_or_generated_capability` | build derives source and version facts without duplicate author input |
| Backlinks | public generated component | `implemented_correctly` | reverse internal-link index is hydrated after page compilation |
| Embed | public block component | `implemented_correctly` | provider, consent and sandbox boundary are covered |
| Grid + Card | composable public components | `implemented_correctly` | replaces separate columns/features concepts |
| Hero | public block component | `implemented_correctly` | split, compact and centered variants use real fixtures |
| Banner | public block component | `implemented_correctly` | bounded announcement plus action is covered |
| Logos | public block component | `implemented_correctly` | text, linked, muted and image examples are covered |
| Media | public block component | `implemented_correctly` | left/right responsive order is covered |
| HTML escape hatch | public restricted component | `implemented_correctly` | sandboxed HTML is explicit and raw HTML is not silently enabled |

## Obsolete public duplicates

The following executable renderers remain temporarily available so authored
legacy content can still build, but they must not be offered as the preferred
public component model:

- `docara.columns` — replaced by `docara.grid`;
- `docara.cta` — replaced by inline `docara.button`;
- `docara.features` — replaced by Grid + Card + Icon;
- `docara.promo` — use `docara.banner` for a bounded announcement or `docara.hero` for a page lead;
- `docara.showcase` — use `docara.media` or Grid + Card + Figure.

## Root cause

The HTML prototype was treated as a visual reference, not as an executable
acceptance contract. A component could therefore look accepted in the prototype
without having all five required surfaces: parser, renderer, catalog entry,
reference page and built-site verification. The correction adds a parity check
so the accepted inventory and the public runtime cannot drift silently again.

## Corrections completed

- Replaced placeholder-only Hero, Logos and Example fixtures with accepted,
  visible variants.
- Added executable publishing capabilities for Tabs, Diagram, Math, Banner,
  Backlinks, metadata, footnotes and sandboxed HTML.
- Made backlinks and optional publisher assets independent build stages.
- Corrected locale-relative component links and removed dead links to retired
  duplicate components.
- Added fail-closed regression markers for the accepted prototype inventory.

No accepted prototype row remains `prototype_only`, `implemented_partially` or
unclassified. This does not claim that Docara now contains every component any
future documentation product could need.
