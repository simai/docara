# Next action: explicit `docara.test` deployment decision

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
| Candidate tree SHA-256 | `457790d4cf212174b7ef34893f8ee3cfc11f8973022c8f28c18348e46f2a3bae` |
| Current served tree SHA-256 | `b98ea2f66b733c5146360af68c1fe15b55aa099b33957fe52813772d93ce836f` |
| State | accepted local release candidate; R2 dossier complete; live gate closed |

The branch HEAD contains later governance/evidence commits. It is not an
alternative artifact source and must not be tagged as though it produced the
accepted ZIP.

## Completed preparation

- recovery workflow:
  `source/workflow/2026-08-02-docara-r2-production-readiness.md`;
- evidence:
  `source/workflow/evidence/2026-08-02-docara-r2-production-readiness/INDEX.md`;
- exact-package production-like consumers, PHP 8.3/Linux, security,
  browser/HTTP, current/candidate delta and disposable cutover/rollback pass;
- deployment dossier:
  `source/workflow/evidence/2026-08-02-docara-r2-production-readiness/R2.5-DELTA-DEPLOYMENT-DOSSIER.md`;
- live boundary: `/Users/rim/Sites/docara.test` is read-only throughout R2.

## Historical artifacts

| Artifact | Source | ZIP SHA-256 | Status |
| --- | --- | --- | --- |
| M5 product candidate | `48751b8…` | `d12169b…` | historical bounded M5 evidence only |
| Rejected R1 candidate | `8c0d145…` | `83afd355…` | `superseded_after_audit`; immutable negative baseline |

## Next authorized action

Ask the user for exactly one decision: deploy or do not deploy the exact
candidate to `docara.test`. If approved, repeat the documented read-only
digest/TLS preflight, create the same-filesystem candidate, run the verified
cutover helper and execute the required smoke. Any threshold failure triggers
the documented exact rollback.

No merge, push, tag, publication, release or live deployment is authorized.
