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
| M3.3 | per-family records through `batch-21-component-index.md` | PASS |
| M3.4 | `batch-21-component-index.md` | PASS |
| M3.5 | `batch-25-language-pack-retirement.md`, `batch-26-zero-page-assets.md`, `old-to-new-map.json` | PASS |
| M3.6 | `M3.6-INTEGRATED-ACCEPTANCE.md`, browser matrix and reverse audit | pending |

## Current checkpoint

- parent SHA: `b14fe4e1e70a5465fe382bd5ced1de26cb65a315`;
- completed milestone: M3.1 durable execution contract and baseline;
- latest completed batch: 21, physical component index and derived views;
- latest completed batch: 26, zero-page catalog asset retirement;
- current milestone: M3.6 integrated acceptance;
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
| 17 | `batch-17-code-html.md` | PASS |
| 18 | `batch-18-embed-example.md` | PASS |
| 19 | `batch-19-steps-tree.md` | PASS |
| 20 | `batch-20-tabs.md` | PASS |
| 21 | `batch-21-component-index.md` | PASS |
| 25 | `batch-25-language-pack-retirement.md`, `old-to-new-map.json` | PASS |
| 26 | `batch-26-zero-page-assets.md` | PASS |
| 27 | `batch-27-code-copy-localization.md` | PASS |
