# Readiness matrix

Date: 2026-07-21

| Requirement | State | Evidence |
| --- | --- | --- |
| Language-neutral technical manifests | complete | static audit |
| Arbitrary BCP 47 locale registry | complete | contract tests |
| External language packs | complete | five bundled packs and schema |
| Explicit fallback graph | complete | positive/negative resolver tests |
| Separate Markdown trees | complete | five-locale fixture |
| One multi-locale build | complete | 100 outputs, static PASS |
| Isolated routes/navigation/search | complete | integration/full regression |
| Switcher and hreflang | complete | DOM and browser evidence |
| LTR/RTL | complete | real LTR plus Arabic browser metrics |
| Existing Russian site compatibility | complete | 154-page build and HTTP checks |
| Deterministic build | complete | duplicate manifest comparison |
| Documentation/migration path | complete | localization and migration guides |
| Full regression | complete | 602 tests, 5022 assertions |
| Local publication/rollback | complete | deployment evidence |

This is implementation and local acceptance readiness. It is not a public
release, production deployment or production-readiness claim.

