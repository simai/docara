# Workflow: browsable declarative preview

Date: 2026-07-20
Status: in_progress
Workflow ID: `2026-07-20-declarative-preview`
Process model: `general_delivery`
Current state: `validation_passed_pending_local_deployment`
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

Batch 5: staged local deployment and browser acceptance.

## Baseline

Starting revision `ab1507a`; accepted legacy renderer SHA-256:
`a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`.

## Result

The declarative content and shell plans are now browsable for all 45 authored
documentation pages. Full tests, static verification and two deterministic
production builds pass. The accepted legacy renderer remains the normal
publisher; local staged deployment is the only unfinished batch.
