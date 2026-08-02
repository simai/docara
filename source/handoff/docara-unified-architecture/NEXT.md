# Next action: deterministic R2 correction and retest

Independent reverse-outcome audit R2 returned `CORRECTION_REQUIRED`: two fresh
dist consumers of the same exact package produced different public metadata and
tree hashes. Local release-readiness and the deployment dossier are withdrawn
until a new candidate passes the complete retest.

## Current correction target

| Field | Exact value |
| --- | --- |
| Planned version | `2.0.0-rc.3` |
| Planned tag | `v2.0.0-rc.3` — not created |
| Future tag target | pending exact product source revision |
| ZIP SHA-256 | pending independent reproducible package build |
| Manifest SHA-256 | pending |
| Candidate tree SHA-256 | pending two-consumer equality proof |
| Current served tree SHA-256 | `b98ea2f66b733c5146360af68c1fe15b55aa099b33957fe52813772d93ce836f` |
| State | correction in progress; no valid deploy candidate |

The former source `56a2abf8…`, ZIP `04c18c95…` and tree `457790d4…` are
`superseded_after_determinism_audit`; they must not be tagged or deployed.

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

Complete the repository-only determinism correction at
`source/workflow/2026-08-02-docara-r2-determinism-correction.md`, create a new
exact unpublished candidate and repeat R2. A deployment decision is not yet an
authorized or meaningful next action.

No merge, push, tag, publication, release or live deployment is authorized.
