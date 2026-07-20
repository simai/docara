# Workflow: declarative region composition contract

Date: 2026-07-20
Status: completed
Workflow ID: `2026-07-20-region-composition-contract`
Process model: `general_delivery`
Current state: `evidence_recorded`
Target state: `evidence_recorded`
Parent track: `docara-consolidation`

## Goal

Make layout regions a real author-facing Docara contract instead of a
hard-coded compiler recipe. Site, section and page descriptors must be able to
inherit region configuration, enable or disable optional regions, select
trusted section and Smart composition, explain provenance, and render a
browsable demonstration without changing the accepted legacy publisher.

## Done When

- `docara.json`, inherited `section.json` and `<page>.page.json` accept one
  bounded `layout.regions` contract.
- Defaults describe `header`, `sidebar`, `main`, `outline` and `footer`.
- Optional regions can be disabled; required regions fail closed when disabled.
- Region sections and Smart components are selected only from registered,
  trusted definitions.
- Dynamic shell data is bound by a fixed source vocabulary rather than
  authored PHP, template paths or callbacks.
- The resolved page plan exposes exact inherited values and provenance.
- The declarative compiler consumes the resolved region contract instead of
  hard-coding the shell recipe.
- Disabled regions are absent from rendered markup.
- Documentation contains a concise configuration reference and a page-level
  demonstration that visibly changes the shell.
- Focused and full tests, deterministic build, static verification, staged
  local deployment and browser checks pass.

## Scope

Allowed:

- portable presentation schemas, configuration defaults and provenance;
- declarative layout/composition compiler and trusted renderer;
- registered Docara layout, section, block and Smart definitions;
- focused fixtures and tests;
- Docara documentation and declarative preview;
- workflow, evidence and project memory;
- reversible staged refresh of `/Users/rim/Sites/docara.test/build_production`.

Forbidden:

- arbitrary authored PHP, classes, callbacks or template paths;
- unregistered Framework or Smart component claims;
- changes to the accepted legacy renderer;
- switching the primary publisher to the declarative renderer;
- writes to Larena, Framework, Bitrix or other owner repositories;
- public push, merge, tag, release or production deployment;
- secrets, database changes or ServBay configuration changes.

## Contract Decisions

1. `layout.key` selects a registered layout definition.
2. `layout.regions.<region>.enabled` is the simple author-facing switch.
3. `sections` is an ordered list of registered section calls; each block names
   a registered block and Smart component.
4. Dynamic values use fixed `bind` identifiers such as `branding`,
   `navigation` and `outline`; arbitrary expressions are forbidden.
5. Layout defaults are part of portable configuration defaults, so ordinary
   projects do not need verbose region JSON.
6. Site -> section -> page inheritance and `$reset` use the existing
   deterministic merger and provenance map.
7. `main` remains required and receives the authored article body. A required
   region cannot be disabled.
8. The legacy page remains the primary publication target; the new behavior is
   demonstrated in the declarative preview until full-shell migration.

## Batches

| Batch | Work | Status |
| --- | --- | --- |
| 0 | Recover accepted baseline, route/process, goal and workflow | completed |
| 1 | Add schemas, defaults, inheritance and negative validation | completed |
| 2 | Resolve typed region composition in compiler and renderer | completed |
| 3 | Add authored demonstration and documentation | completed |
| 4 | Focused/full tests, deterministic build and static verification | completed |
| 5 | Staged local deployment and browser acceptance | completed |
| 6 | Evidence, project memory and goal closure | completed |

## Verification

- schema positive and negative matrices;
- site -> section -> page inheritance with provenance;
- required/optional region behavior;
- registered section/block/Smart allowlists and fixed bindings;
- Larena contract adapter parity;
- rendered markup assertions for enabled and disabled regions;
- legacy renderer digest regression;
- focused and full PHPUnit;
- two identical production builds;
- static build verifier;
- desktop and mobile browser scenarios on `docara.test`.

## Evidence

Root:
`source/workflow/evidence/2026-07-20-region-composition-contract/`

Required:

- `baseline.md`;
- `verification-summary.md`;
- `requirement-matrix.md`;
- `deployment.md`;
- `browser-acceptance.md`;
- `kaizen.md`;
- `acceptance.md`.

## Stop Conditions

- implementation requires arbitrary executable author configuration;
- an owner repository write is required;
- accepted legacy publication changes;
- unrelated overlapping worktree changes appear;
- local deployment lacks backup, staging, rollback or HTTP verification;
- destructive, secret, public release or production action is required.

## Result

- The author-facing `layout.regions` contract is implemented for site,
  section and page configuration with deterministic inheritance, reset and
  provenance.
- The compiler resolves only registered regions, sections, blocks, Smart
  components and fixed data bindings.
- Disabled optional regions are absent from rendered markup; disabling the
  required `main` region fails closed.
- Documentation and a visible demonstration are published locally:
  `https://docara.test/authoring/regions/` and
  `https://docara.test/_docara/declarative-preview/pages/authoring/regions/`.
- Full regression passed: 580 tests and 4,804 assertions.
- Two production builds were deterministic; static verification checked 115
  HTML pages and 11,256 local references with zero broken references.
- The local deployment is staged, backed up, reversible and browser-accepted
  on desktop and mobile.
- The legacy renderer remains byte-identical and remains the primary
  publisher. Full-shell migration, public release and production readiness
  are not claimed.
