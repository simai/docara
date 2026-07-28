# Docara transparent media and SIMAI spelling acceptance

Date: 2026-07-24
Branch: `codex/docara-consolidation`
Candidate: current working tree
Verdict: `PASS`

## Product result

- `docara-flow.png` and the three landing feature illustrations contain a real
  alpha channel;
- no checkerboard or theme-specific rectangular background is baked into the
  accepted files;
- Hero and Promo media use `object-fit: contain`;
- the product screenshot remains rectangular and uses `object-fit: cover`;
- maintained product, starter, documentation and catalog surfaces use the
  canonical `SIMAI Framework` spelling;
- PHP namespace `Simai\Docara`, historical evidence and the hash-protected
  upstream Framework manifest remain unchanged.

## Asset evidence

| Asset | Dimensions | Alpha | SHA-256 |
| --- | ---: | --- | --- |
| `docara-flow.png` | 1672 x 941 | yes | `03996793c8739349f85ab254dfdc5883ed6242382867b086153d491eb42bacf2` |
| `feature-markdown.png` | 240 x 240 | yes | `ab83a4440cd0342045881a9190cc8a6a0fe9c70b2200bb0476acee8047a48124` |
| `feature-json.png` | 240 x 240 | yes | `8bc203a44c69b172aad902985e254c321c4159c7ff35a393d91173a9ff69b327` |
| `feature-build.png` | 240 x 240 | yes | `97881fd1b4416f533f92688969e3ba0885d36f96018cfe12b512f834a698ca32` |

Active-surface search for the noncanonical spelling, excluding immutable
upstream manifests and generated builds: `0` files.

## Automated verification

- focused PHPUnit: PASS, 22 tests and 1,699 assertions;
- full PHPUnit: PASS, 320 tests and 4,962 assertions;
- Pint: PASS;
- production build: PASS, 90 canonical pages;
- static verifier: PASS, 198 HTML pages, 10,908 local references, 0 broken;
- source/deployed tree comparison: PASS;
- `git diff --check`: PASS;
- landing and Hero HTTP checks: `200`.

The PHPUnit contract
`product_surfaces_use_the_canonical_simai_brand_spelling` prevents the legacy
brand spelling from returning to maintained surfaces.

## Browser verification

Desktop light:

- theme: `theme-light`;
- body surface: `rgb(255, 255, 255)`;
- Hero natural size: `1672 x 941`;
- Hero rendered with `object-fit: contain`;
- transparent Hero and feature illustrations have no rectangular background.

Desktop dark:

- theme: `theme-dark`;
- body surface: `rgb(15, 17, 21)`;
- transparent illustrations blend with the dark Framework surfaces;
- broken images: `0`;
- browser console errors and warnings: `0`.

Mobile light:

- viewport: `390 x 844`;
- Hero, feature cards, two scenario illustrations and Promo remain visible;
- actions stack correctly;
- no horizontal clipping or theme-specific image plate is visible.

Screenshots:

- `output/playwright/docara-landing-light-desktop-clean.png`;
- `output/playwright/docara-landing-dark-desktop.png`;
- `output/playwright/docara-landing-light-mobile.png`.

## Publication and rollback

- action gate: PASS;
- action-gate evidence:
  `source/output/action-gates/action-gate-report-20260723223123.json`;
- local URL: `https://docara.test/ru/landing/`;
- actual ServBay document root:
  `/Users/rim/Sites/docara.test/build_production`;
- rollback backup:
  `/Users/rim/Sites/docara.test/.docara-backups/transparent-media-20260724-013133`;
- exact source/deployed tree comparison: PASS.

The first publication attempt targeted the parent site directory. Browser
preflight exposed the resulting 404 because ServBay serves the nested
`build_production` directory. The same verified tree was then published to the
actual document root and rechecked with HTTP, tree comparison and browser
acceptance.

No commit, push, merge, tag, package publication or public/production release
was performed.
