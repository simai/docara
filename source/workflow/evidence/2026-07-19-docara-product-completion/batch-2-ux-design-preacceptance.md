# Batch 2 — UX and design pre-acceptance

Review mode: `$ux` redesign-refactor and design-system alignment plus
`$designer` project-native review. External behavioral references were
VitePress local search, Docusaurus documentation search placement and Retype
component-documentation structure. Their implementation stacks are not Docara
dependencies.

## Decision

Search is a Docara product function built from native semantics and pinned
Simai Framework presentation primitives. A new Smart-component would add a
second behavior owner and is not justified.

## Review corrections applied

- first Escape now closes a non-empty native search field instead of only
  clearing it;
- focus order is explicitly `input -> result links -> close -> input`, with a
  valid no-results loop;
- mobile trigger and close target are both at least `44x44` CSS pixels;
- fallback snippets remove a repeated leading page title;
- result links have an explicit accessible label while visible strings remain
  separate title, trail and excerpt surfaces;
- strict responsive/light-dark styling uses Framework surfaces, outline,
  radius, spacing, color and focus tokens.

## Human-facing surface

Only five necessary surfaces are visible: header trigger, dialog, input, live
status and result list. There are no filters, recent searches, pagination,
server options or AI controls in this batch.

Final verdict is intentionally pending an independent browser recheck of the
exact committed candidate.
