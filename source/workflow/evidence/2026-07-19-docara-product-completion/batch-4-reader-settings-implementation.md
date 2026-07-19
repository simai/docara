# Batch 4 — reader-settings implementation

Date: 2026-07-19
Baseline: `06a993f3e0ce8df3bbe26569aa917b7bfe6de6a5`
Status: candidate-ready worktree; exact-candidate acceptance pending

## Product result

The former binary theme action is now one compact reading-settings dialog with
three explicit choices: follow the system, light or dark. The reader choice is
applied immediately, restored before paint, preserved between pages and can be
reset to the current page's inherited author setting.

No account, request, database, service or portable Node.js runtime was added.

## Simai Framework implementation

- the header trigger and close action use Core `sf-icon-button` with projected
  `sf-icon` glyphs;
- the choices use the exact Core radio structure and native radio inputs;
- the reset action uses the Core button component;
- the surface is a native `dialog` composed with Framework surface, border,
  radius, spacing, flex, gap, color and focus primitives;
- the rendered theme remains `theme-light` or `theme-dark` with Framework
  tokens; Docara owns only preference selection and dialog orchestration;
- `.sf-theme-button` is removed so the pinned binary Framework handler cannot
  run in parallel;
- the pinned Core `theme: false` boot option disables its later binary
  OS/cookie bootstrap, preventing it from overwriting Docara's resolved author
  default or tri-state reader choice;
- no Smart modal/dropdown, new dependency, Framework fork or asset-projection
  change was introduced.

## Preference contract

- reader key: `docara.reader.theme.v1`;
- values: `system`, `light`, `dark`;
- first visit/reset target: inherited author `settings.theme`;
- compatibility: explicit light/dark also projects `sf-theme`; system and reset
  clear the cookie;
- invalid or unavailable storage falls back safely;
- a denied storage write keeps the radio, theme and reset coherent on the
  current page and produces an honest non-persistence status;
- a live system media-query change is applied only while the effective reader
  preference is `system`;
- a storage event synchronizes other tabs.

## Test-first and automated verification

The generated-HTML assertion was changed first and failed on the accepted
baseline because the new settings trigger did not exist. After implementation:

- focused corrected renderer/author-theme pair: `2 tests`, `275 assertions`,
  PASS;
- full PHPUnit after correction: `465 tests`, `2356 assertions`, PASS;
- Pint: PASS;
- Composer strict validation: PASS;
- four generated inline scripts: JavaScript syntax PASS;
- `git diff --check`: PASS;
- production static verifier: `44` HTML pages, `4535` local references, zero
  broken;
- two consecutive production builds: identical relative tree digest
  `9998f8a2e0b011a95e2c061e1f392bf4e877ae86ee3acf323bf30ef038bd7f53`.

## Documentation

A dedicated reader-settings guide documents author defaults, reader override,
system behavior, reset, storage and the deliberate exclusion of font-size and
content-width controls. The beginner, configuration and layout paths link to
that guide.

This evidence is bounded to Batch 4 implementation and does not accept the
batch, the wider Goal, a public release or production readiness.
