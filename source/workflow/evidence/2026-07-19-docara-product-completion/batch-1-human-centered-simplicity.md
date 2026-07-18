# Batch 1 Human-Centered Simplicity review

Date: 2026-07-19
Baseline: `31f468be85d015b962fccc2b4c089204aab1410b`
Scope: complete Batch 0 and Batch 1 working diff
Verdict: PASS for bounded candidate; final Goal HCS remains pending

## Primary human outcome

A reader should understand the four-level page hierarchy and current path at a
glance, without learning Docara internals and without depending on color.

## Complete changed-surface inventory

| Surface | Why it exists | Simplicity judgement |
| --- | --- | --- |
| `PortableNavigationBuilder` | derives one direct current section from the already resolved tree | keeps state preparation out of the template; no second model |
| `PortableHtmlRenderer` menu markup | applies Framework depth classes and three active-trail roles | uses the existing component contract; roles map to reader concepts |
| `PortableHtmlRenderer` controller | preserves accessible disclosure wording and stabilizes active reveal | bounded protective complexity for reliable orientation |
| `PortableHtmlRenderer` shell style | maps roles to Framework tokens/properties and structural markers | no new generic design primitive; works without color alone |
| `PortableSiteBuilderTest` | locks level classes, roles, `aria-current` and disclosure evidence | protects the reader-facing contract against regression |
| Goal workflow and launch record | prevents one menu fix from being mistaken for product completion | necessary delivery control for the confirmed broad Goal |
| Framework/capability/navigation evidence | records exact source decisions and product boundaries | prevents duplicate primitives and unsupported catalogue claims |
| project memory continuation files | lets the long Goal continue from a durable state | operational, not product-facing complexity |

## Removed or avoided complexity

- no second desktop/mobile navigation model;
- no hand-authored active path;
- no replacement for the Framework menu;
- no color-only state;
- no moving Framework revision;
- no automatic forced re-opening after a reader intentionally collapses an
  active branch;
- no search, TOC or settings logic bundled into this bounded correction.

## Protective complexity retained

- explicit page/section/ancestor roles;
- native link and disclosure-button semantics;
- accessible disclosure wording for collapsed active branches;
- delayed geometry measurement after font/custom-element layout;
- automated semantic and browser regression coverage.

## Review result

The bounded implementation is the smallest coherent correction that restores
both hierarchy and current-path orientation while reusing the pinned Simai
Framework. No simplification opportunity justifies removing a state, marker,
semantic attribute or geometry guard.

The final Goal acceptance must repeat Human-Centered Simplicity against every
file in the exact baseline-to-final-candidate diff. This bounded PASS cannot be
reused as final product acceptance.
