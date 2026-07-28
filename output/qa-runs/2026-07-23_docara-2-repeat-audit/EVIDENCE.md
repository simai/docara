# Evidence

## Exact source and tests

- clean detached checkout: `d239d9c97f32193385ac16212183e095338ac3f9`;
- Composer validate strict: PASS;
- Pint: PASS;
- PHP 8.2.29: 310 tests, 4162 assertions, PASS;
- PHP 8.4.20: 310 tests, 4162 assertions, PASS;
- Composer advisory audit: no advisories.

## Package and CLI

- relative and absolute `init [path]`: 20 starter files each;
- `init --update`: 0 copied, 20 preserved, modified `docara.json` unchanged;
- Composer archive before/after install: 413 files each;
- no archived `composer.lock`;
- both archive content digests:
  `578f44944eed4141ab5e2624aa1081b8a7519873aad5fca7ab092fc3ace97c31`;
- Composer-dist install/init/build/verify: PASS;
- portable starter verification: 58 HTML, 820 local refs, 0 broken.

## Documentation and local site

- clean docs build: 86 logical pages, 190 HTML, 10 480 refs, 0 broken;
- two builds: 227 files and identical digest
  `d4df1df05bccf844f5a334b65bd4f8a12dab0978abd073906d791399d0c8c9c5`;
- `/Users/rim/Sites/docara.test/build_production`: same 227 files and digest;
- served `/ru/` equals local `ru/index.html`, SHA-256
  `b573d863f7017fb44328eaaf8fb4e3c732e6a4e0bd82d77e602e4681bbb9c229`;
- link scan: 97 pages, 0 failures; unknown route returns HTTP 404.

## Browser

- Chrome desktop 1282x713: landmarks and navigation present, zero horizontal
  overflow, zero warnings/errors;
- search `установ`: 10 results and 14 `<mark>` highlights;
- deep CLI route: active nested `Справочник → CLI`, breadcrumbs and 10 outline
  links;
- mobile 390x844: desktop sidebars hidden, zero horizontal overflow, menu
  opens with current `CLI`, next-page card is visually above previous;
- mobile console: zero warnings/errors.

## Skill and workflow

- canonical skill: `0aa77d09eec9f045683a9dcc91a17f126c820504`, SHA-256
  `69b843029fbf5ea590948679c4fb508541d1877d85a63250f75b019b3f32c74b`;
- active stable skill: `c160b392e5947672fa3584b72dd33bf2f9237cf3`, SHA-256
  `e53b74c1fedb588365383f31b547879789ead665719fc862a26eb20813aa7fa4`;
- active revision is two commits behind canonical and contains Jigsaw, Mix,
  `source/docs`, npm/yarn instructions;
- installed-state history proves `0aa77d0…` was active at
  `2026-07-22T20:50:39Z`, then reverted to `c160b39…` at
  `2026-07-22T21:37:06Z` and remains there;
- continuation resolver returns `track_workflow_not_found`,
  `active_workflow_not_found` and failed technology execution packet.

## Rollback

- legacy backup exists at
  `/Users/rim/Sites/docara.test.backup-20260722-2054-legacy` (2.7 GB);
- operational journal and explicit atomic-rename rollback remain at
  `/Users/rim/Sites/.codex-backups/docara.test/2026-07-22-2054-docara2/`;
- prior rehearsal evidence remains intact;
- live rollback was intentionally not repeated during this read-only audit.

