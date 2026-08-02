# R1-C semantic correction evidence

Status: `executor_implementation_complete`; independent exact-artifact retest pending

Input revision: `3c491e5bfdf60c8227954b27d50dc050f058d71b`

| Checkpoint | Status | Evidence |
| --- | --- | --- |
| R1-C.1 correction governance | pass | commit `218ff1f` and debt register |
| R1-C.2 public language-pack retirement | pass | [runtime/source boundary](r1c-language-boundary.md) |
| R1-C.3 authoring/spec/runtime convergence | pass | [front matter, locales and actual architecture](r1c-authoring-runtime.md) |
| R1-C.4 semantic documentation gates | pass | [source and artifact semantic gates](r1c-semantic-gates.md) |
| R1-C.5 new exact candidate and update | implementation pass | [deterministic package, consumers and update](r1c-candidate-and-update.md) |
| R1-C.6 integrated retest | implementation pass; independent retest pending | [quality and exact browser matrix](r1c-browser-and-integrated.md) |

## Historical artifact classification

| Artifact | Source | SHA-256 | Status |
| --- | --- | --- | --- |
| M5 product candidate ZIP | `48751b8ca221f7185a72ce19188b1441aea93d2e` | `d12169b3c5080f219dada00cc976a758263cbc38ef845da11176ed7e34e8334a` | accepted for bounded M5 product evidence only; not current release target |
| R1 local ZIP | `8c0d14566837b6e6f4552d14c656ea14b202cd18` | `83afd355436284a0040390c88e1d125f3e5648932a23ff324ba9afa9af5eb561` | `superseded_after_audit`; immutable negative baseline |

## Current corrected candidate

| Source | ZIP SHA-256 | Manifest SHA-256 | Status |
| --- | --- | --- | --- |
| `56a2abf8bad05923f689141afc0bb045aa4d6734` | `04c18c95f2599905b1908fae3e326a9cf1ba47f29327ddd88465c4b4b792f753` | `d709d27cc226a3833c05ca62271525dfe48042d967940eaa9e8b9ac6a7185669` | executor implementation complete; independent exact-artifact retest pending |

Machine summary: [release-candidate.json](release-candidate.json). Historical
R1 evidence remains unchanged and must not be used to claim release readiness.
