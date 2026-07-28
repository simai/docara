# Browser acceptance

Date: 2026-07-20
Status: PASS
Surface: local ServBay HTTPS through the in-app browser

## Desktop

Catalogue:
`https://docara.test/_docara/declarative-preview/`

Confirmed:

- H1 and shadow-mode explanation are visible;
- counts are `Собрано: 45`, `Пропущено: 0`;
- receipt link is present;
- every inspected row has legacy identity and a preview action;
- Framework button contrast is readable after correction `4fa4bbf`;
- no missing-content or unstyled flash was observed.

Detailed page:
`https://docara.test/_docara/declarative-preview/pages/development/declarative-preview/`

Confirmed:

- preview banner, catalogue link and exact legacy link are visible;
- Docara header, four-level navigation, active route and article content render;
- internal authored links are projected under
  `/_docara/declarative-preview/pages/**`;
- `Быстрый старт` navigation reaches
  `https://docara.test/_docara/declarative-preview/pages/start/`;
- resulting H1 is `Быстрый старт`.

## Narrow viewport

Viewport: 390 × 844.

Catalogue:

- controls wrap without clipping;
- rows and actions remain readable;
- buttons have readable primary contrast.

Detailed page:

- document width: 390;
- document scroll width: 378;
- no horizontal overflow;
- H1 and documentation version `current` are present;
- the complete navigation precedes article content in the current shadow
  implementation.

The long mobile navigation is accepted only for the shadow preview. Full visual
parity, final mobile information architecture and switching the primary
publisher remain explicit nonclaims.
