# Final reverse-outcome audit: Docara 2 corrections

Date: 2026-07-22
Verdict: **PASS**
Exact product candidate:
`0d2a528c4bd5cff5b4986ff60e0abd668d328f47`

## Intended outcome

A developer can install the portable Docara 2 package into an explicit target
directory, build the bundled documentation deterministically, use an installed
Docara skill that describes the same architecture, and inspect the exact
candidate at `https://docara.test/` with a usable rollback.

## Blocking findings re-tested

| Finding | Evidence | Result |
|---|---|---|
| Documentation advertised `init [path]`, but CLI rejected the path | Relative and absolute paths create 20 starter files; `--update` preserves all 20; file-target refusal is tested; published CLI page shows the same contract | PASS |
| Composer archive depended on an ignored local lock | Exact candidate archived before and after Composer install: 413 identical entries, identical extracted contents, zero `composer.lock` entries, aggregate content digest `578f44944eed4141ab5e2624aa1081b8a7519873aad5fca7ab092fc3ace97c31` | PASS |
| Installed Docara skill described Jigsaw/Mix | Canonical commit `0aa77d09eec9f045683a9dcc91a17f126c820504` installed through immutable federation maintenance; installed/canonical hash `69b843029fbf5ea590948679c4fb508541d1877d85a63250f75b019b3f32c74b`; zero Jigsaw/Mix references | PASS |
| `docara.test` served a legacy project | Active site contains only the exact static build and its manifest; 190 HTML / 227 files; served `/ru/` equals local file; previous 2.7 GB site is preserved and rollback was actually rehearsed | PASS |

## Exact-candidate verification

- clean clone at the exact SHA;
- Composer validation: PASS;
- Pint: PASS;
- PHP 8.2.29: 310 tests, 4162 assertions, PASS;
- PHP 8.4.20: 310 tests, 4162 assertions, PASS;
- production docs: 86 source pages, 190 HTML files, 10,480 local
  references, zero broken references;
- independently rebuilt output manifest equals the served manifest byte for
  byte, SHA-256
  `d4df1df05bccf844f5a334b65bd4f8a12dab0978abd073906d791399d0c8c9c5`;
- clean dependency lock used for the exact build:
  `e72f6bb3bfee31612ca688260376a83b07995bbd39d371dea28e75db04a25724`.
- human-centered simplicity acceptance from a clean disposable clone of the
  exact candidate: `PASS`, zero blockers and warnings.

## Browser acceptance

- desktop 1296×657: left navigation, breadcrumbs, article and contents are
  present; no horizontal overflow; no console warnings/errors;
- mobile 390×844: no horizontal overflow; desktop sidebars are hidden;
  responsive section dialog opens and marks `Миграция` active;
- local search for `установ`: 10 results, 14 highlighted matches, working
  snippets; modal open/close works;
- published CLI page contains `init [path]` and explains relative and absolute
  paths;
- active site uses only generated Docara/Simai Framework output; no local
  legacy runtime remains in its root.

## Backup and rollback

- previous site:
  `/Users/rim/Sites/docara.test.backup-20260722-2054-legacy`;
- operational journal:
  `/Users/rim/Sites/.codex-backups/docara.test/2026-07-22-2054-docara2/`;
- rollback rehearsal restored the legacy root with its pre-change SHA-256
  `38365edd483ebe5c6121d5f4b17cc275fb31f2ab4e55817990ced80845804c5c`,
  then re-applied the corrected candidate and re-confirmed the exact `/ru/`
  SHA-256
  `b573d863f7017fb44328eaaf8fb4e3c732e6a4e0bd82d77e602e4681bbb9c229`.

## Scope verdict

The four requested blockers are closed. This is product-candidate and local
test-site readiness only. It is not a package publication, public release,
default-branch merge or production-readiness claim.
