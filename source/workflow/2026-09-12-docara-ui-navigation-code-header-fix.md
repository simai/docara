# Docara UI navigation and code-header fix

Date: 2026-09-12
Status: complete — local acceptance PASS
Branch: `codex/ui-doc-navigation-code-header-fix`
Baseline: `e2ff8cb62cbb855cf07e40981a1f8ef3f72bebb1`

## Outcome and Done When

A reader can expand a documentation section from a trailing Framework icon
button after its title, and code-block titles/actions share the exact header
rhythm of `Example`. The canonical source must propagate to a newly initialized
portable site. Acceptance covers keyboard toggle state, LTR and RTL navigation,
desktop and mobile viewports, no horizontal overflow, and a clean browser
console. `ui-doc.test`, dirty `main`, deployment, push, merge, tag and release
are outside this batch.

## Batch 1 — canonical implementation and regression guards

- [x] Isolated a clean worktree from the recorded baseline.
- [x] Located the regression: Docara's `order:-1` contradicted the requested
  trailing disclosure control.
- [x] Reused the existing `docara.navigation` Framework icon button and its
  event/ARIA runtime; no second navigation component or custom control added.
- [x] Moved the existing disclosure after the title in the canonical template
  and removed the local order override.
- [x] Extracted one shared grid/rhythm rule for code and Example headers.
- [x] Removed the inherited `16px` top margin from the code-action container;
  browser measurement identified it as the direct source of the uneven header.
- [x] Avoided a pinned Framework icon-runtime error during disclosure-icon
  changes by replacing the existing Framework icon node instead of mutating its
  live `icon` attribute.
- [x] Added source and rendered-markup regression checks.

## Batch 2 — disposable-site and browser acceptance

- [x] Run focused regression tests: `FrameworkNativeSurfaceTest` (8 tests, 77
  assertions) and the rendered portable-site scenario (1 test, 670 assertions).
- [x] Build a freshly initialized disposable site and static verification:
  39 pages, 78 HTML files, 4,725 local references and 0 broken references.
- [x] Measure desktop `1440` and mobile `390` in LTR and RTL: title/control
  order, keyboard Enter/Space toggle, expanded state, no overflow, matching
  code/Example header geometry and console.
- [x] Record final evidence and acceptance verdict below.

## Simplicity review

| Item | Why it remains | Decision |
| --- | --- | --- |
| Existing disclosure button and navigation runtime | Provides semantic button, keyboard activation and synchronised `aria-expanded` state. | Retain and reposition. |
| `trailing-icon` item state | Keeps the Framework menu state in sync with the existing component contract. | Retain. |
| `order:-1` override | Forces the control before the title and causes the reported regression. | Remove. |
| Separate code-header geometry | Duplicated the Example geometry and could drift. | Merge into one shared rule. |

The simplest complete option is to reorder the existing semantic control and
reuse the existing Example header grid. A new menu, custom icon geometry,
absolute positioning, or a second code-header component are rejected as extra
surface. Residual complexity is the existing ARIA/keyboard runtime; it is
protective accessibility behavior and will be covered by browser checks.

## Next

Run the focused tests, then initialize and inspect a disposable project before
any handoff. No local `ui-doc.test` mutation is permitted in this workflow.

## Acceptance evidence and verdict

- Desktop LTR (`1440px`): navigation direct-child order is `A, BUTTON`; the
  title begins at `x=64` and the trailing button at `x=279`. `Enter` changed
  the button and parent item from `aria-expanded=false` to `true`, and the icon
  from `chevron_right` to `keyboard_arrow_down`. There was no horizontal
  overflow and no console error from the final build.
- Desktop code page: code and Example headers both measure `57px`; the action
  container margin is `0px`.
- Mobile RTL (`390px`): `dir=rtl`, direct-child order remains `A, BUTTON`;
  code and Example headers both measure `53px`; no horizontal overflow.
  `Space` changes the fresh disclosure node, parent item and icon to expanded,
  `true`, and `keyboard_arrow_down`, respectively. The final-build console is
  free of errors.
- No `ui-doc.test` file, URL, server configuration, deployment target, branch
  or dirty `main` file was changed.

Verdict: **PASS** for this bounded local correction. The combined broad
`PortableSiteBuilderTest` run was stopped after it exceeded five minutes;
the affected rendered scenario was then run independently and passed. This is
not a release or production readiness verdict.
