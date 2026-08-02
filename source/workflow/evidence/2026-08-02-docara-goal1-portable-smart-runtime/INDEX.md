# Goal 1 evidence index

Status: `g1_5_pass_candidate`
Input revision: `313afa17e21df2299a6276d246cb4508c7ec00b5`
Branch: `codex/docara-unified-architecture`
Workflow: `source/workflow/2026-08-02-docara-goal1-portable-smart-runtime.md`
Rollback: reset is forbidden; revert individual G1 checkpoint commits in
reverse order from the exact accepted predecessor.

## Checkpoints

| Batch | Evidence | Status |
| --- | --- | --- |
| G1.0 | `G1.0-BASELINE-AND-PATH-MAP.md` | pass |
| G1.1 | `G1.1-SF5-CONTRACT.md` | pass |
| G1.2 | `G1.2-PROVIDERS-AND-SECURITY.md` | pass |
| G1.3 | `G1.3-GENERIC-RUNTIME.md` | pass |
| G1.4 | `G1.4-BUILTIN-MIGRATION.md` | pass |
| G1.5 | `G1.5-PROJECT-LOCAL-FIXTURE.md` | pass candidate |
| G1.6 | `G1.6-INTEGRATED-ACCEPTANCE.md` | pending |

## Evidence policy

Record exact command, revision, exit code, semantic outcome and limitations.
Bulky builds, Composer dependencies and browser profiles stay in disposable
locations outside the repository. Screenshots are evidence only, never source
of truth. No historical R2 artifact or `docara-new.test` result is reused as
fresh Goal 1 acceptance.
