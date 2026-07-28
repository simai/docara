# Requirement matrix

Candidate:
`a29c1ab03462415879ec7383e6cf53e1dcccb1c2`

| Requirement | Evidence | Verdict |
| --- | --- | --- |
| Typed immutable `DocumentAst` | `src/Declarative/Document/**`; parser tests | PASS |
| Parser contains no HTML, CSS or JavaScript implementation | architecture boundary test | PASS |
| `docara.docs` declares header, sidebar, main, outline and footer | layout descriptor, schema and compiler test | PASS |
| Page resolves through Section, Block and Smart | compiler DTOs and plan tests | PASS |
| Immutable `ResolvedRenderPlan` with stable hash | readonly DTOs and repeated-hash assertions | PASS |
| `ui.alert` uses exact manifest and view | `SmartPlanResolver`, view descriptor and tests | PASS |
| Trusted allowlisted template selection | `TrustedTemplateRegistry`; path/context negative tests | PASS |
| Template receives immutable prepared view model | rendering view models and template static checks | PASS |
| Templates contain presentation only and no embedded CSS/JS | template architecture test | PASS |
| Render artifact contains HTML, assets and provenance | rendering test | PASS |
| Builder/orchestrator contains no HTML, CSS or JavaScript implementation | architecture boundary test | PASS |
| Legacy renderer remains unchanged | candidate blob SHA-256 equals baseline | PASS |
| Legacy and new paths are semantically equivalent | focused parity test and 44 build diagnostic PASS records | PASS |
| Same plan passes through Larena contract adapter | adapter test and 44 diagnostic PASS records | PASS |
| Unsupported/managed Smart inputs fail closed | managed `id`, closable state, unknown view/template and invalid context tests | PASS |
| Full regression remains green | 568 tests / 4,625 assertions in UTC | PASS |
| Static build is deterministic and valid | identical digest; 66 pages, 6,036 references, zero broken | PASS |
| No production/readiness overclaim | shadow-only diagnostics and explicit documentation nonclaims | PASS |
| Old renderer is not removed before a later migration acceptance | renderer retained and remains publication source | PASS |

## Nonclaims

This matrix does not claim:

- all Docara Smart-components use the new pipeline;
- landing, search, navigation or the full page shell have been extracted;
- Larena runtime/storage/access integration is implemented;
- the new renderer may replace the accepted renderer;
- production, package release or public readiness.
