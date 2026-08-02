# Goal 1-D evidence index

Date: 2026-08-02
Status: `ready_for_independent_audit`
Input revision: `c5ea85f8d25deff99b671486fdc4d1e820a86491`
Implementation revision: `44acc1ff91233fa78140222fcb0589bf55b65ca0`

The independent audit found a component-ID view branch in active production
code that the previous structural test did not scan. This contour supersedes
only the false-green generic-runtime claim in Goal 1-C evidence; accepted ABI,
cross-host and immutable-pin evidence remains valid and will be rerun.

Evidence:

- [G1D-R1-GENERIC-RUNTIME.md](G1D-R1-GENERIC-RUNTIME.md) — exact source
  correction, provider-local behavioral regression, structural scope, upstream
  blob hashes and rollback;
- [cross-host-report-v3.json](cross-host-report-v3.json) — unchanged fixture
  under Docara and exact SF5: `1/1`, 45 assertions, byte-identical HTML SHA-256
  `7133c5dcd44aa85f351a85c61c280aa883abd5cdb3c91206168ad63ada497b38`,
  empty stderr/warnings;
- [browser-results.json](browser-results.json) and `browser/` — representative
  Alert/Button and all four brand modes, console/overflow zero;
- `G1D-R2-INTEGRATED-RETEST.md` — exact final candidate verification, build
  hashes, public parity and hygiene.

Goal 1 is implementation-complete and audit-pending. This executor contour does
not mark it independently accepted. Goal 2 remains unstarted; its
`RegionCompositionResolver` shell allowlist is an explicit nonclaim.
