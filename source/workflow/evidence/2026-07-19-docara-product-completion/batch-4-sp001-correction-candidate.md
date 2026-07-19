# Batch 4 — SP-001 correction candidate

Date: 2026-07-19
Parent candidate: `adad417a9ea6cad98bc79650710a4d4e732f8cac`
Status: candidate preparation complete; immutable and browser acceptance pending

## Bounded correction

`SP-001` existed because modal mutual exclusion was enforced only by the
search-trigger click listener. The document-level `Cmd/Ctrl+K` handler called
the shared `openSearch()` function directly and bypassed that listener.

The correction keeps one ownership boundary:

- the portable search runtime discovers the existing reader-settings dialog;
- `openSearch()` closes it before it calls `dialog.showModal()`;
- no Framework asset, theme contract, component registry or public API changes;
- no new visual primitive or parallel interaction system.

## Test-first evidence

The generated-runtime regression was added before implementation and failed on
the parent candidate. It requires both the close operation and its ordering
before `showModal()`. After implementation:

- focused test: 1 test, 297 assertions, PASS;
- full PHPUnit: 465 tests, 2385 assertions, PASS;
- Pint: PASS using ServBay PHP 8.2;
- Composer strict validation: PASS;
- `node --check resources/portable/search.js`: PASS;
- PHP syntax and `git diff --check`: PASS;
- two clean production builds: byte-identical;
- each build: 44 HTML pages, 4535 checked local references, zero broken;
- common path-independent digest:
  `945481f5badb7508860dbb44b56a610e4e1f5484d1cc4ff4baffae322b9f15fd`.

The local immutable-commit preflight passed at
`source/output/action-gates/action-gate-report-20260719040941.json`.

## Physical-keyboard preacceptance boundary

A native-Chrome runner was corrected to use real
`page.keyboard.press('Meta+K')` and `page.keyboard.press('Control+K')` events.
It covers normal and native disabled local storage, 390 and 1440 px, light and
dark, both modal directions, Escape, focus, geometry, icons and browser errors.

- runner: `/tmp/docara-b4-sp001-correction-preacceptance.js`, SHA-256
  `bae37bb32fcc567599b4dc085b9cb49ca9e6593a75cb67d0b28c7603c5f57bf5`;
- frozen build:
  `/tmp/docara-b4-sp001-correction-preacceptance/build_snapshot`;
- frozen canonical digest:
  `945481f5badb7508860dbb44b56a610e4e1f5484d1cc4ff4baffae322b9f15fd`.

The first runner attempt is excluded: it required `:focus-visible` after a
pointer click and failed before reaching the shortcut, producing 16 harness
false failures rather than product findings. The corrected runner opens reader
settings with keyboard Enter.

The corrected native-Chrome repeat was not executed because the external
approval transport reported its usage limit and explicitly prohibited an
indirect workaround. Therefore this file does **not** claim browser PASS,
Batch 4 acceptance, publication, production or wider-Goal readiness. The new
immutable candidate may receive automated/source verdicts, but its exact
physical-keyboard browser gate remains mandatory before local publication.
