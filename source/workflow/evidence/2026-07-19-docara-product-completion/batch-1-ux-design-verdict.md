# Batch 1 independent UX and design verdict

Date: 2026-07-19
Reviewer: `/root/menu_ux_gate`
Scope: bounded four-level navigation correction
Verdict: **PASS**

## Confirmed outcomes

- all four levels use the pinned Framework menu level classes;
- the current page is strongest, its current section is medium and distant
  ancestors remain quiet;
- hierarchy does not depend on color: indentation, marker thickness and text
  weight contribute to the distinction;
- a collapsed active branch keeps a visible state and the accessible phrase
  `содержит текущую страницу`;
- direct links and separate disclosure buttons remain intact;
- desktop/mobile and light/dark implementations do not diverge;
- browser evidence contains no horizontal overflow or clipped active row;
- Human-Centered Simplicity passes for this bounded change because no second
  registry, separate mobile menu or new generic primitive was added.

## Non-blocking follow-up

1. Exact-candidate tester must repeat the keyboard path and mobile
   Escape/focus-return scenario.
2. The final combined visual gate must check the active trail beside search and
   the right TOC so their emphasis stays balanced.
3. Semantic depths greater than four keep the documented level-4 visual clamp
   until a pinned Framework contract adds more presentation levels.
4. This bounded verdict is not final Goal acceptance; the final exact
   candidate requires a complete-diff UX/design/HCS review.
