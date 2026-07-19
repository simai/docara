# Batch 3 — browser, UX and design preacceptance

Status: `PASS` for the candidate-ready worktree; exact-candidate replay remains
required before Batch 3 closure.

Review modes:

- `$ux`: redesign-refactor, information architecture, keyboard and responsive
  acceptance;
- `$designer`: project-native visual hierarchy and design-system alignment;
- implementation constraint: exact pinned Simai Framework primitives and
  tokens, with no local replacement design system.

## Browser matrix

| Viewport | Result |
| --- | --- |
| 390 × 844 | One-column article, mobile menu, all five deep breadcrumb levels, stacked previous/next, zero page overflow. Brand/search/theme and menu targets are 44 px; breadcrumb links are at least 44 × 44 px. |
| 900 × 760 | Two-column `252 + 606` layout, desktop outline hidden and native mobile outline visible; search and brand are 44 px high; zero overflow. |
| 1440 × 1000 | Three-column `288 + 816 + 240` composition, sticky right outline, clear active menu trail and zero overflow. Header and sticky rails retain a 5 px safe gap after scroll. |

Additional browser assertions:

- deep path has `data-max-items="5"`, five semantic crumbs and zero generated
  Framework ellipsis nodes;
- desktop and responsive outlines contain the same fragment targets;
- Unicode fragment `#как-формируются-якоря` resolves to its real heading and
  remains below the sticky header;
- light and dark themes use coherent Framework surface/on-surface tokens;
- the theme control updates its accessible action label;
- keyboard focus has a visible 3 px outline with 3 px offset;
- console: zero errors and zero warnings;
- primary shell/navigation/touch targets meet the 44 px contract.

The exact Framework code-copy control remains intentionally unmodified. It is
an embedded secondary component with the component-level WCAG target/spacing
contract, not Docara shell chrome. A larger upstream target is optional
Framework polish, not a Batch 3 blocker.

## Design judgement

The composition follows the mature documentation pattern used by Docusaurus,
Mintlify and Retype: persistent global navigation, contextual breadcrumbs,
page-local outline, responsive progressive disclosure and document adjacency.
Those products are pattern references only; their runtimes are not Docara
dependencies.

Optional later polish: active-heading scroll tracking in the right outline and
automatic reveal of the current item inside exceptionally long mobile menus.
Neither is required for the current reading-orientation outcome.

This verdict is bounded to Batch 3 and is not release, production or Goal-wide
readiness.
