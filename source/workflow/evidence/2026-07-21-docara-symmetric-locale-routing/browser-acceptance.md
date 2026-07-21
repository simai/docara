# Browser acceptance

Date: 2026-07-21
Browser: native Google Chrome through Playwright
Verdict: PASS

## Desktop, 1440 x 1000

- `https://docara.test/` opened `https://docara.test/ru/`;
- the localization page exposed `lang=ru`, `dir=ltr`, the expected H1,
  self-canonical `/ru/authoring/localization/` and `x-default=/`;
- active page and expanded active ancestor were present in both navigation
  surfaces;
- rendered main content had zero `/authoring/...` legacy links and four direct
  `/ru/authoring/...` links;
- search opened, returned seven results for `локали`, and every sampled result
  used `/ru/...`;
- the Smart alert example rendered an alert with the Docara Smart shell;
- horizontal overflow was zero.

## Mobile, 390 x 844

- the exact mobile-navigation trigger opened its dialog;
- the active page was visible;
- a fourth navigation depth was present;
- horizontal overflow was zero.

## Network and RTL boundary

An isolated follow-up network run over root, localization and Smart alert
pages recorded no HTTP failure or failed request. RTL is not fabricated on the
Russian-only live documentation: the complete acceptance suite builds an
isolated Arabic locale and asserts `<html lang="ar" dir="rtl">`, while the
five-locale build (`ru`, `en`, `ar`, `zh-Hans`, `fr-CA`) is byte-deterministic.

This proves the engine's RTL contract without publishing untranslated Arabic
content on `docara.test`.
