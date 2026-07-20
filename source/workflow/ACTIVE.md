# Workflow: browsable declarative preview

Date: 2026-07-20
Status: completed
Workflow ID: `2026-07-20-declarative-preview`
Process model: `general_delivery`
Current state: `evidence_recorded`
Target state: `evidence_recorded`

## Goal

Publish a safe, browsable preview of the accepted declarative chain next to the
unchanged legacy site.

## Source Of Truth

- workflow:
  `source/workflow/2026-07-20-declarative-preview.md`;
- launch record:
  `source/workflow/2026-07-20-declarative-preview.launch.yaml`;
- evidence:
  `source/workflow/evidence/2026-07-20-declarative-preview/`.

## Current Batch

All batches completed.

## Baseline

Starting revision `ab1507a`; accepted legacy renderer SHA-256:
`a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`.

## Result

The declarative content and shell plans are browsable for all 45 authored
documentation pages at
`https://docara.test/_docara/declarative-preview/`. Full tests, static
verification, deterministic builds, staged deployment and desktop/mobile
browser acceptance pass. The accepted legacy renderer remains the normal
publisher.
