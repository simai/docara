# Docara full-bleed landing acceptance

Date: 2026-07-24
Branch: `codex/docara-consolidation`
Candidate: current working tree

## Problem

The landing main-region wrapper received the ordinary-content gutter. Registered
`data-docara-width="full"` blocks were nested inside that wrapper, so the Hero
was inset by 32 px on both sides instead of reaching the viewport edges.

## Correction

- the declarative main-region wrapper now has `width: 100%`, `max-width: none`,
  zero margin and zero padding;
- the bounded width and Framework gutter apply to ordinary children inside the
  region wrapper;
- registered full-width blocks bypass the bounded-content rule;
- the inner `data-docara-container` still owns readable content padding.

## Automated verification

- focused PHPUnit: PASS, 35 tests and 839 assertions;
- full PHPUnit: PASS, 319 tests and 4561 assertions;
- Pint: PASS;
- Composer strict validation: PASS;
- `git diff --check`: PASS;
- production build: PASS, 90 canonical pages;
- static verifier: PASS, 198 HTML pages, 10,908 local references, 0 broken;
- source/deployed tree comparison: PASS.

## Browser verification

Desktop:

- viewport content width: `1282 px`;
- Hero: `x=0`, `width=1282 px`, right edge `1282`;
- main-region padding: `0 px`;
- inner Hero padding: `32 px`;
- horizontal overflow: `0`.

Mobile:

- viewport content width: `378 px`;
- Hero: `x=0`, `width=378 px`, right edge `378`;
- inner Hero padding: Framework mobile spacing (`14 px` in the verified
  runtime);
- horizontal overflow: `0`;
- both actions remain visible and usable.

Browser errors and warnings: none.

## Publication and rollback

- action gate: PASS;
- gate evidence:
  `source/output/action-gates/action-gate-report-20260723212815.json`;
- local URL: `https://docara.test/ru/landing/`;
- rollback backup:
  `/Users/rim/Sites/docara.test/.docara-backups/full-bleed-20260724-002827`;
- manifest SHA-256:
  `3bb773e7edf0907761afab1eebb88ba69b1918398924c1fe9831c367d8c2a92f`.

## UX verdict

`PASS`.

- Scope: landing Hero outer geometry.
- Scenario: a reader opens the product landing on desktop or mobile.
- Evidence: computed browser bounds, desktop/mobile screenshots and automated
  verification above.
- Not checked: public deployment and unrelated documentation layouts.
- Risks: none inside this bounded correction.
- Next: no additional work is required for the reported gutter defect.

No commit, push, merge, tag, package publication or public/production release
was performed.
