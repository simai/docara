# Docara product homepage acceptance

Date: 2026-07-24
Branch: `codex/docara-consolidation`
Candidate: current working tree
Verdict: `PASS`

## Product result

- `/ru/` is the primary product and documentation entrypoint;
- the Hero presents one value proposition and one primary quick-start action;
- evaluator, author and developer paths are visible near the top;
- the page demonstrates the current landing blocks without a new renderer;
- documentation experience, authoring model, component system, build flow,
  verification and update boundaries are covered;
- `/ru/landing/` remains a non-indexed technical map of the used blocks;
- two new product illustrations have real alpha and work on both themes.

## Automated verification

- project doctor: PASS;
- PHPUnit: PASS, 320 tests and 4,956 assertions;
- Pint: PASS;
- production build: PASS, 90 canonical pages;
- static verifier: PASS, 198 HTML pages, 10,718 references, 0 broken;
- `git diff --check`: PASS;
- exact built/deployed tree comparison: PASS;
- HTTP `/`, `/ru/` and `/ru/landing/`: `200`.

## Browser verification

- desktop light and dark: PASS at 1,440 x 1,000;
- mobile light/dark composition: PASS at 390 x 844;
- large-monitor container alignment: PASS at 2,560 x 1,440;
- header, Hero content and ordinary landing sections share the same 104rem
  inner container while the Hero surface remains full-bleed;
- horizontal overflow: none;
- images: 10 total, 0 broken;
- console errors and warnings: 0;
- keyboard-visible links and headings are exposed in the accessibility tree.

Screenshots:

- `output/playwright/docara-product-home-final-light-desktop.png`;
- `output/playwright/docara-product-home-dark-desktop.png`;
- `output/playwright/docara-product-home-final-dark-mobile.png`;
- `output/playwright/docara-product-home-team-section.png`;
- `output/playwright/docara-product-home-final-promo.png`;
- `output/playwright/docara-container-alignment-2560.png`;
- `output/playwright/docara-container-alignment-mobile.png`.

## Publication and rollback

- local URL: `https://docara.test/ru/`;
- actual ServBay document root:
  `/Users/rim/Sites/docara.test/build_production`;
- rollback:
  `/Users/rim/Sites/docara.test/.docara-backups/product-homepage-20260724-050131`;
- the served tree is byte-identical to the accepted build.

No commit, push, merge, tag, package publication or public release was
performed.
