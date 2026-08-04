# SF5 UI radius integration evidence

Date: 2026-08-04
Status: ready for independent audit
Docara product candidate: `1dee6d19e2d9a6c35402b3552f3f5c8c366317b6`

## Immutable Framework inputs

- source: `simai/ui-loader@36123543027d6b363c2242c747bf1fd8ec7d6c88`;
- distribution: `simai/ui@d1daa951dd08b94a9f209fd9f31a78d2b3779563`;
- distribution `distr` archive SHA-256:
  `8f28b6c5a09173f5502836506c64d2e0aa66c2099a4f95a1f26f47145981f7fe`;
- distribution files: `6238`;
- exact jsDelivr byte comparison: core/buttons/icon-buttons/inputs/dropdown
  PASS.

Framework source focused tests are 2/2, full tests 42/42. The deterministic
source build and distribution transform are recorded in the parent workflow.
The unrelated historical Framework registry JSON parse failure remains a
nonclaim and was not weakened.

## Docara focused checks

```text
ReaderPreferenceCompiler + configuration + Framework lock/composition:
60 tests / 424 assertions — PASS

Reader projection/browser-string regression:
2 tests / 630 assertions — PASS

Pint --test — PASS
Composer validate --strict --no-check-publish — PASS
git diff --check — PASS
Full PHPUnit: 437 tests / 7974 assertions — PASS
```

## Deterministic release package

Two independent `git clone --no-local` checkouts of exact product revision
`1dee6d19e2d9a6c35402b3552f3f5c8c366317b6` produced byte-identical release
artifacts with the repository-owned packager:

- ZIP SHA-256:
  `276f1491eaeda4c86432f793267d45980ec074e39588fdcfe38a67ea11a08dc2`;
- manifest SHA-256:
  `d78f77ce619edbdc54e94e031f55f2659dade0cf17501bf331a5ba784df8c8c2`;
- checksum-file SHA-256:
  `cfd6de2e5e252aa23483a1b41f8ca3d451bd3eedd52b58ca440f718dd0b70456`;
- package files: `741`;
- repository verifier: PASS.

The package is audit evidence only. No tag, release, publication or deploy was
performed.

## Fresh dist consumers

Two disposable Composer projects installed the exact ZIP as `dist` from the
same immutable consumer lock. Neither contains package `.git` or Node runtime
dependencies.

- consumer lock SHA-256:
  `4adfd98be91cdec885515fd1384da545c8c75d040bc95cd0892f5fa4699f2592`;
- both initialized the package starter and built `38` routes / `168` files;
- both static verifiers: `76` HTML / `3743` references / broken `0`;
- byte-identical consumer ledger:
  `ee9cb243bfac8328236a6e839f8a61973fd1463650bcc0c92cb5f4bc492a969d`;
- selected `/ru/components/alert/` rebuild preserves the complete ledger in
  both consumers.

Composer emitted only tool-owned PHP 8.4 deprecation notices.

## Build determinism and parity

Commands:

```text
php ../../docara build radius-proof-a
php ../../docara build radius-proof-b
php ../../docara verify-static build_radius-proof-a
php ../../docara build radius-proof-single --page=/ru/components/button/
```

Results:

- both full builds: `104` routes, `307` files, byte-identical;
- normalized full tree SHA-256:
  `981bef4125456978b75b85b85bf3f06ecdb8c5bfa15fb1b05874fa770fbd2404`;
- static verifier: `208` HTML, `21842` references, broken `0`;
- full/single complete tree equality: PASS;
- Button HTML SHA-256:
  `8adfbe6e0f2e6e834ab015073ccde0e971ff083a3eb61306aecc0db3c567cc6b`.

## Exact browser proof

Production build served from `build_radius-proof-a` through a disposable local
HTTP server. Playwright reloaded the exact page after CDN warm-up and reported:

| Mode | Shared token | Button computed radius |
| --- | --- | --- |
| `default` | `0.125rem` | `2px` |
| `medium` | `0.25rem` | `4px` |
| `large` | `0.5rem` | `8px` |
| restored | `0.125rem` | `2px` |

Both transient modals expose `backdrop-blur-none`; captured-run console errors
`0`, warnings `0`, horizontal overflow `0`. Screenshot SHA-256:
`74bce39fc552a1219105812e9e2c938d1238b5dbac15ecfa49758f8d0d5a12a4`.
Initial Chromium requests intermittently reported CDN socket errors; the exact
repeatable captured reload was clean and independent `curl` byte checks passed.
The final candidate differs from the captured runtime candidate only in the
reader-settings Markdown wording; runtime, templates, Framework assets and UI
configuration are byte-identical, so the browser proof is rebound without
claiming a new screenshot run.

## Boundaries

- one ReaderPreference registry and one allowlisted effect;
- one existing PageBuilder/Gateway/render pipeline;
- no arbitrary CSS values or component-ID dispatch;
- no edits to `docara.test` or `docara-new.test`;
- no merge, tag, release or deploy.

## Graph and handoff

- canonical project graph validator: `1` goal / `11` stages / `14` batches /
  `7` mappings, warnings `0`, blockers `0`;
- generated project context and handoff semantic freshness: PASS;
- tracked JSON validation and `git diff --check`: PASS.
