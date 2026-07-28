# Docara search experience alignment

Date: 2026-07-22
Status: completed
Owner: Docara
Companions: UX, Simai Framework, tester

## Primary scenario

A reader opens search from the documentation header or with `Cmd/Ctrl+K`,
types at least two characters, scans highlighted contextual matches, moves
through them with the keyboard, opens a result and can dismiss search without
losing orientation.

## Reference invariants

The Retype search at `https://retype.com/components/` is used only as a UX
reference for a dark blurred scrim, compact query row, one scrollable results
surface, contextual snippets, semantic match highlighting and predictable
keyboard dismissal. Docara keeps its own index contract, framework, copy and
visual tokens.

## Scope matrix

| Surface | Required behavior | Evidence |
|---|---|---|
| Modal | dark theme-independent scrim, close on overlay/Escape | browser + CSS/source |
| Query | size-1 compact control, search icon, clear/close command | browser + generated HTML |
| Results | one list, title/trail/excerpt hierarchy, max 20 | browser + runtime test |
| Highlight | escaped query tokens rendered as semantic `mark` | test + browser DOM |
| Keyboard | Cmd/Ctrl+K, arrows, Enter, Escape, focus restore | browser smoke |
| Security | exact index schema/hash/origin validation preserved | existing and focused tests |
| Themes | light and dark modal/results remain legible | browser screenshots |
| Responsive | no overflow at desktop and mobile widths | browser screenshots |

## State matrix

| State | Expected UI |
|---|---|
| Idle | concise minimum-query hint, empty list |
| Loading | localized loading status |
| Results | localized count and highlighted matches |
| Empty | localized empty state without decorative empty card |
| Error | localized error state using Framework error color |

## Findings register before implementation

| ID | Severity | Finding | Correction |
|---|---|---|---|
| S-001 | P1 | backdrop derives from `on-surface`, so dark theme produces a light scrim | use Framework modal default `bg-black opacity-6` overlay |
| S-002 | P2 | query has a redundant visible label/header composition | reduce to one compact accessible search row |
| S-003 | P2 | every result is a separate bordered card | use one shared results surface with dividers |
| S-004 | P1 | matched query terms are not highlighted | safe DOM-based semantic `mark` rendering |
| S-005 | P2 | close control is manually assembled with private geometry | use the native Framework `sf-icon-button` component classes and `sf-icon` Smart element |
| S-006 | P1 | Docara and the Framework loader both register the same Smart elements | keep immutable local projections, but let the canonical Framework loader own discovery and script loading |

## Readiness matrix

| Gate | Initial | Acceptance requirement |
|---|---|---|
| Source contract | PASS | 123 focused tests, 1,343 assertions |
| Exact build | PASS | 271 pages, 20,512 references, 0 broken |
| Desktop light/dark | PASS | neutral scrim, legible shared surfaces, highlighted matches |
| Mobile | PASS | 390 px viewport, 362 px panel, no horizontal overflow |
| Local publication | PASS | atomic replacement and rollback copy recorded |
| Overall batch | PASS | local search accepted; no public release claim |

## Kaizen

The recurring root causes were treating theme-dependent foreground tokens as
modal scrims, reconstructing controls that the Framework already owns and
running a second Smart loader beside the canonical Framework loader. This
batch makes component and loader ownership explicit and adds regression checks
for the scrim, semantic highlights and exact projected assets.
