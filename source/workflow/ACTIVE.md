# Workflow: Docara legacy replacement readiness

Date: 2026-07-20
Status: active
Workflow ID: `2026-07-20-docara-legacy-replacement-readiness`
Process model: `full_qa`
Current state: `tests_recorded`
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

Batch 7: immutable candidate, exact-archive regression, comparative browser/HCS
and independent tester acceptance.

## Baseline

Immutable candidate `de87bdef224d518d1c707286d4640be0238d34bc`
and the served `docara.test` build remain the accepted regression baseline.
This Goal adds replacement-ready contracts and comparative evidence; it does
not reinterpret the previous local-product acceptance.

## Verified Working-Tree Result

- Batches 0–6 completed;
- full PHPUnit: 547 tests / 4,449 assertions;
- two deterministic builds: 66 HTML pages, 6,033 references, zero broken;
- working build digest:
  `c1b105efeb75e7688573e18a5aac6a90b9eac386e02c2f5e1d4e4ec33ac0b9e9`;
- exact candidate and local publication still pending.
