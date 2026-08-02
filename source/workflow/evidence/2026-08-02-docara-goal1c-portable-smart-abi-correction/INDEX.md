# Goal 1-C correction evidence index

Status: `BLOCKED_EXACT_SF5_HOST_CONTRACT`
Input revision: `531ccdbb3493a3109bfabe91bb3f2e00a17447ce`
Rejected Goal 1 implementation: `34496d49ce366f1108d2aed37c0adda35f6e5f58`
Branch: `codex/docara-unified-architecture`
Workflow: `source/workflow/2026-08-02-docara-goal1c-portable-smart-abi-correction.md`

The earlier Goal 1 evidence directory remains immutable historical evidence.
This contour supersedes its cross-host compatibility and integrated acceptance
claims after the independent `CORRECTION_REQUIRED` verdict.

## Checkpoints

| Batch | Evidence | Status |
| --- | --- | --- |
| G1C.0 | `G1C.0-RECOVERY-AND-ABI-INVENTORY.md` | pass |
| G1C.1 | `G1C.1-CROSS-HOST-ABI.md`, `cross-host-report.json` | blocker proven |
| G1C.2 | `G1C.2-PROVIDER-AND-ADMISSION.md` | pass within Docara |
| G1C.3 | `G1C.3-ID-LIST-RETIREMENT.md` | pass |
| G1C.4 | `G1C.4-INTEGRATED-RETEST.md`, `browser/` | partial pass; no candidate |

## Evidence policy

Every compatibility claim must include a reproducible command, exact upstream
revision, the unchanged artifact root, stdout/stderr, exit code, normalized
comparison and SHA-256. Integrated evidence is bound to the exact correction
implementation/evidence revisions. Goal 1 remains blocked on the recorded
exact-host contract decision and is neither accepted nor `audit_pending`.
