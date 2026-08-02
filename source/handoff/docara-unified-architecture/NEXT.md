# Next action: independent Goal 1 reverse-outcome audit

Goal 1-D correction is complete at implementation candidate
`44acc1ff91233fa78140222fcb0589bf55b65ca0`. Generic artifact/provider data now
resolve explicit view, preset-selected view and `default`; the active central
source structural gate includes `DeclarativePageCompiler` and rejects known
component IDs or the retired `defaultCompositeView` helper.

Review the reproducible commands and hashes in
`source/workflow/evidence/2026-08-02-docara-goal1d-generic-smart-view-correction/INDEX.md`.
Goal 1 is `ready_for_independent_audit`, not accepted. Goal 2/3 remain
unstarted. No live/release action is authorized.

## Historical deployment decision (parked, not executable)

The deterministic R2 correction and complete disposable retest pass. The live
site is unchanged. There is now one exact unpublished candidate and one
remaining production decision.

The same exact candidate is deployed for user validation at
`https://docara-new.test`. Its 103/103 route smoke, static verification and
representative browser interactions pass. This test deployment does not open
the separate `docara.test` production gate.

## Historical rc.3 release baseline

| Field | Exact value |
| --- | --- |
| Planned version | `2.0.0-rc.3` |
| Planned tag | `v2.0.0-rc.3` — not created |
| Future tag target | `be0ba2db5254e468c7c014016ade02e8b4f3f16c` |
| ZIP SHA-256 | `630d971e94a1222624304a3a5c2a7791586c0b7866ede5b8f3506c93bdebadc0` |
| Manifest SHA-256 | `0d0c280fc93824d76bafb703a5be8b70cf3cf34128e94ac4bf6906e3648a35af` |
| Candidate tree SHA-256 | `425da363fc51d33d2c5b42577980f4ca4603b83814440dbfb06fe419b4cade46` |
| Current served tree SHA-256 | `b98ea2f66b733c5146360af68c1fe15b55aa099b33957fe52813772d93ce836f` |
| State | historical local release baseline; not the current architecture candidate or next action |

The former source `56a2abf8…`, ZIP `04c18c95…` and tree `457790d4…` are
`superseded_after_determinism_audit`; they must not be tagged or deployed.

## Historical R2 proof

- correction workflow:
  `source/workflow/2026-08-02-docara-r2-determinism-correction.md`;
- exact evidence:
  `source/workflow/evidence/2026-08-02-docara-r2-determinism-correction/INDEX.md`;
- two clean-clone packages and two dist consumers are byte-identical;
- macOS PHP 8.4/8.3 and Linux PHP 8.3, security, browser/HTTP,
  current/candidate delta and disposable cutover/rollback pass;
- live boundary: `/Users/rim/Sites/docara.test` is read-only throughout R2.

## Historical artifacts

| Artifact | Source | ZIP SHA-256 | Status |
| --- | --- | --- | --- |
| M5 product candidate | `48751b8…` | `d12169b…` | historical bounded M5 evidence only |
| Rejected R1 candidate | `8c0d145…` | `83afd355…` | `superseded_after_audit`; immutable negative baseline |
| Rejected rc.2 candidate | `56a2abf8…` | `04c18c95…` | `superseded_after_determinism_audit`; immutable negative baseline |

## Parked live action

The previous next action was to choose `deploy` or `do not deploy` for the rc.3
candidate. It remains historical and unauthorized while Goal 1 changes product
source. Any future deployment requires a new exact candidate and fresh dossier.

No merge, push, tag, publication, release or live deployment is authorized.
