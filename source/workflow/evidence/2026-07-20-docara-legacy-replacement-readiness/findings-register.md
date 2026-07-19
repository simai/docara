# Findings register

| ID | Finding | Severity | Evidence | Safe boundary | State |
| --- | --- | --- | --- | --- | --- |
| QA-REF-001 | Legacy capabilities lack a complete machine-readable disposition and replacement proof | P1 | legacy docs vs `docs/site/content/migration/legacy.md` | ledger, docs, tests; no legacy runtime restoration | implemented; exact acceptance pending |
| QA-REF-002 | Required legacy routes lack a generated redirect contract/corpus | P1 | legacy route tree and portable output | schema/data/generator/static verifier | implemented; exact acceptance pending |
| QA-UX-001 | Documentation shell uses card-on-card framing and excessive per-item borders | P1 | comparative 1440 `/start/` review | renderer presentation only; preserve semantic tree | resolved in working tree |
| QA-UX-002 | Mobile navigation/TOC pushes the article far below the viewport | P1 | comparative 390/768 review | overlay interaction and assets; preserve focus/accessibility | resolved in working tree |
| QA-UX-003 | Code block chrome contains nested surfaces/borders | P1 | start/catalog code examples | renderer/CSS only; preserve copy/highlight/scroll | resolved in working tree |
| QA-UX-004 | Docs H1/header/spacing are oversized relative to the reading task | P2 | comparative desktop/mobile review | docs preset only; landing hero unchanged | resolved in working tree |
| QA-CONTRACT-001 | Enhanced code behavior is visible while its catalogue record remains unavailable | P1 | runtime/catalog comparison | admit with exact proof or remove accidental enhancement | resolved: base behavior admitted; author controls remain gap |
| QA-CONTRACT-002 | Locale/version and `socialImage` have no accepted portable contract | P1 | migration documentation | explicit support/retire/defer decision and tests | resolved: locale/version implemented; social image deferred |
| QA-UX-005 | Search results are less dense and lack match highlighting | P2 | comparative search review | optional after mandatory shell work | parked |
| QA-UX-006 | Reader width/text-size controls differ from legacy | P2 | reader settings comparison | retain current default; add only with proven need | parked |

No finding authorizes public release, repository retirement or Framework owner
writes.
