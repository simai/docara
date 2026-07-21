# Multi-locale verification

Date: 2026-07-21
Verdict: PASS

The acceptance fixture builds these simultaneous locales from separate
Markdown trees:

| Locale | Prefix | Direction | Pack/fallback |
| --- | --- | --- | --- |
| `ru` | `/` | `ltr` | bundled `ru` |
| `en` | `/en/` | `ltr` | bundled `en` |
| `ar` | `/ar/` | `rtl` | bundled `ar`, then `en` |
| `zh-Hans` | `/zh-hans/` | `ltr` | bundled `zh-Hans`, then `en` |
| `fr-CA` | `/fr-ca/` | `ltr` | bundled `fr-CA`, then `en` |

Evidence:

- one build produced 100 declared outputs and 117 HTML pages;
- static verifier checked 3708 local references with zero broken references;
- search documents contain all five exact locale tags;
- Arabic output contains `lang="ar" dir="rtl"`;
- the Arabic language control contains all five options and selects `ar`;
- all five pages expose exact `hreflang` alternates;
- a repeated five-locale build is byte-for-byte deterministic;
- fallback cycles, unresolved required messages, unsafe pack paths and invalid
  locale tags fail closed in the regression suite.

The current Russian documentation build was also repeated byte-for-byte. Its
manifest digest is
`d228276224986c5e2bd04b64aa336945efcbd25fbc281d23c489418d56e26237`.

