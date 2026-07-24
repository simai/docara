# Docara product homepage visual refinement

Status: completed
Track: `docara-consolidation`
Process model: `docara_documentation_site_publication`

## Current Goal

Исправить обрезанные outline-кнопки и грязные пятна Hero-иллюстрации на
главной Docara.

## Primary user outcome

Посетитель с первого просмотра понимает, что делает Docara, видит два
согласованных действия, затем быстро сканирует возможности продукта в
компактной сетке без визуально тяжёлых карточек.

## Done when

- Hero содержит две соседние CTA-кнопки на desktop и безопасно переносит их на
  узком экране.
- Вторая кнопка имеет короткую командную подпись и штатный outline-вариант.
- Feature-иконки используют размерный примитив Framework вместо произвольного
  пиксельного значения.
- Showcase документации использует новую прозрачную концептуальную
  иллюстрацию без следов от пользовательского скриншота.
- Читательский и командный блоки содержат по четыре смысловых элемента и
  раскладываются 1 / 2 / 4 по mobile / tablet / desktop.
- Production build и verify-static проходят.
- Локальный `docara.test` обновлён через backup и проверен в браузере.

## Changed surface inventory

| ID | Surface | Decision |
| --- | --- | --- |
| S1 | Hero actions | Merge into one responsive action row |
| S2 | Hero secondary label | Shorten to `Компоненты` |
| S3 | Task-card icons | Increase to one Framework size primitive |
| S4 | Documentation showcase media | Replace error screenshot with conceptual transparent asset |
| S5 | Reader benefits | Add one necessary navigation benefit and use four-column grid |
| S6 | Team benefits | Separate layout/config responsibility and use four-column grid |

## Simplicity review

The simplest complete alternative retains one explanatory Hero, two direct
actions and compact proof blocks. No new controls, disclosure layers,
configuration switches or product-specific CSS classes are introduced.
The fourth reader item removes a coverage gap around reading position; the
fourth team item separates layout configuration from build verification.

## Evidence plan

- focused renderer tests for two Hero actions and four feature items;
- full PHPUnit and formatter checks;
- production build plus static verification;
- browser screenshots at desktop and mobile widths;
- no horizontal overflow and no missing local assets.

## Result

- Hero actions are one responsive group. On desktop they sit in one row; on
  mobile they remain full-width and stack without overflow.
- The secondary action is now `Компоненты` and retains the Framework
  `on-surface + outline` variant.
- Both actions use the same Framework heights: `h-c8` on mobile and `lg:h-d0`
  on desktop. The outline border no longer adds two pixels to the rendered
  height.
- Illustrative feature icons use `var(--sf-e0)` and render as 80 × 80 px.
- The documentation showcase uses the new transparent
  `assets/docara-navigation.png` illustration.
- Reader and team sections each contain four items and use the standard
  `1 / 2 / 4` responsive grid.
- No product-specific style class was added.

## Verification

- Pint: PASS.
- Full PHPUnit: PASS, 322 tests and 4964 assertions.
- Focused renderer/builder suite after the final height correction: PASS,
  84 tests and 1013 assertions.
- Production build: PASS, 90 source pages.
- Static verification: PASS, 198 HTML pages, 10719 local references,
  zero broken references.
- Browser desktop 1600 × 1200: both CTA buttons are 40 px high, feature icons
  are 80 × 80 px, both requested grids have four equal columns, no horizontal
  overflow and no console errors.
- Browser mobile 390 × 844: both CTA buttons are 36 px high and full-width,
  no horizontal overflow and no console errors.
- Light and dark screenshots use the same transparent showcase illustration.
- Published local URL: `https://docara.test/ru/`.
- Rollback backup:
  `/Users/rim/Sites/docara.test/.docara-backups/product-homepage-visual-20260724-155744`.
- Acceptance evidence:
  `source/workflow/evidence/2026-07-24-docara-product-homepage-visual-refinement/acceptance.md`.

## Verdict

PASS for the bounded local product-homepage visual refinement. This is not a
public release or a production-readiness claim.

## Correction batch: outline borders and transparent Hero media

Status: completed

The first local acceptance exposed two visual defects at the user viewport:

- generated Framework button CSS resets the physical left and right border
  widths after applying the logical outline variables;
- the first Hero PNG contains semi-transparent grey artefacts that become
  visible against the light theme.

Done when:

- all standard `.sf-button--outline` buttons keep their four one-pixel borders
  without a CTA-specific or search-specific presentation class;
- the Hero illustration uses a real alpha channel and has no smoky background
  artefacts on light or dark surfaces;
- focused and full tests, production build, static verification and browser
  acceptance pass again;
- the local publication has a new rollback backup.

### Correction result

- Added one general compatibility rule for the official
  `.sf-button.sf-button--outline` surface. It restores the logical start and
  end border widths from the existing Framework variables; no Hero-specific
  or search-specific border class was introduced.
- Root cause remains in the pinned Framework distribution: generated physical
  left/right resets follow the logical declarations. The Docara rule is
  intentionally bounded until a new immutable Framework pair can carry the
  generator correction.
- Replaced `docara-flow.png` with a newly edited 1672 × 941 true-alpha PNG.
  Grey smoky artefacts are absent on both light and dark surfaces.
- Final asset SHA-256:
  `c7ffa0a31f9c228fa85229a227b786891aa7702f1217e68d14e8f5449165e124`.

### Correction verification

- Focused PHPUnit: PASS, 40 tests and 824 assertions.
- Full Pint: PASS.
- Full PHPUnit: PASS, 323 tests and 4968 assertions.
- Production build: PASS, 90 source pages.
- Static verification: PASS, 198 HTML pages and 10719 local references,
  zero broken references.
- Chrome 2048 × 1152: CTA and search outline buttons have 1 px top, right,
  bottom and left borders; no horizontal overflow and no console errors.
- Chrome 390 × 844: CTA has four 1 px borders, no horizontal overflow and no
  console errors.
- Light and dark screenshots:
  `output/playwright/docara-homepage-outline-image-light-final.png`,
  `output/playwright/docara-homepage-outline-image-dark-final.png`.
- Mobile screenshot:
  `output/playwright/docara-homepage-outline-image-mobile-final.png`.
- New rollback backup:
  `/Users/rim/Sites/docara.test/.docara-backups/product-homepage-outline-alpha-20260724-163102`.

### Correction verdict

PASS for the bounded local outline-border and transparent-Hero correction.
This does not claim that the pinned Framework distribution itself has already
been released with the generator fix.
