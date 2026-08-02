# G1C.5 — repeated exact-host blocker audit

Status: `BLOCKED_EXTERNAL_CONTRACT`
Candidate parent: `1d731ac7e5991a5b41ff05e728665f130e71d80f`
Environment: macOS, PHP 8.4.20

This is the third consecutive Goal turn in which the same explicit stop
condition remains authoritative. No new immutable SF5 Smart runtime revision is
available in the inspected repository history: the only commit touching
`local/modules/simai.main/lib/UI/Smart.php` is still
`d6f90bba6a9a2f30ac41075d62cf51f1014b7e78`.

## Fresh proof

- all five files pinned by `resources/contracts/sf5/smart/v1/source.json`
  match their committed upstream blobs byte-for-byte;
- `vendor/bin/phpunit --filter Sf5CrossHostSmartCompatibilityTest --testdox`:
  2 tests, 83 assertions, PASS;
- the unchanged tracked artifact still renders title/text under both hosts but
  exact SF5 loses the selected view record and the public render shortcut does
  not forward `slot` as a node field;
- `cross-host-report.json` remains byte-identical with SHA-256
  `c2ff49b0234188aecb1521eeb329e143a5da512d2912994ddfdd3918b8db81e0`;
- the bounded correction patch remains a proof artifact only, with SHA-256
  `1baa8a37d6ddebeb0378593ff7d2706f61cc12f45e0cd39821aca09d2536faf8`.

## Decision boundary

Docara cannot create a compliant `audit_pending` candidate without either a
new exact SF5 revision containing the bounded host fix or an explicit decision
to reduce the accepted portable context. Applying the patch to the external
Framework repository is forbidden. Goal 2 remains unstarted.

The graph Goal and Smart Gateway feature readiness were corrected from stale
`audit_pending` wording to `goal1c_blocked_external_sf5_contract` so the
machine-readable state matches the workflow, stage, batch and handoff.
