# Batch 8 — complete documentation implementation

Date: 2026-07-19
Starting closure: `33c9381`
Status: `AUTOMATED_PREACCEPTANCE_PASS`

## Result

Docara now has one task-oriented documentation system for five audiences:

1. a beginner can install, build, verify and open a portable site over HTTP;
2. an author can configure inheritance, layouts, components and publication;
3. a migrating owner has explicit legacy, template and Mix-to-Vite paths;
4. a maintainer has setup, architecture, testing and asset guidance;
5. an extension developer or AI has one native → typed → Smart → requirement
   decision path.

Nine manually maintained component-detail pages were removed. Component
identity, lifecycle, parameters, limitations, exact calls and examples now
belong only to the effective JSON catalogue and generated pages. All 17 source
records point to one of three stable conceptual documentation owners.

## Executable documentation contracts

Added:

- `DocumentationContractTest`;
- `DocumentationExamplesTest`;
- `PortableDocumentationSiteTest`.

The tests require five audience paths, exactly one authored H1 per page, no
retired manual component routes or links, stable Simai Framework naming,
bounded readiness statements, exact `docs_ref` mapping and the full generated
site matrix. Marked valid and invalid JSON examples execute through the real
`SchemaRepository`; invalid examples must return their declared error code.

The first focused run was meaningfully red: six failures exposed the two
missing build pages, absent executable examples, stale component links and an
incomplete authored inventory. The stable integrated tree passes:

- focused documentation/catalogue suite: 17 tests, 945 assertions;
- full sequential PHPUnit: 541 tests, 4,285 assertions;
- Pint: PASS;
- Composer strict validation: PASS;
- JSON parsing and `git diff --check`: PASS.

## Exact mutable build matrix

Two clean production builds from the same mutable tree are byte-identical:

- authored Markdown: 43;
- HTML pages: 56;
- search documents: 55;
- files: 66;
- catalogue records: 17;
- supported detail pages: 12;
- unavailable records: 5;
- generated catalogue surfaces: 13;
- retired manual routes: 0;
- static local references checked: 5,793;
- broken references: 0;
- canonical path-independent digest:
  `69e1fb2a341b43806ad8e00e14158b2234b0e79b5021efe1b047293d981bcc9e`.

The digest algorithm processes files in sorted relative-path order and feeds
SHA-256 with each UTF-8 relative path, one NUL byte and the raw 32-byte
SHA-256 digest of the file. This reproduces the algorithm used for prior
accepted local publications and is independent of the absolute build path.

## Review findings

- quick start ends with production build, static verification, HTTP preview,
  an observable browser result and `Ctrl+C`;
- built-in defaults, starter values, inheritance, absence, `$reset` and
  provenance are described separately;
- docs and landing presets use the same Markdown/JSON model;
- publication guidance is provider-neutral and requires staging, matching
  digest, smoke, atomic switch and rollback;
- portable author/build remains PHP-only; Vite belongs only to theme-source
  development;
- `docara-mix` and `docara-template` retirement remains a separate
  zero-reference and consumer-migration decision;
- no public release, production readiness, all-components readiness,
  Framework owner write or repository retirement is claimed.

## Acceptance boundary

This is mutable preacceptance evidence. Batch 8 and the wider Goal are not
accepted until one immutable candidate passes exact-archive tests,
complete-baseline HCS/source/security review and native-Chrome
UX/design/browser acceptance. The accepted Batch 7 build remains served until
all final gates pass.
