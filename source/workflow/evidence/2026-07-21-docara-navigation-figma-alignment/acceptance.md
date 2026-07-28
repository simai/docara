# Docara navigation Figma alignment: verification and handoff

Date: 2026-07-21
Status: implementation and local publication PASS; independent release
acceptance not claimed

## Design and implementation mapping

Target mode: project-native PHP templates, Simai Framework Menu and
component-owned CSS/JavaScript.

Design evidence:

- SF UI Kit file `ee9qUZp4VhVpDxeMqxWtcv`;
- Simple Menu Item node `17583:25972`;
- assembled Simple Menu node `17607:34059`;
- structured design context, screenshots and variable definitions obtained;
- Code Connect unavailable for the current Figma seat and therefore not used.

Mapping:

| Figma contract | Docara implementation |
| --- | --- |
| Menu Item / Simple Menu | existing `sf-menu`, `sf-menu-item`, `sf-menu-element` |
| 4 levels | Framework levels 1-4 and tokens `space-1`, `space-4`, `space-6`, `space-7` |
| disclosure on the left | existing `sf-icon-button` moved before the label |
| closed/open glyph | `chevron_right` / `keyboard_arrow_down` |
| active state | `surface-container-active`, medium weight, 8 px radius from Framework |
| hover/default/theme | inherited Framework tokens and component states |
| fixed 360 px example | responsive width of the Docara `sidebar` region |

## Changed-surface inventory and necessity map

| ID | Surface | Needed for | Decision |
| --- | --- | --- | --- |
| NAV-01 | recursive menu item template | Figma ordering and semantic tree | retained and simplified; no new primitive |
| NAV-02 | navigation CSS | token mapping, density, active and focus states | retained in component-owned asset |
| NAV-03 | navigation JavaScript | one disclosure state and event | retained; keyboard double-toggle removed |
| NAV-04 | renderer weight mapping | active item typography | changed from bold hierarchy to Figma medium active only |
| NAV-05 | Smart component documentation | discoverable customization contract | added to existing guide |
| NAV-06 | regression assertions | protect ordering, tokens and keyboard guard | added to existing portable test |
| NAV-07 | sidebar/menu spacing | match the Figma rhythm through Framework tokens | raw pixel values replaced by `p-1`, `d2`, `d1`, `d0`, `c6`, `space-1/3` and `b0` |

Simplest complete alternative: reuse the current Framework Menu structure and
the existing `tree` view. No new Smart ID, preset, template family, JavaScript
controller, fixed-width wrapper, page CSS or Markdown field was added.

Protective complexity retained: current-page `aria-current`, active ancestors,
four-level fail-closed limit, direct section links, visible focus, 44 px
disclosure hit area, pointer and keyboard behavior, component event, mobile
sheet and theme inheritance.

Visual simplicity status: PASS for the changed surface. The menu removes the
old primary-colored active treatment and excess top-level gaps, while every
remaining element maps to navigation, disclosure or accessibility.

## Verification

- PHP syntax: PASS.
- Focused Pint on changed PHP/template files: PASS.
- Focused navigation/declarative suite: 41 tests, 708 assertions, PASS.
- Portable builder after spacing regression additions: 35 tests, 670 assertions,
  PASS.
- Full PHPUnit after spacing refinement: 618 tests, 5,447 assertions, PASS.
- Deterministic production build: 271 HTML pages, 20,569 local references,
  zero broken references.
- Full-tree `git diff --check`: PASS.
- Full-tree Pint remains non-green on pre-existing unchanged formatting in
  eight committed files; none belongs to this change. The changed PHP surface
  passes Pint.

Browser evidence on the exact local served build:

- desktop route:
  `https://docara.test/ru/authoring/layout-and-navigation/hierarchy/four-level/`;
- `tree` view, active page `Четыре уровня навигации`;
- desktop indentation: 16 / 32 / 48 / 64 px;
- active background: `rgba(118, 119, 124, 0.32)` in dark and
  `rgba(118, 119, 124, 0.20)` in light;
- open icons: `keyboard_arrow_down`; closed icon: `chevron_right`;
- 390 x 844 mobile sheet: four-level active item visible, no horizontal page
  overflow;
- pointer collapse/expand: PASS;
- keyboard `Enter` collapse/expand: PASS after preventing the old double
  handler path;
- system theme was restored after light-theme verification.

Follow-up spacing evidence on
`https://docara.test/ru/authoring/configuration/`:

- desktop sidebar padding: 16 px on every side (`p-1` / `space-1`);
- desktop menu rows: 48 px (`d2`), including expandable rows;
- desktop disclosure target: 44 x 44 px (`d1`);
- no horizontal overflow;
- 390 x 844 mobile menu: tokens resolve through the Framework 14 px root
  scale to 42 px rows and 38.5 px disclosure targets; no raw mobile sizes and
  no horizontal overflow;
- mobile section disclosure changes `aria-expanded` and its accessible label:
  PASS;
- browser console contains one pre-existing Framework CDN error from
  `core/js/556.js` (`Cannot set properties of null (setting 'data')`); the
  navigation interaction remains functional and this spacing batch does not
  change that asset.

UX verdict: PASS for scanability, active-page recognition, responsive
navigation and keyboard disclosure. Designer parity verdict: PASS with one
intentional adaptation — Figma's demonstration width is not hard-coded.

## Local publication and rollback

Action gate: PASS. The candidate was copied into a same-site staging
directory, statically verified, content-compared and atomically swapped.

- served digest:
  `eae8d26289e7be5c1dc49bfdf037c4f2b1e5b52ef2f99e7a7692e05f5c9e53f5`;
- rollback backup:
  `/Users/rim/Sites/docara.test/.docara-backups/figma-navigation-20260721-205659/build_production`;
- spacing-refinement rollback backup:
  `/Users/rim/Sites/docara.test/.docara-backups/navigation-spacing-20260721-230417/build_production`;
- served HTTP 200: four-level example, Smart guide, navigation CSS and
  navigation JavaScript;
- source/staging/served digest equality: PASS.

The first staging attempt stopped before the live swap because its digest
included different absolute roots. The live site remained untouched. The
successful retry compared relative paths plus file contents.

No public push, merge, tag, package release or production-readiness claim was
made. A separate independent tester verdict is still required only if this
change is promoted from local verification to a release candidate.
