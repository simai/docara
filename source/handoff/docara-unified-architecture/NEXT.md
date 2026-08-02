# Next checkpoint: R1-C semantic correction

Independent audit rejected local release readiness because the exact R1 ZIP
contained broken README links and the retired public language-pack contract.
The current target is R1-C; no corrected candidate exists yet.

## Current correction target

- recovery workflow:
  `source/workflow/2026-08-02-docara-r1c-semantic-correction-goal.md`;
- debt register:
  `source/workflow/2026-08-02-docara-architecture-documentation-debt-register.md`;
- evidence index:
  `source/workflow/evidence/2026-08-02-docara-r1c-semantic-correction/INDEX.md`;
- current action: remove public `language_pack` schema/config/runtime/starter
  surfaces, then converge public docs/spec/tests before packaging;
- completion requires a new immutable source SHA, ZIP SHA and independent
  exact-artifact retest.

## Historical artifacts

| Artifact | Source revision | ZIP SHA-256 | Status |
| --- | --- | --- | --- |
| M5 product candidate | `48751b8ca221f7185a72ce19188b1441aea93d2e` | `d12169b3c5080f219dada00cc976a758263cbc38ef845da11176ed7e34e8334a` | historical bounded M5 product evidence; not release target |
| R1 local candidate | `8c0d14566837b6e6f4552d14c656ea14b202cd18` | `83afd355436284a0040390c88e1d125f3e5648932a23ff324ba9afa9af5eb561` | `superseded_after_audit`; immutable negative baseline |

Do not select either historical artifact as the current release target.

## Nonclaims

Local release readiness, merge, push, tag, publication, release, production
deploy and complete non-Russian translations are not authorized or claimed.
