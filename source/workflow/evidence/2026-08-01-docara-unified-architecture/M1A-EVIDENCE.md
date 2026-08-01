# M1A typed source locator evidence

Date: 2026-08-01

Branch: `codex/docara-unified-architecture`

Base revision: `959591151d7622747ee9c273300ef7cc8611d647`

Candidate binding: the commit containing this file; verify that its parent is
the base revision above. A commit cannot contain its own SHA without changing
itself.

## Implemented boundary

- `PageSource` carries canonical locale, repository-relative physical path and
  locale route as typed data;
- `RouteMapper` rejects unknown locales, paths outside the configured locale
  root, parent traversal and extensions other than `.md`;
- `PageSourceLocator` discovers physical sources in stable path order and
  rejects symlinks, the legacy `.markdown` extension and ambiguous pairs such
  as `guide.md` plus `guide/index.md`;
- `PortableSiteBuilder` now consumes the locator for authored routes; all
  later planning and rendering remains on the frozen legacy pipeline.

## Focused evidence

- new unit suite: PASS, 4 tests, 9 assertions;
- positive fixture: `index.md` and nested `guide/start.md` produce typed,
  stably ordered sources;
- negative fixtures: duplicate route, unknown locale, outside-root path,
  parent traversal and legacy extension all fail with typed error codes;
- formatter on all changed PHP files: PASS;
- `git diff --check`: PASS.

## Full and single-page parity

- full build: PASS, 103 pages;
- static verification: PASS, 206 HTML documents, 18,866 local references,
  zero broken references;
- baseline build from exact base code and candidate build used the same
  temporary source tree and dependency tuple: `diff -qr` PASS across all 321
  files;
- the preserved M0 manifest differs only in `_docara/page-metadata.json`
  because the temporary archive has different source mtimes and no Git
  metadata; every other file hash is identical;
- single-page build: PASS, 1 page selected at `/ru/components/badge/`;
- full/single badge HTML: byte-identical;
- badge HTML SHA-256:
  `faeb6c6a8e075bff9ad5602bcea4b1e019c700aeae74f696c0289e32fbb83f79`;
- full/single component asset catalog: byte-identical.

## Full verification

- PHP: 8.4.20;
- PHPUnit: PASS, 347 tests, 7206 assertions, 3 minutes 22 seconds;
- dependency tuple: the contract-checkpoint temporary installation, outside
  the repository; no lock or vendor write was made in the worktree.

The first direct build attempt inherited the documentation site's stale PHP
8.2 selector and stopped before build with a missing ICU 73 library. The
recorded retry used the explicit installed PHP 8.4.20 binary and passed. This
is an environment-selector failure, not ignored product evidence.

## Nonclaims and rollback

- M1B source-boundary guards are not implemented;
- `docara.gate.badge_source_ready` remains open;
- no renderer, public content, config, template, asset or legacy path changed;
- rollback is the single M1A commit; no data migration is required;
- no merge, tag, release or deploy was performed.
