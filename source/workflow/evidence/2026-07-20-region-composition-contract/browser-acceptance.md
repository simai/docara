# Browser acceptance

Date: 2026-07-20
Surface: local HTTPS Docara
Verdict: PASS

URL:
`https://docara.test/_docara/declarative-preview/pages/authoring/regions/`

## Desktop

Viewport observed: 1282 x 657.

- title: `Области макета — Declarative preview`;
- regions: `header`, `main`;
- aside count: 0;
- main count: 1;
- H1 count: 1;
- horizontal page overflow: false;
- browser warnings/errors: none.

Control page:
`/_docara/declarative-preview/pages/authoring/layout-and-navigation/`

- regions: `header`, `sidebar`, `main`, `outline`;
- aside count: 2;
- horizontal page overflow: false.

This proves that the demonstration difference comes from page configuration,
not from a globally missing shell.

## Mobile

Requested viewport: 390 x 844; page client width: 378.

- regions: `header`, `main`;
- aside count: 0;
- main count: 1;
- horizontal page overflow: false;
- code blocks contain their own overflow safely;
- content, table, JSON example and informational alert remain readable.

The browser was left on the demonstration page for direct user inspection.
