# Reverse outcome audit

Date: 2026-07-21
Verdict: PASS

| Required outcome | Evidence | Result |
| --- | --- | --- |
| one locale, one source tree | Docara and starter use `content/ru`; five-locale fixture uses isolated roots | PASS |
| one locale, one URL prefix | shared `LocaleUrlProjector`; `/ru/` live | PASS |
| deterministic root behavior | `/` opens `/ru/`; browser detection disabled | PASS |
| old mode preserved | complete suite covers `default_unprefixed` compatibility | PASS |
| old Docara links preserved | 92 legacy redirects, HTTP smoke and verifier | PASS |
| generated surfaces localized | catalogs, examples and declarative preview verified | PASS |
| authored links localized | final HTML has no legacy Markdown authoring links | PASS |
| canonical and hreflang correct | static and browser checks | PASS |
| LTR/RTL and arbitrary tags | live Russian LTR plus isolated Arabic RTL and five BCP 47 locales | PASS |
| search, menu and Smart runtime work | desktop/mobile browser acceptance | PASS |
| build is deterministic | two identical production tree digests | PASS |
| static result is intact | 271 pages, 20,566 references, zero broken | PASS |
| local publication is reversible | timestamped backup and same-filesystem rollback tree | PASS |
| public readiness is not overstated | explicit local-only nonclaims | PASS |

The exact requested outcome is complete. Public server redirects, sitemap
publication, translated English/Arabic content, push, release and production
deployment remain separate future work rather than hidden incompleteness.
