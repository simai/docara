# Framework component classification

Date: 2026-07-18
Status: normative for this Docara batch

User-facing documentation calls the product **Simai Framework**. The term
`sf5` is a technical routing label only and is not introduced in product names,
page copy, aliases, or new code prefixes.

| Authoring surface | Classification | Docara implementation | Boundary |
| --- | --- | --- | --- |
| Alert | existing Smart-component | `ui.alert` -> Smart alert -> `sf-alert` | use supported static props; do not claim unsupported closable behavior |
| Button | existing Smart-component | `ui.button` -> Smart buttons -> `sf-button` | action button, not a fabricated link/navigation contract |
| Code | semantic element + utilities | native `<pre><code>` with Framework surface, border, radius, padding, and overflow utilities | no separate Smart-component exists or is needed |
| Steps | documentation recipe | one semantic ordered list plus layout/spacing utilities | do not reuse wizard/progress `sf-steps` for prose instructions |
| Table | semantic element + utilities | Markdown table with responsive overflow and existing table utilities | a data grid is a separate future contract |
| Card | utility recipe | `bg-surface-0 border border-outline-variant radius-2 p-3 flex flex-col gap-1` | do not emit an inert `sf-card` substitute |
| Tabs | **blocked** | no Docara directive is enabled | backend manifest is absent and `cl-tabs` has an incomplete/mismatched CSS/JS publication contract |

## Exact Rule

Docara may expose a Markdown directive only when it can bind to an exact,
versioned backend manifest and the complete runtime asset contract. When no
Smart-component is appropriate, semantic Markdown/HTML plus existing utilities
is the preferred result. A missing contract is documented as a blocker rather
than hidden behind locally invented markup, JavaScript, aliases, or assets.

## Tabs Exit Criteria

Tabs may move from `blocked` to `supported` only after all of the following are
true:

1. the Framework owner publishes an exact backend manifest and slot/content
   model;
2. the matching Core/Smart compatibility pair exposes coherent CSS and JS;
3. keyboard behavior, focus, ARIA relationships, no-JS degradation, and nested
   Markdown are covered by tests;
4. Docara binds the directive to that exact revision and independent browser
   acceptance passes.

Until then, documentation uses headings or a normal list of links instead of
tabs.
