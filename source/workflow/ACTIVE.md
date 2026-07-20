# Workflow: Docara legacy replacement readiness

Date: 2026-07-20
Status: completed
Workflow ID: `2026-07-20-docara-legacy-replacement-readiness`
Process model: `full_qa`
Current state: `evidence_recorded`
Target state: `evidence_recorded`

## Goal

Make portable Docara replacement-ready relative to legacy while keeping public
release, default-branch migration and repository retirement out of scope.

## Source Of Truth

- workflow:
  `source/workflow/2026-07-20-docara-legacy-replacement-readiness.md`;
- launch record:
  `source/workflow/2026-07-20-docara-legacy-replacement-readiness.launch.yaml`;
- evidence:
  `source/workflow/evidence/2026-07-20-docara-legacy-replacement-readiness/`.

## Current Batch

None. The replacement-readiness Goal is complete.

## Baseline

Immutable candidate `de87bdef224d518d1c707286d4640be0238d34bc`
and the served `docara.test` build remain the accepted regression baseline.
This Goal adds replacement-ready contracts and comparative evidence; it does
not reinterpret the previous local-product acceptance.

## Accepted Candidate

- immutable candidate:
  `2640503ba14913aa83bc3b4343c86966a807e29f`;
- canonical build digest:
  `a16d61252837c8d23102e2285a948d7a81c513150080f09b2e9095c31ba475f4`;
- full PHPUnit: 548 tests / 4,474 assertions;
- two deterministic builds: 66 HTML pages, 6,033 references, zero broken;
- technical and HCS: `PASS`;
- root browser: `PASS`;
- independent UX/design: `PASS_WITH_NOTES`, no blockers;
- local publication, served digest and browser proof: `PASS`.

## Result

Portable Docara is replacement-ready for the accepted local contour and is
served at `https://docara.test/`. Public release, default-branch migration and
repository retirement are not started.
