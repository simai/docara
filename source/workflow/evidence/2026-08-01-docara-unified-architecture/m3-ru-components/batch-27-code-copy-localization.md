# Batch 27 — localized code-copy controls

Date: 2026-08-02

Parent: `3293f65`

Candidate: commit containing this record

Verdict: PASS

## Finding and correction

The first M3.6 real-browser pass found that SIMAI Framework enhances static code
blocks with a visible English `Copy` button even on Russian pages. The authored
example control also retained English fallback labels in its HTML dataset.

The shared runtime now reads `code.copy` and `code.copied` from the existing
page runtime-copy payload. Russian values are owned only by
`docs/site/content/ru/lang.json`; package language packs and page Markdown do
not own them. A MutationObserver localizes Framework-injected buttons without
forking or modifying Framework, while the same labels configure the existing
example-copy control.

No component-specific renderer, PageBuilder, Smart gateway or pipeline was
added.

## Verification

```text
focused LocaleRuntime + SourceBoundary + real documentation suite
PASS — 17 tests, 149 assertions

php ../../docara build m3-b27-full
PASS — 103 selected pages

php ../../docara verify-static build_m3-b27-full
PASS — 206 HTML pages, 18,942 references, 0 broken

php ../../docara build m3-b27-alert --page=/ru/components/alert/
PASS — exact full/single HTML
```

- Alert full/single SHA-256:
  `6a2d923212904accc4b2558f4dc8ac2aa392b928877ac2e765062b8f38a63368`;
- declarative shell SHA-256:
  `9feba64f70aca83bbb2533ee9f915ee6aae013ef2f0daa92b6b511645c5c9e11`;
- browser at 1440px: both Framework code buttons expose visible text and
  `aria-label` `Скопировать`; console warnings/errors: 0;
- browser click executes the existing clipboard control without navigation or
  console failure.

Full PHPUnit passes with 377 tests, 6,540 assertions and zero warnings. Browser
matrix continuation, graph/JSON validation and hygiene are recorded by the
candidate checkpoint and M3.6 acceptance record.

## Rollback

Revert the candidate commit. The fallback remains English for locales that do
not yet own optional `code.*` strings; no other locale was migrated.
