# Next checkpoint: independent R1-C exact-artifact retest

R1-C executor correction is complete. The new candidate removes the retired
public language-pack contract, fixes packaged links, implements the accepted
front-matter/missing-page contract and passes the local package, consumer,
update, public-build and exact-browser matrices. Local release readiness still
requires a separate read-only tester verdict.

## Current correction target

- recovery workflow:
  `source/workflow/2026-08-02-docara-r1c-semantic-correction-goal.md`;
- debt register:
  `source/workflow/2026-08-02-docara-architecture-documentation-debt-register.md`;
- evidence index:
  `source/workflow/evidence/2026-08-02-docara-r1c-semantic-correction/INDEX.md`;
- current source: `56a2abf8bad05923f689141afc0bb045aa4d6734`;
- expected ZIP SHA-256:
  `04c18c95f2599905b1908fae3e326a9cf1ba47f29327ddd88465c4b4b792f753`;
- expected external manifest SHA-256:
  `d709d27cc226a3833c05ca62271525dfe48042d967940eaa9e8b9ac6a7185669`;
- current action: independently rebuild twice, verify the unpacked artifact,
  run fresh dist consumer/update/build/static/browser checks and issue a
  tester-owned verdict.

## Current candidate

| Artifact | Source revision | ZIP SHA-256 | Status |
| --- | --- | --- | --- |
| R1-C corrected candidate | `56a2abf8bad05923f689141afc0bb045aa4d6734` | `04c18c95f2599905b1908fae3e326a9cf1ba47f29327ddd88465c4b4b792f753` | current; executor implementation complete; independent retest pending |

## Historical artifacts

| Artifact | Source revision | ZIP SHA-256 | Status |
| --- | --- | --- | --- |
| M5 product candidate | `48751b8ca221f7185a72ce19188b1441aea93d2e` | `d12169b3c5080f219dada00cc976a758263cbc38ef845da11176ed7e34e8334a` | historical bounded M5 product evidence; not release target |
| R1 local candidate | `8c0d14566837b6e6f4552d14c656ea14b202cd18` | `83afd355436284a0040390c88e1d125f3e5648932a23ff324ba9afa9af5eb561` | `superseded_after_audit`; immutable negative baseline |

Do not select either historical artifact as the current release target. The
R1-C artifact above is the only current retest target, not a published release.

## Nonclaims

Independent R1-C acceptance, local release readiness, merge, push, tag,
publication, release, production deploy and complete non-Russian translations
are not authorized or claimed.
