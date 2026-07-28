# Workflow: declarative layout demonstrator

Date: 2026-07-21
Status: accepted
Workflow ID: `2026-07-21-declarative-layout-demonstrator`
Process model: `general_delivery`
Current state: `review_ready`
Target state: `review_ready`
Owner: `docara`
Companions: `dev`, `tester`, `browser`, `ux`, `docs`
Track ID: `docara-consolidation`
Memory decision: `skip`
Memory reason: current repository workflow and project memory are authoritative;
prior personal memory was used only for orientation and all mutable facts were
reverified from the live worktree and local site.

## Current Goal

Create a universal Docara demonstrator for declarative layouts. It must expose
separate examples for region enable/disable/composition, inherited settings,
layout presets and Smart components. Every example is built by the same
primary generator and exposes its exact Markdown and JSON sources, a live
result and invocation/configuration code without breaking the documentation
navigation.

## Final Outcome

An author opens one catalogue, selects a layout capability, inspects the real
generated result and copies its exact minimal Markdown/JSON. The catalogue and
all example results use the same declarative generator as the documentation.

## Primary User Outcome

A Docara author opens one catalogue, chooses a capability, sees the real
generated result, and can copy the exact minimal Markdown/JSON needed to
reproduce it without learning an internal PHP/template surface.

## Done When

- the public documentation page `/authoring/regions/` keeps normal navigation;
- a dedicated demonstrator catalogue is reachable from the documentation tree;
- every example has one canonical machine-readable descriptor and exact source
  files, not hand-maintained code duplicates;
- the catalogue and detail pages are produced by the primary declarative
  publisher;
- each detail page shows purpose, live result, Markdown source and JSON source;
- the result page is isolated from the documentation shell when an example
  intentionally disables regions or uses the landing preset;
- examples cover:
  - regions enabled and composed;
  - regions disabled without empty layout columns;
  - inherited section settings with a page override;
  - `docs` and `landing` presets;
  - at least two Smart components with parameters;
- disabled optional regions do not emit visible or space-consuming wrappers;
- descriptors and source paths fail closed on invalid schema, traversal,
  collision, missing source or unsupported component;
- portable install/build remains PHP-only and deterministic;
- static verification, focused tests, full regression and desktop/mobile
  browser acceptance pass;
- the accepted candidate is published only to `https://docara.test/` with a
  local rollback backup;
- no push, merge, tag, public release, production deploy or production
  readiness claim occurs.

## Architecture Decision

The demonstrator is a generated projection, not a second renderer:

```text
example descriptor + Markdown + JSON
  -> existing config/schema/parser pipeline
  -> existing Layout -> Region -> Section -> Block -> Smart compiler
  -> existing primary page publisher
  -> hidden isolated result route
  -> generated catalogue/detail route with live preview and exact source
```

The public explanatory pages never disable their own navigation. A result that
needs another shell is rendered at a dedicated hidden route and embedded by a
registered Docara Smart component. Templates remain presentation-only.

## Human-Centered Simplicity Contract

- Initial catalogue surface shows only example title, category and purpose.
- Detail pages show the live result first; source is grouped by file and remains
  directly discoverable.
- The descriptor is the only added coordination object. No database, editor,
  second page schema, dynamic server runtime or author-supplied template path is
  introduced.
- Safety, deterministic hashes, provenance, navigation, accessibility,
  responsive behavior and rollback are protected complexity and must remain.
- Machine review and independent tester verdict are stored under the evidence
  directory and validated by the canonical simplicity checker.

## Stages

1. Correct the declarative shell and establish the example contract.
2. Build the descriptor-driven catalogue and its seven source-backed examples.
3. Verify, publish locally, reverse-audit and close the workflow.

## Batches

- Batches 0–1: baseline and disabled-region correction.
- Batches 2–4: projector, example corpus, verifier and documentation.
- Batches 5–7: regression, deterministic build, local browser acceptance and
  closure.

| # | Batch | Evidence | Status |
| ---: | --- | --- | --- |
| 0 | Baseline, workflow, launch record and gates | clean HEAD, inventory, gate report | completed |
| 1 | Disabled-region correction and contract tests | focused layout/publisher tests | completed |
| 2 | Descriptor/schema/projector and preview Smart | unit and negative tests | completed |
| 3 | Example corpus and documentation navigation | generated routes/source parity | completed |
| 4 | Static verifier, docs and change notes | verifier and documentation review | completed |
| 5 | Full regression and deterministic builds | PHP suite, duplicate digest | completed |
| 6 | Local staged publication and browser matrix | backup, HTTP, desktop/mobile | completed |
| 7 | Reverse audit, simplicity verdict, memory and commit | Done When matrix, clean commit | completed |

## Evidence Plan

Evidence root:
`source/workflow/evidence/2026-07-21-declarative-layout-demonstrator/`

- `baseline.md`
- `requirements-matrix.md`
- `verification-summary.md`
- `browser-acceptance.md`
- `deployment.md`
- `human-centered-simplicity.json`
- `human-centered-simplicity-tester-verdict.json`
- `human-centered-simplicity-validator.json`
- `acceptance.md`

## Allowed Changes

- declarative definitions, rendering/view models and portable builder/projector;
- strict schemas and example fixtures;
- Docara product documentation and tests;
- workflow, evidence and project memory;
- local generated site and its local rollback backup.

## Forbidden Changes

- deletion or mutation of the byte-accepted legacy renderer;
- author-supplied PHP, Blade, HTML, callback or template paths;
- database/runtime CRUD for examples;
- writes to Framework/Larena/Bitrix owner repositories;
- push, merge, tag, release or production deployment;
- secrets in tracked files or evidence;
- readiness claims outside this local demonstrator goal.

## Stop Conditions

- accepted legacy renderer digest changes;
- example source escapes its registered root or follows a symlink;
- result routes collide with authored/generated routes;
- main documentation navigation regresses;
- deterministic build hashes diverge;
- failed candidate publication changes the accepted local destination;
- browser evidence contradicts static or machine evidence.

## Result

The demonstrator is accepted locally. Seven examples use the primary
declarative generator, expose exact Markdown/JSON, preserve documentation
navigation and pass full, deterministic, static and browser acceptance. The
accepted tree is published only to `docara.test`; no push, release or
production-readiness claim was made.
