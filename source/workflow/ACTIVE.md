# Workflow: declarative view composition contract

Date: 2026-07-20
Status: completed
Workflow ID: `2026-07-20-declarative-view-composition-contract`
Process model: `general_delivery`
Current state: `evidence_recorded`
Target state: `evidence_recorded`

## Goal

Develop the accepted region contract into reusable Layout, Section and Block
definitions with stable calls, slots, safe Framework View Trees, a complete
resolved plan and registered Blade leaves.

## Source Of Truth

- workflow:
  `source/workflow/2026-07-20-declarative-view-composition-contract.md`;
- evidence:
  `source/workflow/evidence/2026-07-20-declarative-view-composition-contract/`.

## Result

The bounded contract is accepted. Docara now resolves stable Section and Block
instances, named slots, registered Smart calls and safe Framework View Trees
into `docara.resolved_render_plan.v2`. The exact local build is published at
`https://docara.test/` and passed static and browser acceptance.

## Nonclaim

The accepted legacy renderer remains byte-identical and the primary publisher
until a later full-shell migration and acceptance goal.
