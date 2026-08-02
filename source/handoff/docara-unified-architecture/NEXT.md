# Next checkpoint: R2 production-readiness dossier

Independent reverse-outcome audit accepted R1-C with verdict
`PASS_WITH_NOTES`. Local release-readiness is accepted for the exact artifact;
release, production and live cutover remain closed.

## One current candidate

| Field | Exact value |
| --- | --- |
| Planned version | `2.0.0-rc.2` |
| Planned tag | `v2.0.0-rc.2` — not created |
| Future tag target | `56a2abf8bad05923f689141afc0bb045aa4d6734` |
| ZIP SHA-256 | `04c18c95f2599905b1908fae3e326a9cf1ba47f29327ddd88465c4b4b792f753` |
| Manifest SHA-256 | `d709d27cc226a3833c05ca62271525dfe48042d967940eaa9e8b9ac6a7185669` |
| State | accepted local release candidate; production dossier active |

The branch HEAD contains later governance/evidence commits. It is not an
alternative artifact source and must not be tagged as though it produced the
accepted ZIP.

## Current work

- recovery workflow:
  `source/workflow/2026-08-02-docara-r2-production-readiness.md`;
- evidence:
  `source/workflow/evidence/2026-08-02-docara-r2-production-readiness/INDEX.md`;
- active stage: exact-package production-like consumers, compatibility,
  browser/HTTP, current/candidate delta and disposable cutover/rollback;
- live boundary: `/Users/rim/Sites/docara.test` is read-only throughout R2.

## Historical artifacts

| Artifact | Source | ZIP SHA-256 | Status |
| --- | --- | --- | --- |
| M5 product candidate | `48751b8…` | `d12169b…` | historical bounded M5 evidence only |
| Rejected R1 candidate | `8c0d145…` | `83afd355…` | `superseded_after_audit`; immutable negative baseline |

## Next authorized action

Continue R2 disposable/read-only checks. After R2 PASS, stop at one explicit
user decision: deploy or do not deploy the exact candidate to `docara.test`.

No merge, push, tag, publication, release or live deployment is authorized.
