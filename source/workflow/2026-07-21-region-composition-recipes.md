# Workflow: region composition recipes

Date: 2026-07-21
Status: completed
Workflow ID: `2026-07-21-region-composition-recipes`
Process model: `general_delivery`
Current state: `evidence_recorded`
Target state: `evidence_recorded`
Owner: `docara`
Companions: `sf5`, `dev`, `ux`, `tester`, `browser`, `docs`
Memory decision: `inject`
Memory reason: prior Docara generator and local publication decisions define
the compatibility boundary; all mutable runtime and repository facts were
reverified against the current baseline.
Memory context pack:
`evidence/2026-07-21-region-composition-recipes/personal-memory-context.md`
quality_controls: `human_centered_simplicity`
simplicity_review: `source/workflow/evidence/2026-07-21-region-composition-recipes/human-centered-simplicity.json`
simplicity_repository_refs: `repo://docara-consolidation`
simplicity_repository_baselines: `repo://docara-consolidation@5823e5b974ceb4e26e8e7973903e1cfc87f37ce0`
Track ID: `docara-consolidation`
Baseline HEAD: `5823e5b974ceb4e26e8e7973903e1cfc87f37ce0`
Legacy renderer SHA-256: `a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`

## Current Goal

Extend Docara's declarative model for practical interface composition. Create
source-backed recipes for a branded header, sidebar, aside and footer; show
regions populated by safe element blocks and Smart components; and demonstrate
inheritance and overrides at site, section and page levels. Every result uses
the primary generator, Simai Framework, exact Markdown/JSON sources,
responsive behavior and light/dark themes, with static and browser acceptance.

## Final Outcome

An author can assemble the documentation shell from registered declarative
building blocks, inspect the exact source and inheritance provenance in the
demonstrator, and reproduce branded responsive layouts without editing PHP or
template files.

## Done When

- header, sidebar, outline/aside and footer each have a practical generated
  recipe in the existing demonstrator;
- recipes show a combination of safe element blocks and registered Smart
  components where appropriate;
- site, section and page configuration layers visibly demonstrate inheritance,
  replacement and reset behavior;
- region sections and blocks use stable IDs and deterministic ordering;
- authored configuration cannot select PHP, Blade, callbacks, scripts,
  arbitrary HTML strings or filesystem template paths;
- every recipe exposes its exact Markdown and JSON sources and generated live
  result through the primary generator;
- recipes use registered Simai Framework utilities/components and preserve the
  pinned Framework compatibility contract;
- generated pages are usable at desktop and mobile widths and in light and
  dark themes;
- schemas, negative tests, static verifier, full regression and deterministic
  duplicate builds pass;
- accepted candidate is published only to `https://docara.test/` with a local
  rollback backup;
- no push, merge, tag, public release, production deployment or production
  readiness claim occurs.

## Architecture Decision

```text
docara.json -> section.json -> page.page.json
  -> deterministic JSON merge and provenance
  -> layout.regions.<region>.sections[]
  -> registered section + authored safe block calls
  -> registered Block and Smart definitions
  -> safe View Tree + Simai Framework utility registry
  -> primary declarative publisher
```

Authored element blocks are structured data with a constrained semantic tag,
text/link data and validated Framework utilities. Raw HTML, event handlers,
scripts, styles and author-selected templates remain forbidden. Registered
Smart components stay the preferred implementation for behavior and reusable
product UI.

## Graph Gap

The federation resolver selected `fill` after matching the word “population”,
although the task changes the Docara generator and the candidate ranking gave
Docara the strongest domain signal. Raw owner sources are therefore used:
`docara` owns the generator, `sf5` owns Framework frontend contracts, `ux`
reviews composition, and `tester` gates acceptance.

## Human-Centered Simplicity Contract

- The existing demonstrator remains the single catalogue.
- Each detail keeps the live result first and exact sources second.
- Authors choose registered blocks and Smart components; implementation-only
  renderer/template details stay hidden.
- No database, visual editor, second renderer or dynamic server runtime is
  introduced.
- Safety, provenance, deterministic output, accessibility, responsive layout,
  theme support and rollback are protected complexity.
- Machine review, independent tester verdict and canonical simplicity
  validation are stored under the evidence root.

## Stages

- [completed] Define and secure the declarative shell composition contract.
- [completed] Add exact-source recipes and inheritance/provenance examples.
- [completed] Verify, publish locally and record acceptance evidence.

## Batches

- [completed] Baseline, architecture inventory and workflow.
- [completed] Region, section and block composition contract.
- [completed] Branded shell definitions and primary-generator recipes.
- [completed] Inheritance, reset and provenance demonstrator.
- [completed] Documentation, static verifier and negative gates.
- [completed] Full regression and deterministic builds.
- [completed] Local publication and browser matrix.
- [completed] Reverse audit, simplicity acceptance, memory and commit handoff.

## Batch Ledger

| # | Batch | Evidence | Status |
| ---: | --- | --- | --- |
| 0 | Baseline, architecture inventory and workflow | baseline and digest | completed |
| 1 | Region/section/block composition contract | schemas and focused tests | completed |
| 2 | Branded shell definitions and primary-generator recipes | source parity and generated routes | completed |
| 3 | Inheritance, reset and provenance demonstrator | site/section/page fixtures | completed |
| 4 | Documentation, static verifier and negative gates | verifier and docs review | completed |
| 5 | Full regression and deterministic builds | suite and duplicate digest | completed |
| 6 | Local publication and browser matrix | rollback, desktop/mobile/themes | completed |
| 7 | Reverse audit, simplicity acceptance, memory and commit | Done When matrix | completed |

## Result

The primary generator now accepts a registered `docara.shell` section with
validated `shell.element` and `shell.smart` blocks. Five source-backed recipes
demonstrate header, sidebar, aside, footer and site/section/page inheritance in
the existing catalogue. All 595 tests and 4,947 assertions pass; two clean
builds and the served local site have the same digest; the static verifier and
desktop/mobile light/dark browser matrix pass; the legacy renderer digest is
unchanged. The accepted result is published only to `https://docara.test/`
with a local rollback backup. No push, release or production claim occurred.

## Evidence Plan

Evidence root:
`source/workflow/evidence/2026-07-21-region-composition-recipes/`

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

- declarative schemas, registered definitions, compiler and safe render plans;
- demonstrator descriptors, exact source fixtures and portable documentation;
- focused/full tests, static verifier, workflow evidence and project memory;
- local generated site and rollback backup.

## Forbidden Changes

- mutation of the byte-accepted legacy renderer;
- arbitrary authored HTML/PHP/Blade/callback/template/file paths;
- changes to Framework, Larena or Bitrix owner repositories;
- database or live server CRUD;
- push, merge, tag, release or production deployment;
- secrets or unsupported readiness claims.

## Stop Conditions

- accepted legacy renderer digest changes;
- authored input gains executable or unregistered rendering surfaces;
- inherited configuration loses deterministic provenance;
- exact sources differ from generated receipts;
- build digests diverge;
- failed publication changes the accepted local destination;
- browser evidence contradicts static evidence.
