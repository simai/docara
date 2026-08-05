# B6 useful-demo semantic correction

Date: 2026-08-05
Status: `in_progress`
Rejected candidate: `e06ff0c945dafd4e9678794773d8bde83c8de535`
Correction parent/handoff: `b66e2c24e71fe9b93efbdf88ed6089d6a047c053`

## Independent RED

- `project.install-builder` rendered an immutable readonly Framework input and
  a checkbox, but its behavior listened only to a duplicate native select.
  Package/version and checkbox state did not affect the command, and no OS
  selector existed.
- `project.product-configurator` rendered a populated admitted dropdown, but
  only feature checkboxes affected the total. Selecting `Командный` changed
  the label while the base remained `2500 ₽`.
- the previous unit/browser evidence asserted presence, assets and absence of
  side effects, not the effect of every displayed control.

This is a project-owned semantic defect. Exact Framework artifacts, locks,
providers, registries, Gateway, renderer, LayoutComposer and PageBuilder remain
frozen. The correction must not add an `items` prop, raw option markup or a
local Framework dialect.

## Intended GREEN

1. install builder uses admitted text-only dropdowns for OS and method,
   admitted inputs for package/version, and admitted checkboxes for options;
2. every control changes a safe local command or its validated state, and the
   page can only copy the text;
3. product dropdown changes the allowlisted base tariff while checkboxes add
   admitted local options;
4. invalid state fails closed with an actionable local status and disabled
   copy/result acceptance;
5. permanent browser semantics plus focused/full/package/build/security
   regression is fresh on one new candidate.

Rollback: revert the bounded project-demo artifacts/tests commit to
`b66e2c2…`. Goal C, external writes, release and deploy remain forbidden.
