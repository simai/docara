# Docara product homepage visual refinement — acceptance

Date: 2026-07-24
Verdict: PASS
Scope: local product homepage at `https://docara.test/ru/`

## Accepted outcomes

1. Hero CTA labels are `Создать первый сайт` and `Компоненты`.
2. Desktop CTA layout is horizontal; mobile layout is safely stacked.
3. Framework height utilities equalize default and outline buttons:
   40 px desktop, 36 px mobile.
4. Feature icons use the Framework size token `--sf-e0` and render at
   80 × 80 px.
5. The documentation showcase no longer contains the supplied error
   screenshot. It uses a purpose-made transparent conceptual illustration.
6. `Всё важное для читателя` and `Всё важное для команды` contain four items
   and render as four columns on desktop.

## Automated evidence

- `vendor/bin/pint --test`: PASS.
- `vendor/bin/phpunit`: PASS, 322 tests, 4964 assertions.
- focused renderer/builder suite: PASS, 84 tests, 1013 assertions.
- `docara build production`: PASS, 90 source pages.
- `docara verify-static build_production`: PASS, 198 HTML pages,
  10719 local references, no broken references.
- source and served navigation assets have the same SHA-256:
  `c38ff3bb23d94c48c5c3873e7c990d809958629b1e8c5449daeffc1464e9a112`.

## Browser evidence

- desktop screenshot:
  `output/playwright/docara-homepage-desktop-final.png`;
- dark-theme screenshot:
  `output/playwright/docara-homepage-dark-final.png`;
- mobile screenshot:
  `output/playwright/docara-homepage-mobile-final.png`;
- viewport checks: 1600 × 1200 and 390 × 844;
- horizontal overflow: 0 at both widths;
- console errors: 0.

## Publication and rollback

- served directory:
  `/Users/rim/Sites/docara.test/build_production`;
- rollback backup:
  `/Users/rim/Sites/docara.test/.docara-backups/product-homepage-visual-20260724-155744`;
- local HTTP checks: `/` = 200, `/ru/` = 200.

No commit, merge, tag, package publication, public deployment or
production-readiness claim is included in this acceptance.

## Correction acceptance: outline borders and Hero alpha

- Verdict: PASS.
- General Framework compatibility rule restores logical start/end borders for
  `.sf-button.sf-button--outline`; no CTA-only or search-only border rule.
- CTA and search buttons render 1 px on all four sides at 2048 px.
- CTA renders 1 px on all four sides at 390 px.
- Hero media is a 1672 × 941 true-alpha PNG with SHA-256
  `c7ffa0a31f9c228fa85229a227b786891aa7702f1217e68d14e8f5449165e124`.
- Full Pint: PASS.
- Full PHPUnit: PASS, 323 tests and 4968 assertions.
- Production build: PASS, 90 source pages.
- Static verification: PASS, 198 HTML pages and 10719 local references.
- Browser overflow: 0; console errors: 0.
- Light:
  `output/playwright/docara-homepage-outline-image-light-final.png`.
- Dark:
  `output/playwright/docara-homepage-outline-image-dark-final.png`.
- Mobile:
  `output/playwright/docara-homepage-outline-image-mobile-final.png`.
- Rollback:
  `/Users/rim/Sites/docara.test/.docara-backups/product-homepage-outline-alpha-20260724-163102`.
