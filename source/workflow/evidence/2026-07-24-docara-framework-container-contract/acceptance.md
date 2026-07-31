# Docara Framework container contract — acceptance

Date: 2026-07-24
Verdict: `PASS`
Scope: local integration; no public release or production-readiness claim

## Exact candidates

| Role | Revision |
|---|---|
| Framework source | `ui-loader@922c1745a1f09f291e81fdf4b9cd08274807d45f` |
| Canonical builder | `ui-builder@894cfd4a323ee99381f21bf97467436c72e8a204` |
| Generated Core | `ui@f0b41eb526a8f1daf24a34484143bdfabf7802a4` |
| Generated Smart | `ui-smart@ab896dc7cd33f151377e3992ffb286769beee7f7` |
| Framework docs | `ui-doc@39a2312e7395e5f0aa3e2aa4f4c5a730cb2fb1db` |
| Executable demo | `ui-play@4225ec17b6ff79a6d957d765430ede7e8f73a90e` |
| Compatibility pair | `sf-v5.3.2-f0b41eb5-ab896dc7` |

The raw `git archive --format=tar 4100d3f7... distr` SHA-256 is
`040485a1dc7cba671e05240e3a9a8b93b5982cb5b264b5a044e213ac26dc93a9`
and contains `5873` files.

## Framework evidence

The canonical two-wave verifier returned `PASS`.

| Product | Files | Manifest SHA-256 | Byte-identical |
|---|---:|---|---|
| Core | 57 | `140f1e2d62d7811f7f915f05dd1bf131513537d87d39c0afae9a7881f6d4503b` | yes |
| Component | 1886 | `1d08b0fe515a32a07a6b51364866fd2e25a350f576418e305deff8562f307614` | yes |
| Utility | 3836 | `adcccf4eec13cc3c140ac933ca5feef2294e5ea312ecf585f017c1687c2b83fa` | yes |
| Smart | 362 | `907ea8db26e45eb414adf202fcd9b0fc02508a8284608285e490a13a26ade239` | yes |

Source tests: `24/24` PASS.
Highlight contract: PASS across `1768` source files and `18` token mappings.
The earlier container browser matrix passed `144` assertions:
`9 viewports × 2 directions × 8 max-container values`.

The fresh generated runtime additionally proves:

- Core loader, Core and Smart base are one generated set;
- released Smart components retain `markStylePending`,
  `markStyleReady` and `whenRenderedStylesReady`;
- Highlight derives its public root from its own component bundle;
- the observed lazy chunk is served from
  `distr/component/highlight/js/47358934084073.js`.

## Docara automated evidence

| Check | Result |
|---|---|
| PHPUnit | PASS — 320 tests, 4957 assertions |
| Pint | PASS |
| JSON parse | PASS |
| Legacy-width zero-reference search | PASS |
| Production build | PASS — 90 source pages |
| Static verifier | PASS — 198 HTML pages, 10718 references, 0 broken |
| Published static verifier | PASS — same bounded snapshot |

The direct `vendor/bin/pint` launcher is unusable on this machine because its
Homebrew PHP links to a removed ICU 73 library. The accepted formatting check
therefore used the explicit ServBay PHP 8.4.20 binary, which passed.

## Browser evidence

Fresh browser session, not inherited console history:

- exact Core revision visible in page scripts:
  `f0b41eb526a8f1daf24a34484143bdfabf7802a4`;
- no console errors or warnings after documentation runtime, Highlight and
  reader-settings interaction;
- code blocks are upgraded to `language-* hljs` and contain syntax spans and
  line-number markup;
- light theme: `rgb(255, 255, 255)` background, no overflow;
- dark theme: `rgb(15, 17, 21)` background, no overflow.

Geometry:

| Requested viewport | Actual content viewport | Surface | Result |
|---:|---:|---|---|
| 390 | 376 | landing + docs | no overflow; all regions use full available width |
| 1440 | 1426 | landing + docs | no overflow; aligned regions |
| 2560 | 2546 | landing | full-bleed 2546px; inner/header/article 1664px at x=441 |
| 2560 | 2546 | docs | header/grid 1664px at x=441; no overflow |

RTL is accepted through the exact Framework LTR/RTL matrix and the Docara
arbitrary-BCP47 portable-site test, which builds an Arabic locale with
`<html lang="ar" dir="rtl">`.

## Publication and rollback

Published document root:
`/Users/rim/Sites/docara.test/build_production`.

Rollback backup:
`/Users/rim/Sites/docara.test/.docara-backups/framework-container-20260724-145338`.

Rollback:

```bash
rsync -a --delete --exclude=.docara-backups/ \
  /Users/rim/Sites/docara.test/.docara-backups/framework-container-20260724-145338/ \
  /Users/rim/Sites/docara.test/
```

## Nonclaims

- no public Framework release or tag;
- no public Docara release;
- no default-branch merge;
- no production-readiness claim;
- no claim that every Framework component is ready.
