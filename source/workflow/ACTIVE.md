# Workflow: declarative rendering pipeline vertical slice

Date: 2026-07-20
Status: acceptance_pending
Workflow ID: `2026-07-20-declarative-rendering-pipeline`
Process model: `general_delivery`
Current state: `review_ready`
Target state: `review_ready`

## Goal

Implement the first Docara vertical slice of the shared declarative rendering
pipeline intended for later Larena consumption.

## Source Of Truth

- workflow:
  `source/workflow/2026-07-20-declarative-rendering-pipeline.md`;
- launch record:
  `source/workflow/2026-07-20-declarative-rendering-pipeline.launch.yaml`;
- evidence:
  `source/workflow/evidence/2026-07-20-declarative-rendering-pipeline/`.

## Current Batch

Batch 7: separated reverse-outcome acceptance against the immutable candidate.

## Baseline

Starting revision `922c00e`; accepted legacy renderer SHA-256:
`a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`.

## Result

Implementation and regression verification are complete. The new pipeline is
shadow-only; legacy HTML remains published. Public release, local publication,
default-branch migration and legacy renderer removal are not part of this
candidate.
