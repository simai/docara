# Browser acceptance

Date: 2026-07-21
Verdict: PASS

Published site: `https://docara.test/authoring/localization/`

Chrome acceptance on the real ServBay site:

- desktop 1440 x 900: correct H1, active navigation, breadcrumbs, outline,
  `lang="ru"`, `dir="ltr"`, no horizontal overflow and no console warnings or
  errors;
- mobile 390 x 844: responsive header/menu/outline controls, active nested
  navigation, `scrollWidth === clientWidth`, no console warnings or errors;
- the language control is intentionally omitted on this site because only one
  real content locale is configured.

RTL acceptance used a disposable exact five-locale build served locally:

- Arabic page loaded its shell, search and Framework assets over HTTP 200;
- executed browser metrics reported a 500 px effective headless viewport with
  `scrollWidth === clientWidth === 500` for root, body and layout;
- reading column and main content remained wholly within the viewport;
- generated DOM contained `lang="ar" dir="rtl"`, all five language options,
  selected Arabic and five exact alternate links.

Native headless Chrome enforces a 500 px minimum layout viewport when a 390 px
screenshot is requested. The initially cropped screenshot was therefore not
treated as product evidence; executed layout metrics and the real 390 px
browser session are the acceptance sources.

