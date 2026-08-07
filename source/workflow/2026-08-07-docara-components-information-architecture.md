# Workflow: component catalogue information architecture

Date: 2026-08-07

Status: `complete_local_validation`

## Current router

Status: `local_framework_runtime_ready_for_independent_audit`

Current stage: `docara.stage.lfr.local_framework_runtime`

Current batch: `docara.batch.lfr.integrated_retest`

Current next action: `independent_local_framework_runtime_audit`

Next roadmap goal: `docara.stage.lfr.local_framework_runtime` (`audit_pending`, authorized=`true`)

## Goal

Make `/ru/components/` a catalogue of actual component/reference pages only.
Move the authoring-model explanations out of the component namespace, preserve
old inbound URLs with deterministic redirects, and present the catalogue as two
Retype-inspired tables with six clear user-facing categories.

## Done When

- `/ru/components/` contains two three-column tables (six categories total)
  and exactly the 31 current component/reference links;
- the six former entry-point pages and the syntax overview no longer own routes
  below `/ru/components/`;
- one concise `/ru/start/component-model/` page explains Markdown, inline,
  blocks, containers, Framework and project extensions from source-backed
  contracts;
- all seven old URLs redirect to that canonical overview;
- catalog, redirects, navigation, search and static verification pass;
- desktop and mobile browser checks show readable tables, no page overflow,
  working links and clean console/network;
- `docara-new.test` is rebuilt and atomically switched with rollback evidence;
- the tracked worktree is clean and no external owner/release action occurs.

## Constraints

- preserve one physical Markdown prose owner per public page;
- no second catalog/runtime, component-ID dispatch or arbitrary HTML/CSS;
- do not alter admitted component/runtime semantics;
- do not touch `docara.test`, external owner repositories, release, tag or push;
- old public URLs must remain navigable through the existing redirect contract.

## Batch Plan

| Batch | Work | Verification | Status |
| --- | --- | --- | --- |
| IA | Freeze component inventory and category map | 31 unique actual routes, no overview route in catalogue | complete |
| Content | Build grouped index and consolidated start overview | focused documentation/catalog tests | complete |
| Routes | Add seven exact redirects and retire old owners | redirect receipt/static verifier | complete |
| Acceptance | Full/full/single, browser and local-site cutover | hashes, HTTP/browser, rollback | complete |
| Handoff | Evidence and current router synchronization | context/diff/clean status | complete |

## UX Decision

The smallest complete model is two tables with three category columns each,
matching the scan pattern of the referenced Retype catalogue. A single table
with six columns would be too dense at documentation content width and would
degrade mobile readability. The former seven explanatory routes are merged
into one discoverable start-page overview; redirect pages are retained
protective complexity for inbound links.

## Current Next

Independent read-only review may verify product candidate `75143bc9b6e978a167a20f87d5a26c469e0b415e`,
the evidence index and the authorized local validation result. No release or
production action follows from this local cutover.
