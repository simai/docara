# G1C-R1 — exact repin and cross-host regression

Status: PASS

The source manifest pins adapter
`b3cdff87563ff78e7eddf044048a4b298fc69036`. Fresh `git show
<pin>:<path>` output matched every committed hash:

| Blob | SHA-256 |
| --- | --- |
| manifest schema | `9d65a9b3d63567ef8a12dd43f5c3e24913e2659105b088778dc50476a9578037` |
| view schema | `f7592ddd3c1fabf8ed9a6f32984f8745f0e4f031b50ab1b15617f093ab26fdc` |
| preset schema | `cbaa993e005a710a79a0dce4c2cd41063d8fc8da6cd4b01b9ea1ee6d039cea5c` |
| runtime Smart.php | `5052fad560faf71766a52ed2402266d8ef5f64c3467b6d4294b9763a766fae9a` |
| runtime proof | `bf7276a9fff990b40047d9155c5ba4c7b24fc44359d87e1c95125f3263663221` |

Canonical identity is `sf.smart_artifact_abi` / `1.0.0` /
`sf-smart-artifact-abi-v1`. `sf5.smart.artifact.v1` is only
`storage_compatibility_alias`.

Reproduction:

```bash
DOCARA_SF5_SOURCE_REPO=/Users/rim/Git/.worktrees/bx-simai-main-portable-smart-abi-v1 \
DOCARA_SF5_CROSS_HOST_REPORT=source/workflow/evidence/2026-08-02-docara-goal1c-portable-smart-abi-resume/cross-host-report-v3.json \
vendor/bin/phpunit --filter Sf5CrossHostSmartCompatibilityTest
```

Result: 1 test, 45 assertions, PASS. The unchanged fixture tree hash is
`eb6fd28f295c360fa80375beb21aba634c14d2466699ca55509b64ac39f2d058`.
Docara and exact SF5 both selected view `default`, preset `compact` and slot
`content`; hydration and strategies match. Both HTML SHA-256 values are
`7133c5dcd44aa85f351a85c61c280aa883abd5cdb3c91206168ad63ada497b38`.
stderr, warnings and blockers are empty. Report SHA-256:
`79372b7aa5decb9a1f12c07b5f02604ad83c9cedd75661890457d75761b521b4`.

The blocker-only runtime patch was removed after zero-reference verification;
the historical failure text and hashes remain in the prior evidence contour.
