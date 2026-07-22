# Workflow: Docara scrollbar divider alignment

Date: 2026-07-22
Status: completed — accepted locally
Baseline: `c2882ecfc2d7ef505df0b0d791bb57a1b739045b`
Primary owner: `docara`
Companions: `sf5`, `ux`, `tester`, `ops`

## Primary user outcome

Desktop readers see both navigation scrollbars attached to their respective
column dividers through the Framework `b2` spacing token, while menu and
outline content retain their normal internal padding.

## QA plan and scope matrix

| Surface | Baseline defect | Required result |
| --- | --- | --- |
| left navigation | outer `p-1` moves the scroll container away from the right divider | scrollbar is `var(--sf-b2)` from the divider |
| right contents | outer `p-2` moves the scroll container inward and the native LTR scrollbar stays at the far edge | scrollbar is `var(--sf-b2)` from the inner divider |
| content spacing | rail padding currently provides all breathing room | equivalent Framework spacing moves inside the scroll container |
| directionality | right-rail scrollbar side is implicit | LTR and RTL keep the scrollbar at `border-inline-start` and preserve content direction |
| responsive | desktop rails disappear at existing breakpoints | mobile behavior remains unchanged |

## Role and access matrix

- Desktop reader: can scan and independently scroll long navigation and page contents.
- Keyboard reader: navigation links and outline anchors retain focus and semantics.
- RTL reader: text direction stays native while the scrollbar follows the logical divider.

## Findings register

- `P2`: rail padding is applied to the scroll container's parent and creates a large visual gap.
- `P2`: the right outline uses the browser's default scrollbar side, which is opposite its inner divider in LTR.

## Readiness matrix

- source regression guards: PASS;
- focused PHPUnit: PASS — 37 tests, 809 assertions;
- full PHPUnit: PASS — 623 tests, 5,565 assertions;
- exact documentation build: PASS;
- static verification: PASS — 271 HTML pages, 20,512 local references, 0 broken;
- desktop browser geometry and visual acceptance: PASS — `13 px` on both rails (`12 px b2 + 1 px divider`);
- visible overflow acceptance: PASS — left navigation on `/ru/migration/`, right contents on `/ru/examples/`;
- mobile regression at `390 x 844`: PASS — rails hidden, horizontal overflow `0`;
- browser console errors: none;
- local publication backup and rollback: PASS.

## Exclusions

- no navigation hierarchy, copy, URLs, component behavior or locale changes;
- no Framework source or revision change;
- no public push, merge, tag, release or production-readiness claim.
