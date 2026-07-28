# Batch 1 navigation implementation evidence

Date: 2026-07-19
Baseline: `31f468be85d015b962fccc2b4c089204aab1410b`
Candidate: pending bounded commit
Status: implementation, root browser and independent UX/design passed; exact-archive tester pending

## Implemented contract

- every rendered menu row now uses the pinned Simai Framework presentation
  class `sf-menu-element--level-{1..4}`;
- semantic depths greater than four remain in the navigation model and clamp
  only their visual Framework class to level 4;
- the active trail has deterministic roles: `page`, `section` and `ancestor`;
- only the active page receives `aria-current="page"`;
- the direct parent receives the section state and more distant parents retain
  the quieter ancestor state;
- active-trail disclosure buttons expose
  `data-docara-contains-current="true"` and say that the branch contains the
  current page in their accessible name;
- active-row reveal waits for fonts and the pinned `sf-icon` custom element,
  then performs a two-frame geometry stabilization before scrolling the rail.

The implementation uses the pinned Framework menu component classes, theme
tokens and component custom properties. It does not introduce a replacement
menu, a second navigation registry or an unpublished Framework asset.

## Test-first evidence

Before implementation, the focused test failed on the missing level-1
Framework class. After implementation:

```text
PortableSiteBuilderTest focused navigation scenario
PASS: 1 test, 144 assertions
```

Full suite with network enabled for the repository's pre-existing remote
collection fixtures:

```text
PHPUnit 11.5.56, PHP 8.2.29
PASS: 432 tests, 2047 assertions
```

The first sandboxed full-suite run produced three errors because existing
snapshot fixtures call `jsonplaceholder.typicode.com`; no navigation assertion
failed. The same unchanged candidate passed after the test's required network
transport was enabled. Formatting gate:

```text
Pint --test: PASS
git diff --check: PASS
```

## Deterministic build evidence

The bundled documentation was rebuilt through the portable production path.
The existing static verifier reported:

```text
schema: docara.static_build_verification.v1
html_pages: 41
local_references_checked: 3574
broken: []
```

## Root browser matrix

Preview:
`http://127.0.0.1:8899/authoring/layout-and-navigation/hierarchy/four-level/`

| Scenario | Result |
| --- | --- |
| desktop, light | four visibly indented levels; page, section and ancestors distinguishable |
| desktop, dark | active trail remains readable with Framework theme tokens |
| mobile, 390 x 844 | active page visible inside opened navigation; no horizontal page overflow |
| active reveal | rail scrolls to keep the active row inside its viewport after icon/font layout |
| collapsed current section | active page hides; section row and marker remain; disclosure announces contained current page |
| semantics | roles are `ancestor`, `ancestor`, `section`, `page`; only page has `aria-current` |

Measured mobile state after opening navigation:

```text
active top: 456.5
active bottom: 497
navigation panel bottom: 613.5
horizontal page overflow: 0
```

Measured desktop active reveal:

```text
rail scrollTop: 51
active bottom: 638
rail bottom: 646
horizontal page overflow: 0
```

## Remaining gates

- bounded exact-archive tester verdict after the candidate is committed;
- safe staging and local publication only after those gates pass.

This is acceptance evidence for Batch 1 only. It does not claim search, TOC,
breadcrumbs, previous/next, reading settings, landing, catalogue or Goal
completion.
