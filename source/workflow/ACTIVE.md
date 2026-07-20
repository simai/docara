# Workflow: declarative primary publisher migration

Date: 2026-07-20
Status: completed
Workflow ID: `2026-07-20-declarative-primary-publisher-migration`
Process model: `general_delivery`
Current state: `evidence_recorded`
Target state: `evidence_recorded`

## Goal

Replace the primary portable page publisher with the accepted declarative
`Layout -> Region -> Section -> Block -> Smart` chain while preserving current
URLs, capabilities, deterministic builds and explicit rollback.

## Source Of Truth

- workflow:
  `source/workflow/2026-07-20-declarative-primary-publisher-migration.md`;
- evidence:
  `source/workflow/evidence/2026-07-20-declarative-primary-publisher-migration/`.

## Result

Primary portable publication now uses
`Layout -> Region -> Section -> Block -> Smart` for authored pages, landing
pages and the generated component catalogue. Full PHP/static/deterministic and
desktop/mobile browser acceptance passed; the exact candidate is published on
the local test site with an explicit backup and legacy publisher rollback.

## Nonclaims

No push, merge, tag, release, production deploy or readiness claim is part of
this workflow.
