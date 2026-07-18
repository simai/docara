# Batch 0 current menu reproduction

Date: 2026-07-19
Status: reproduced before implementation

## Exact source baseline

- commit: `31f468be85d015b962fccc2b4c089204aab1410b`;
- tree: `43d5e0f7d90d39bcc95164f247bdbca0e9e962dc`;
- accepted implementation parent: `83d677c7eb5f22d9ca2f4ac16990fe16eddbe985`;
- branch: `codex/docara-consolidation`;
- served page: `https://docara.test/authoring/layout-and-navigation/hierarchy/four-level/`.

The source worktree contains only the new Goal workflow, launch record, project
memory and evidence changes at reproduction time. Product implementation files
are unchanged from the accepted baseline.

## Semantic behavior that already works

- the desktop DOM contains nested lists through depth four;
- the current page has `aria-current="page"`;
- all three ancestors are expanded and use real disclosure buttons;
- section overview labels remain direct links;
- the active page is visible in the scroll rail.

These behaviors are preservation requirements, not defects to replace.

## Reproduced visual defects

At the current desktop viewport the four active-path links begin at x positions
`62`, `70`, `78` and `86` pixels. Every level therefore differs by only eight
pixels. The hierarchy is technically present but too weak to scan reliably.

Computed presentation on the active path:

| Depth | Page/section | Background on link | Weight |
| --- | --- | --- | --- |
| 1 | Содержание и макет | transparent | 400 |
| 2 | Макеты и навигация | transparent | 400 |
| 3 | Иерархия разделов | transparent | 400 |
| 4 | Четыре уровня навигации | transparent | 600 |

The Framework menu marks every open ancestor through the surrounding open
element, but those ancestors receive the same undifferentiated grey surface.
The current page is distinguished primarily by bold text. There is no active
rail/marker, no explicit ancestor-path affordance and no collapsed-branch
signal that its hidden subtree contains the current page.

## User jobs that fail

1. A reader cannot rapidly reconstruct which section contains which child.
2. A reader cannot distinguish the current section from more distant active
   ancestors.
3. After collapsing an active ancestor, the reader loses the visible current
   page without a clear indication that it remains inside that branch.

## Initial acceptance boundary

The correction must preserve native links, nested lists, disclosure buttons,
`aria-current`, automatic opening and scroll reveal while adding hierarchy that
does not depend on color alone. It must be accepted in desktop and mobile,
light and dark, expanded and collapsed active-path states, with keyboard focus
and no horizontal page overflow.
