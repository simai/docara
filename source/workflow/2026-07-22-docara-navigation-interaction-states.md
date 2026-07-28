# Workflow: Docara navigation interaction states

Date: 2026-07-22
Status: completed — accepted with upstream note
Baseline: `8036a68`
Primary owner: `docara`
Companions: `sf5`, `ux`, `tester`, `ops`

## Scope

Correct the interaction states of the product-owned `docara.navigation` Smart
component without changing navigation hierarchy, indentation or URL behavior.

## Scenario

A reader points at a collapsible navigation row, expands or collapses it with
the pointer, then repeats the operation with keyboard focus. The row remains a
coherent visual object, the two actions remain semantic (`button` toggles,
`a` navigates), and the current page remains identifiable.

## QA plan and scope matrix

| Surface | Baseline finding | Required result |
| --- | --- | --- |
| row hover | only disclosure tile receives visible hover | whole menu row receives Framework hover surface |
| icon hover | icon loses contrast | icon retains or increases contrast |
| pointer activation | persistent focus ring reported | no keyboard-style ring after pointer click |
| keyboard focus | accessible focus must remain | ring visible on disclosure with Tab/keyboard |
| toggle state | already operational | submenu and `aria-expanded` stay synchronized |
| active page | already operational | active page survives collapse/expand |
| responsive | desktop and mobile | no overlap or horizontal overflow |

## Role/access matrix

- Pointer: hover row, toggle disclosure, follow label link.
- Keyboard: Tab to disclosure, Enter/Space toggle, visible focus indication.
- Assistive technology: accessible button label and `aria-expanded` reflect
  the current state.

## Findings register

- `P2`: Docara's unconditional menu-element variables override the Framework
  hover variables, fragmenting the row into a separately highlighted tile.
- `P2`: the disclosure color bridge keeps the low-emphasis icon token during
  hover instead of projecting the row's interactive color.
- `P2`: pointer and keyboard focus presentation require browser verification;
  focus indication must not be removed globally.

## Readiness matrix

- source contract and regression guard: PASS;
- focused PHPUnit: PASS — 5 tests, 525 assertions;
- full PHPUnit: PASS — 623 tests, 5557 assertions;
- exact build and static verification: PASS — 271 HTML pages, 20,512 local
  references, 0 broken;
- desktop pointer and keyboard browser acceptance: PASS;
- mobile browser acceptance at `390 x 844`: PASS;
- local publication backup and rollback: PASS;
- known pinned Framework runtime console error: pre-existing, recorded as an
  upstream note and excluded from this bounded correction.

## Exclusions

- no Framework source release or revision change;
- no menu hierarchy, indentation, typography or content changes;
- no public push, merge, tag, package release or production-readiness claim.
