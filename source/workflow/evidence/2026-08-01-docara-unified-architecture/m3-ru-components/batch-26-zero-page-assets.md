# Batch 26 — zero-page catalog asset retirement

Date: 2026-08-02

Parent: `a579390db9e8841f97d841ff060fba300d611ca4`

Candidate: commit containing this record

Verdict: PASS

## Result

The builder now publishes packaged component-catalog assets per locale only
when that locale has at least one generated catalog page. The fully authored
Russian component section has an empty catalog projection, so its build no
longer contains `_docara/component-catalog/`.

This is one conditional publication boundary inside the existing
`PortableSiteBuilder`; no second pipeline, PageBuilder, renderer, gateway or
content registry was added. English legacy generated projections still publish
and verify their exact packaged assets.

## Zero-reference proof

Before the change the Russian full build published seven unreferenced files:

| Asset | SHA-256 |
| --- | --- |
| `simai.svg` | `10781f8fb31318e8807a215ec002ec1f8033227bf8678300e3dc63a7e3c1aebc` |
| `feature-markdown.png` | `ab83a4440cd0342045881a9190cc8a6a0fe9c70b2200bb0476acee8047a48124` |
| `docara-mark.svg` | `bdcbb55b92479cc9f2d390aadc78276d12500fda39e7c8dba9e977dfc20b6a16` |
| `docara-flow.png` | `c7ffa0a31f9c228fa85229a227b786891aa7702f1217e68d14e8f5449165e124` |
| `docara-screen.png` | `f3192e9a98abee5155b9546b321356a4e6e244223958f391bb88b7332a144502` |
| `feature-json.png` | `8bc203a44c69b172aad902985e254c321c4159c7ff35a393d91173a9ff69b327` |
| `feature-build.png` | `97881fd1b4416f533f92688969e3ba0885d36f96018cfe12b512f834a698ca32` |

`rg '_docara/component-catalog/'` over all Batch 25 HTML/JSON outputs returned
zero references. The source assets remain packaged for non-migrated locale
projections; only their unused Russian build copies are suppressed.

## Build, parity and verification

```text
php ../../docara build m3-b26-full
PASS — 103 selected pages

php ../../docara verify-static build_m3-b26-full
PASS — 206 HTML pages, 18,942 local references, 0 broken

php ../../docara build m3-b26-index --page=/ru/components/
PASS — 1 selected page

php ../../docara build m3-b26-badge --page=/ru/components/badge/
PASS — 1 selected page
```

- Batch 26 equals Batch 25 byte-for-byte outside the intentionally absent
  `_docara/component-catalog/` directory;
- component index full/single SHA-256 remains
  `38ef5ff864ade3bd043d9bb245a939205cb9698fd12e13791188afd889fae6b7`;
- Badge full/single SHA-256 remains
  `553aff3e1a0135fe244ee6f7d1c93f4bdf859eba550ebca41964fda558747f77`;
- generated English projection test publishes the directory and passes its
  fail-closed asset verification.

## Tests and rollback

The static verifier accepts an absent directory only when its independently
reconstructed trusted projection has zero pages. An unexpected directory,
unsafe link, missing required generated asset or byte drift still fails.

Full PHPUnit passes with 377 tests, 6,532 assertions and zero warnings. PHP
lint, graph/JSON validation, `git diff --check` and repository hygiene also
pass. Rollback is `git revert <batch-26-commit>`; no packaged source asset was
deleted.
