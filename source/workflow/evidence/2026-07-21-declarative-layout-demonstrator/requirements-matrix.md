# Requirement matrix

| Requirement | Evidence | Result |
| --- | --- | --- |
| Regions documentation keeps navigation | Desktop browser active menu and sidebar | PASS |
| Catalogue is in documentation tree | `/examples/`, active menu, 7 cards | PASS |
| Canonical descriptor and exact sources | 7 descriptors and receipt source hashes | PASS |
| Same primary publisher | Resolved page plans use declarative publisher | PASS |
| Purpose, result and code | Detail template and browser inspection | PASS |
| Isolated result routes | Hidden authored pages under `demonstrator-results` | PASS |
| Composed and disabled regions | `regions-composed`, `regions-disabled` | PASS |
| Inheritance | nested `section.json` plus page override | PASS |
| Docs and landing preset | `preset-docs`, `preset-landing` | PASS |
| Two Smart components | `smart-alert`, `smart-button` | PASS |
| No empty disabled wrappers | focused test and iframe DOM audit | PASS |
| Fail-closed inputs | five negative cases plus existing unsupported Smart gate | PASS |
| PHP-only deterministic build | Composer/PHP path; double-build check | PASS |
| Static verification | 137 pages, 13,804 references, no broken records | PASS |
| Desktop/mobile browser acceptance | 1440x1000 and 390x844 | PASS |
| Reversible local publication | staged swap and retained backup | PASS |
| No release/readiness overclaim | no push, tag, release or production action | PASS |
