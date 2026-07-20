# Batch 8 — local publication and rollback evidence

Date: 2026-07-20
Verdict: `PASS`
Target: `https://docara.test/`

## Published identity

- immutable candidate:
  `2640503ba14913aa83bc3b4343c86966a807e29f`;
- tree:
  `4a0b5a68f613853ba9503f76d48068a1a6ca6724`;
- standard archive SHA-256:
  `fbda45bb8042140e817aafdcb881482765f39740b58b4e697db95e307049080b`;
- canonical build digest:
  `a16d61252837c8d23102e2285a948d7a81c513150080f09b2e9095c31ba475f4`;
- output: 77 files, 66 HTML pages.

The served tree is byte-identical to exact build A at
`/private/tmp/docara-exact-2640503.oCHReP/dist-a/docs/site/build_production`.

## Gate

The local publication action-gate preflight returned `success`, with no
warning or blocker:

- generated report:
  `source/output/action-gates/action-gate-report-20260720002604.json`;
- report SHA-256:
  `8a6839fb9049d7718c4c22fe41253695878f66c701ade707ab95ab982e89a98e`;
- risk classification: reversible local work;
- public release, Git branch/tag operations, ServBay configuration,
  credentials, databases and repository retirement remained forbidden.

The post-check gate also returned `success`, with no warning or blocker:

- report:
  `source/output/action-gates/action-gate-report-20260720003627.json`;
- report SHA-256:
  `8f53b6701275a5d0a3dd70541e3809cf2fdd96e9e500ba7b5da8108e05ad24cb`.

## Publication batch

Batch:
`replacement-ready-docs-2640503-20260720-032656`

Paths:

- served:
  `/Users/rim/Sites/docara.test/build_production`;
- independent rollback backup:
  `/Users/rim/Sites/docara.test/.docara-backups/replacement-ready-docs-2640503-20260720-032656/build_production`;
- atomic pre-swap tree:
  `/Users/rim/Sites/docara.test/.docara-staging/replacement-ready-docs-2640503-20260720-032656/served-before`.

All three paths report filesystem device `16777230`. The swap used two
same-filesystem directory renames; ServBay configuration and processes did
not change.

## Baseline and rollback proof

Before the swap, the served tree was byte-identical to the previous exact
accepted build:

- previous digest:
  `502e43119ea2f2fc6ce358042858937060a67c4aa3d4d5ac0295e3d19c8e782f`;
- 66 files, 56 HTML pages;
- previous verifier: 5,793 local references, zero broken.

The independent backup and `served-before` both reproduce that digest and
verifier result and are byte-identical to each other. Rollback therefore
requires one same-filesystem rename and does not depend on rebuilding the old
candidate.

Canonical manifest SHA-256:

- previous served and backup:
  `0e0bb352d6bad9c7b26445585470e3f4dce6318afbf965179b3ffa54749c8826`;
- candidate staging and served:
  `e3866c0baab31d0e5f1118ab492c087953fad5eb38d117d1718f29ab4146a099`.

## Served static and HTTPS smoke

Post-swap verifier:

- 66 HTML pages;
- 6,033 local references;
- zero broken;
- served canonical digest exactly
  `a16d61252837c8d23102e2285a948d7a81c513150080f09b2e9095c31ba475f4`.

HTTPS returned 200 for:

- `/`;
- `/start/`;
- `/authoring/layout-and-navigation/hierarchy/four-level/`;
- `/authoring/redirects/`;
- `/components/catalog/`;
- `/components/catalog/native.code/`;
- `/landing/`;
- `/development/extensions/`;
- `/build/verify/`;
- `/_docara/component-catalog.json`;
- `/_docara/search-index.json`;
- `/components/code/`.

A unique missing route returned 404.

## Served native-Chrome smoke

- 9 routes × 3 viewports (`1440`, `768`, `390`): 27/27 passed;
- every page returned 200, `lang=ru`, documentation version `current`;
- no page-level horizontal overflow;
- no unexpected console or page errors;
- search `перенаправления`: two results and trigger focus restored;
- reader settings: Tab/Shift+Tab focus contained and Escape restored the
  trigger;
- dark theme applied and survived reload;
- mobile menu: active four-level page, no article reflow, focus contained,
  Escape and restore passed;
- mobile outline: no article reflow, focus contained, Escape and restore
  passed;
- catalog: 17 total, `код` gives 2, impossible query gives 0 plus an empty
  state, reset restores 17;
- primary code surface: one block, one header, one `pre`, no nested visual
  surface, horizontal overflow is locally contained;
- permission-enabled clipboard exactly returned
  `$site = 'Docara';\necho $site;\n`;
- raw redirect fallback contains canonical, `noindex`, meta refresh and a
  visible ordinary link.

## Boundary

This proves local replacement readiness only. No public release, push, merge,
tag, default-branch change, repository archive/deletion or Framework-owner
write was performed.
