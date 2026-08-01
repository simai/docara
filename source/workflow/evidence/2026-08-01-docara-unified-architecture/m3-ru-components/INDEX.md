# M3 Russian components evidence index

Date: 2026-08-01

Goal status: in-progress

Recovery source:
`source/workflow/2026-08-01-docara-m3-ru-components-goal.md`

## Milestone evidence

| Milestone | Evidence | Verdict |
| --- | --- | --- |
| M3.1 | `M3.1-EXECUTION-CONTRACT.md`, `route-owner-inventory.json`, `baseline.json`, `browser-captures.json` | PASS |
| M3.2 | `M3.2-RUNTIME-ALERT.md` | PASS |
| M3.3 | `M3.3-CONTENT-MIGRATION.md` and per-family records | pending |
| M3.4 | `M3.4-DERIVED-VIEWS.md` | pending |
| M3.5 | `M3.5-LEGACY-RETIREMENT.md`, `old-to-new-map.json`, zero-reference records | pending |
| M3.6 | `M3.6-INTEGRATED-ACCEPTANCE.md`, browser matrix and reverse audit | pending |

## Current checkpoint

- parent SHA: `b14fe4e1e70a5465fe382bd5ced1de26cb65a315`;
- completed milestone: M3.1 durable execution contract and baseline;
- latest completed batch: 16, Diagram and Math family;
- current batch: 17, Code-from-file and HTML family;
- next evidence: per-family physical owners and route parity;
- blockers: none; federation/process gaps are documented in the workflow.

## Evidence rules

- each record binds parent and candidate;
- route/owner changes are explicit;
- commands and semantic results are recorded, not only exit codes;
- parity and zero-reference hashes include their reproduction method;
- browser screenshots remain evidence, never source of truth;
- rollback is the checkpoint commit or explicit old-to-new mapping;
- milestone PASS never implies goal PASS before the Completion Gate.

## Batch evidence

| Batch | Evidence | Verdict |
| --- | --- | --- |
| 03 | `batch-03-early-route-selection.md` | PASS |
| 04 | `batch-04-component-block-ir.md` | PASS |
| 05 | `batch-05-component-block-gateway.md` | PASS |
| 06 | `M3.2-RUNTIME-ALERT.md` | PASS |
| 07 | `batch-07-native-text-lists.md` | PASS |
| 08 | `batch-08-native-links-table.md` | PASS |
| 09 | `batch-09-native-code-footnotes.md` | PASS |
| 10 | `batch-10-details-backlinks.md` | PASS |
| 11 | `batch-11-banner-download.md` | PASS |
| 12 | `batch-12-button-icon-kbd.md` | PASS |
| 13 | `batch-13-card-hero.md` | PASS |
| 14 | `batch-14-grid-figure.md` | PASS |
| 15 | `batch-15-media-logos.md` | PASS |
| 16 | `batch-16-diagram-math.md` | PASS |
