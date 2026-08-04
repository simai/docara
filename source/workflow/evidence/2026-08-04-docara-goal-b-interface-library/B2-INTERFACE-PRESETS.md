# B2 — Interface variants and presets

Date: 2026-08-04
Status: `pass`
Parent runtime checkpoint: `2c650ba` (registered replaceable chrome)

## Outcome

The accepted `docs` and `landing` page presets remain configuration data on
the same `PortableSiteBuilder` path. One `docara.navigation` continues to
provide the registered `header`, `tree` and `compact` presentations through
the same binding, Gateway and composer path. Branding, sidebar/TOC, search,
preferences and footer remain ordinary registered composition inputs rather
than a second preset renderer.

No default preset or navigation selection changed in B2. The exact frozen
Goal A default HTML parity proof was repeated during B1 after the registered
chrome migration: 104 routes, 208 HTML files, byte differences `0`, shared
HTML ledger `bd2cc9138556d7bd1a8e2ce0fba0d1472745c11b6df4649bdbb8c84dca07047c`.

## Focused proof

```text
PreviewKernelTest::navigation_presentations_keep_preview_bound_to_the_production_page
PASS — header, tree and compact are extracted from the same production page

PortableConfigurationTest
PASS — inherited docs preset and explicit landing preset

PortableInitCommandTest
PASS — initialized docs and landing pages retain their registered preset data
```

Every preview artifact reports `runtime=portable_site_builder` and keeps the
production `plan_hash`. No preset-specific parser, renderer, Smart Gateway,
LayoutComposer or PageBuilder was added.

## Rollback

B2 introduces no runtime mutation. Revert only this evidence/governance
checkpoint if its statement is found inaccurate; the accepted Goal A
navigation implementation remains the rollback boundary.
