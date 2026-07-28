# Batch 7 — exact browser, UX and design verdict

Date: 2026-07-20
Root browser verdict: `PASS`
Independent UX/design verdict: `PASS_WITH_NOTES`
Blocking findings: none

## Identity

- candidate:
  `2640503ba14913aa83bc3b4343c86966a807e29f`;
- tree:
  `4a0b5a68f613853ba9503f76d48068a1a6ca6724`;
- standard archive SHA-256:
  `fbda45bb8042140e817aafdcb881482765f39740b58b4e697db95e307049080b`;
- canonical build digest:
  `a16d61252837c8d23102e2285a948d7a81c513150080f09b2e9095c31ba475f4`;
- browser: native Google Chrome 150.0.7871.128 through Playwright.

The machine-readable root matrix is
`batch-7-root-browser-checks.json`, SHA-256
`c9c16c873cf6aa59d79b06d4a8c8522a6f1887b9e8a5ee3374ad767c5e518c9c`.

## Passed scenarios

- 9 representative routes × 3 viewports (`1440`, `768`, `390`):
  27/27 return 200, with no page overflow and no console/page errors;
- one-build identity: `lang=ru`, documentation version `current`;
- search keyboard navigation, Escape and trigger focus restoration;
- reader settings Tab/Shift+Tab containment, Escape, restore and persisted
  light/dark/system themes;
- four-level mobile navigation in a native modal, active trail, no article
  reflow, contained focus and restore;
- mobile outline in a native modal, no article reflow, contained focus and
  restore;
- all seven checked visible mobile controls are at least 44 px high;
- the primary `native.code` example has one visual surface, local horizontal
  scrolling and one copy control;
- permission-enabled clipboard payload exactly equals
  `$site = 'Docara';\necho $site;\n`;
- component catalog query, empty result, reset and count;
- redirect fallback page exposes canonical, `noindex`, meta refresh and a
  visible target link; a unique missing route remains 404.

## Comparative finding

Portable Docara is the stronger replacement base:

- it retains the useful legacy density, visible reading structure and
  documentation affordances;
- it adds a reliable four-level active trail, accessible modal behavior,
  deterministic assets/output, explicit redirect and locale/version contracts;
- it removes the legacy mobile clipping, unnamed/small controls, broken
  favicon and opaque mutable frontend/build coupling.

## Non-blocking notes

These observations are recorded for a later polish Goal and do not alter the
accepted candidate:

- colloquial search `редиректы` has no synonym; the documented
  `перенаправления` and `redirect` terms work;
- Framework-owned labels `Copy` / `Copied` remain English;
- a secondary framework-invocation copy control is about 24 px high; the
  primary reader code control and all checked navigation controls meet the
  accepted target;
- this exact run is Chromium-only.

The independent disposable report was produced at
`/private/tmp/docara-2640503-acceptance.WFqSUy/acceptance-report.md`,
SHA-256
`3d9b7a4992456a7ee373802e6197f91b563a9fe9154798ef3610fb4c0a8f519e`.
