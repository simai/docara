# Docara Example: stable tabs prototype

Date: 2026-07-29
Status: complete
Workflow ID: `2026-07-29-docara-example-stable-tabs-prototype`
Track: docara-consolidation

## Goal

Before another full documentation build, validate the shared Example/Source
component in an isolated page using the current SIMAI Framework assets.

## Required behaviour

- header padding is symmetrical: `space-1/3` above and below size `1` controls;
- Example and source states keep one stable component height;
- one shared active indicator glides between tabs instead of disappearing and
  reappearing on individual buttons;
- the indicator sits on the header divider;
- the copy action keeps size `1` and the same logical edge inset as the first
  tab;
- light and dark themes use the same geometry.

## Batches

1. Build and browser-check the focused standalone prototype — complete.
2. Collect visual review from the owner — accepted.
3. Keep the shared moving indicator as a narrow product-owned Docara
   composition while all dimensions, colours and controls come from SIMAI
   Framework — complete.
4. Integrate the accepted result, rebuild and verify Docara — complete.

## Evidence

- prototype: `source/workflow/prototypes/docara-example-component-preview.html`;
- Framework source review: the current underline preset owns an underline per
  button and has no shared moving indicator;
- root cause of the stretched header: the prototype used the nonexistent
  `--sf-size-1`, which invalidated the two-column grid declaration; the header
  now uses the Framework control token `--sf-ui-1--control-height`;
- root cause of the missing copy glyph: Core hides `.sf-icon` until the loader
  marks it as `.sf-icon-loaded`; the standalone file has no loader, therefore
  the prototype declares this state explicitly and uses the local Framework
  Material Symbols font;
- the copy action now uses the official `sf-icon-button--size-1` contract
  instead of a fixed `sf-icon--size-3`; its glyph and control therefore follow
  the same adaptive scale as the tab text;
- both tabs use the same symmetrical `space-1` inline padding; there is no
  special zero-padding rule for the first tab;
- the active indicator is recalculated after Framework fonts are ready, so its
  initial width stays equal to the rendered tab width;
- headless Chrome measurement, light/dark-capable prototype:
  - below `960px`: tab text `14px`, copy glyph `20px`, copy control `36px`;
  - from `960px`: tab text `16px`, copy glyph `24px`, copy control `40px`;
  - at `960px`, both tabs have `16px` left and right padding;
  - the copy glyph is visible only for source;
  - indicator: `2px`, bottom edge exactly equals the header divider;
  - browser console/page errors: `0`;
- static verification: HTML parse, JavaScript syntax, token contract and
  `git diff --check` pass.
- implementation: `src/PortableSite/PortableExampleRenderer.php`,
  `resources/portable/declarative-shell.css` and
  `resources/portable/declarative-shell.js`;
- focused PHPUnit: `53 tests, 267 assertions`;
- full PHPUnit: `336 tests, 6701 assertions`;
- two production builds: byte-identical;
- static build verification: `200` HTML pages, `17730` local references,
  `0` broken;
- served browser acceptance:
  - desktop: tab text `16px`, copy control `40x40`, glyph `24x24`;
  - mobile: tab text `14px`, copy control `36x36`, glyph `20x20`;
  - selected tab and indicator left/right edges are equal in both states;
  - Arrow Left returns from source to Example;
  - JavaScript console/page errors: `0`;
- detailed evidence:
  `source/workflow/evidence/2026-07-29-docara-example-stable-tabs-prototype/verification.md`.

## Routing note

The central federation resolver classified this focused Docara/SIMAI Framework
prototype as a Bitrix release task. That route is not applicable here, so the
batch follows the raw `dev` + `sf5` + `ux` owner sources and records this as a
federation graph gap.

## Boundary

The verified tree is published only to the local test site
`https://docara.test/`. No merge, tag, package publication, public deployment
or production-readiness claim is part of this batch.
