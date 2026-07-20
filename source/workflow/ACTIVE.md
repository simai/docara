# Workflow: declarative rendering pipeline vertical slice

Date: 2026-07-20
Status: completed
Workflow ID: `2026-07-20-declarative-rendering-pipeline`
Process model: `general_delivery`
Current state: `evidence_recorded`
Target state: `evidence_recorded`

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

None. The vertical slice is complete.

## Baseline

Starting revision `922c00e`; accepted legacy renderer SHA-256:
`a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`.

## Result

Candidate `a29c1ab03462415879ec7383e6cf53e1dcccb1c2` passed the complete
requirement matrix. The new pipeline is shadow-only; legacy HTML remains
published. Public release, local publication, default-branch migration and
legacy renderer removal are not part of this result.
