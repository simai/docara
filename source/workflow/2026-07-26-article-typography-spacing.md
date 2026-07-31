# Article typography spacing

Date: 2026-07-26
Status: completed

## Goal

Use the native SIMAI Framework typography rhythm in Docara articles without a
second layout gap between every Markdown node.

## Done When

- `section.docara.article` does not impose `flex`, `flex-col`, or `gap-*`;
- paragraphs, lists, headings, code blocks, and Smart-components retain their
  own Framework/component spacing;
- structural grids and composed blocks keep their explicit `gap-*` utilities;
- the production build and browser evidence show no artificial flex gap.

## Scope

- `resources/views/section.docara.article.json`;
- the narrow declarative rendering regression;
- local `docara.test` build and visual verification.

## Non-goals

- changing gap utilities inside grids, cards, menus, toolbars, or other
  composition components;
- changing SIMAI Framework typography tokens;
- changing landing page section-to-section composition.

## Verification

- focused declarative rendering: `5` tests, `190` assertions — PASS;
- complete PHPUnit matrix: `331` tests, `5128` assertions — PASS;
- production build: `90` source pages — PASS;
- static verifier: `198` HTML pages, `14236` local references, `0` broken — PASS;
- generated `/ru/start/` article section has no `class` attribute;
- browser computed style: article section is `display:block`, `gap:normal`,
  and its Framework-managed content keeps its own margins;
- landing smoke: section remains block flow, the outer landing composer keeps
  its section-level `gap-2`, and the page has no horizontal overflow;
- local ServBay response: HTTP `200`.

## Result

`section.docara.article` is now a neutral semantic wrapper. Framework
typography and individual components own vertical spacing. Explicit `gap-*`
utilities remain only on actual composition surfaces such as grids, cards,
toolbars, menus, and the landing section composer.
