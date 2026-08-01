# M1B source-boundary evidence

Date: 2026-08-01

Branch: `codex/docara-unified-architecture`

Base revision: `87bad1d4bf03d312e3e8ea3455afa6a31bb2ba61`

Candidate binding: the commit containing this file; verify that its parent is
the base revision above. A commit cannot contain its own SHA without changing
itself.

## Implemented boundary

- `SourceBoundaryValidator` rejects article prose, Markdown, HTML and CSS in
  target composition and component manifests;
- public PageBuilder input validation rejects `site.json`, `resources/i18n`,
  legacy language packs and package-owned system-message paths;
- `docara.lang.v1` accepts only bounded shared-UI namespaces and short plain
  strings; page, component, catalog and example prose cannot enter it;
- `docs/site/content/ru/lang.json` contains 66 shared interface strings and no
  catalog/example/component/page prose; all 92 legacy catalogue/example
  messages remain on the finite migration side;
- raw `lang.json` is an input and is not copied to public output;
- the explicit legacy allowlist records all 44 current generated routes,
  owners, deletion gate and per-locale language-pack component maxima;
  removal is permitted and growth fails closed.

## Badge source proof

- `/ru/components/badge/` is absent from the generated-route allowlist because
  `content/ru/components/badge.md` owns it;
- an integration test builds once with the current Russian language pack,
  removes only `components.docara.badge` from the same pack and builds again;
- both builds contain 103 pages and badge HTML is byte-identical;
- common shell strings remain on the legacy path only until PageBuilder
  consumes `lang.json` in M2.

## Verification

- focused source-boundary and allowlist tests: PASS, 7 tests, 29 assertions;
- focused badge language-pack independence: PASS, 1 test, 6 assertions;
- changed PHP formatter: PASS;
- JSON parse for schema, profile, allowlist, lang and graph: PASS;
- full PHPUnit: PASS, 355 tests, 7248 assertions, PHP 8.4.20, 3 minutes
  48 seconds;
- full build: PASS, 103 pages and 321 files;
- exact M1A/M1B build tree comparison: `diff -qr` PASS;
- static verification: PASS, 206 HTML documents, 18,866 local references,
  zero broken references;
- single-page build: PASS, one selected badge page;
- full/single badge and component asset catalog: byte-identical;
- badge HTML SHA-256:
  `faeb6c6a8e075bff9ad5602bcea4b1e019c700aeae74f696c0289e32fbb83f79`;
- `git diff --check`: PASS.

## Gate verdict

`docara.gate.badge_source_ready`: PASS. M2 may start.

The global `docara.gate.source_ownership` remains open because 44 generated
routes and prose-bearing legacy catalogue/example records have not migrated.

## Nonclaims and rollback

- the legacy language-pack runtime is not removed;
- no generated route, renderer, template, asset or public HTML is changed;
- M2 typed IR/PageBuilder is not implemented in this checkpoint;
- rollback is the single M1B commit and removal of the unused target
  `lang.json`; no public data migration is required;
- no merge, tag, release or deploy was performed.
