# Workflow: Docara product completion

Date: 2026-07-19
Status: in-progress
Process model: `general_delivery`
Current state: `planned`
Target state: `launch_record_ready`
Project mode: `productization`
Requested level: `goal`
Recommended level: `goal`
Scale reason: the requested result spans five product stages, several UI and
documentation surfaces, local runtime publication, and one final exact-candidate
acceptance gate.
Owner: `docara`
Coordinator: `teamlead`
Companions: `docs`, `sf5`, `ux`, `designer`, `tester`, `ops`
Memory decision: `inject`
Memory reason: prior Docara menu-repair evidence defines a regression contour;
all mutable repository, Framework and runtime facts will be reverified from the
current exact baseline before implementation.
Memory context pack:
`evidence/2026-07-19-docara-product-completion/personal-memory-context.md`

## Track

Track ID: `docara-product-ui-restoration`

## Track Goal

Restore Docara as a complete, attractive and configurable documentation and
landing product on the portable PHP-only engine and pinned Simai Framework,
without reviving the legacy frontend runtime or creating a second content
language.

## Current Goal

Довести Docara до целостного локального продукта: ясное многоуровневое меню и active trail, поиск и reading context, адаптивный landing, полный практический каталог typed Markdown/Simai Framework компонентов, полная документация и независимая приёмка одного exact candidate с безопасной публикацией на `docara.test`.

## Final Outcome

From an empty directory, a user can configure and build a branded responsive
Docara documentation site or landing page through one inherited JSON shell
contract and Markdown content. Readers can orient themselves in a deep page
tree, find content, scan headings, move between pages and adjust reading
preferences. Authors have a live catalogue of every supported typed component,
including syntax, parameters, states and responsive examples. The portable
production build remains PHP-only and consumes exact pinned Simai Framework
assets.

## Done When

- four or more menu levels have visible structural indentation and connector
  or spacing hierarchy without relying on color alone;
- the active page, its current section and all active ancestors are visually
  distinguishable in both expanded and collapsed branches on desktop and
  mobile, with keyboard/focus acceptance;
- local search is deterministic, keyboard-accessible and built from published
  content without an external runtime service;
- the right heading TOC, breadcrumbs and previous/next links are derived from
  one resolved page/tree contract and remain useful on responsive layouts;
- reading settings expose only useful controls, retain strong defaults and work
  in light and dark themes;
- the `landing` preset has a real responsive demo with hero, feature/content
  composition and primary action, built from Framework utilities/components;
- a machine-readable capability matrix has zero unexplained `required_missing`
  rows, and every supported typed Markdown component has a live demo, call
  syntax, parameters, states, limitations and source/Framework mapping;
- every new configuration field has a strict schema, negative tests,
  inheritance/reset behavior, migration notes and public documentation;
- Framework locks contain no moving reference and no undocumented custom
  primitive duplicates an existing Framework utility/component/Smart-component;
- documentation has a clear beginner path, configuration/layout guide,
  authoring/component reference, migration guide and troubleshooting path;
- one exact candidate passes deterministic/full tests, static-link checks,
  keyboard, responsive, light/dark, browser, UX, designer, complete-diff
  Human-Centered Simplicity and independent exact-archive tester acceptance;
- the verified build is published to `docara.test` through staging with a
  timestamped rollback copy and matching source/staging/served digests.

## Non-Goals

- public package or website release;
- merge, push, tag or migration of default branches;
- archive or deletion of `docara-mix`, `docara-template` or other repositories;
- a second canonical component registry or a second JSON content language;
- consuming `main`, `master`, `latest` or an unpublished Framework asset;
- turning standalone Docara into Larena.

## Product Feedback Driving The Goal

The accepted first vertical preserves the semantic tree, but the current
presentation still fails two reader-orientation jobs:

1. nested items look visually flat, so the reader cannot quickly see which
   item belongs to which section;
2. when a child page is active, its parent section and ancestor path are not
   visually apparent enough, especially after branch collapse.

The old Docara is a behavioral reference for clarity, not a runtime to restore.
Official documentation platforms will be reviewed for hierarchy, active trail,
search, TOC, responsive navigation, settings, landing and component-reference
patterns. Their implementation stacks are not dependencies.

## Product Principles

1. Use one portable JSON shell/configuration contract plus Markdown content.
2. Use Framework utilities for layout, components for presentation and local
   interaction, and Smart-components only for state/data/side effects.
3. Prefer an existing pinned Framework primitive; record a framework gap before
   creating a Docara-specific adapter, and do not silently create a generic
   duplicate.
4. Preserve native links, headings and disclosure semantics; visual hierarchy
   must support, not replace, semantic hierarchy.
5. Simplicity means clear defaults and progressive disclosure, not removal of
   navigation, discovery or reading context.
6. Keep templates presentation-only and keep data preparation in builders,
   view models or renderer services.
7. Keep the portable production build PHP-only.

## Milestones

### Milestone 1 — Navigation clarity

Result: the existing semantic tree becomes visually and interactively clear.

Batches:

- Batch 0: exact baseline, workflow/launch, current defect reproduction,
  official reference review, Framework inventory and acceptance contract;
- Batch 1: visible level hierarchy, active page/section/ancestor states,
  collapsed-active indication and responsive/keyboard regression coverage.

### Milestone 2 — Documentation discovery and reading context

Result: readers can find, scan and move through documentation.

Batches:

- Batch 2: deterministic local search index, search UI and keyboard behavior;
- Batch 3: heading extraction, right TOC, breadcrumbs and previous/next;
- Batch 4: simple reading settings and responsive integration.

### Milestone 3 — Landing and component authoring system

Result: Docara can build a real landing and authors can discover every
supported typed component.

Batches:

- Batch 5: landing layout recipe and responsive demonstration;
- Batch 6: capability matrix and typed component families;
- Batch 7: universal live component catalogue with syntax, parameters, states
  and examples.

### Milestone 4 — Complete documentation

Result: configuration, layouts, authoring, components and migration are usable
without reconstructing behavior from source code.

Batches:

- Batch 8: documentation map, beginner/configuration/layout/authoring/migration
  and troubleshooting paths, with link/build checks.

### Milestone 5 — Unified acceptance and local publication

Result: one exact complete product candidate is independently accepted and
served locally with rollback evidence.

Batches:

- Batch 9: deterministic/full regression, complete-diff HCS, browser/UX/design
  matrix, exact-archive tester verdict and safe `docara.test` publication.

## Stages

1. Navigation clarity.
2. Documentation discovery and reading context.
3. Landing and component authoring system.
4. Complete user/developer documentation.
5. Unified exact-candidate acceptance and local publication.

## Batches

1. Batch 0: baseline, defect reproduction, reference/Framework/docs inventory
   and test-first menu contract.
2. Batch 1: menu hierarchy and active-trail correction.
3. Batch 2: deterministic local search.
4. Batch 3: right TOC, breadcrumbs and previous/next.
5. Batch 4: simple reading settings and responsive integration.
6. Batch 5: landing recipe and responsive demonstration.
7. Batch 6: component capability matrix and typed component families.
8. Batch 7: live component catalogue and examples.
9. Batch 8: complete documentation and migration/troubleshooting paths.
10. Batch 9: full regression, browser/UX/design/HCS/tester acceptance and local
    publication with rollback.

## Completion Gate

Do not complete the Goal until:

- every required row in the product capability matrix is `pass` or an explicit
  user-approved non-goal;
- menu hierarchy and active-trail scenarios pass on desktop and mobile;
- every supported component has a live catalogue entry and verified example;
- documentation link/build checks have zero broken required references;
- the full goal requirement matrix has zero `missing` and zero unexplained
  `partial` rows;
- an independent tester returns `PASS` for the same exact candidate accepted by
  UX/design/browser/HCS;
- source, staging and served build digests match and rollback is preserved.

## Current Remaining

- Milestones remaining: 4 of 5.
- Batches remaining: 8 of 10.
- Active batch: Batch 2.
- Goal status: `in-progress`.

## Do Not Complete Until

- a setup, inventory or demonstrator batch is not mistaken for the Goal;
- HCS inventory covers the complete candidate diff rather than selected files;
- browser checks include a cold mobile load, same-tab desktop transition,
  keyboard path, both themes and no-overflow assertions;
- no release/default-branch/archive claim is bundled into local product
  acceptance.

## Owner Map

| Surface | Owner | Role |
| --- | --- | --- |
| Goal, stages, integration | `teamlead` | coordinator |
| Portable generator, schemas, renderer | `docara` | owner / implementer |
| Documentation architecture and content | `docs` | author / gatekeeper |
| Framework mapping and exact contracts | `sf5` | consulted owner |
| IA, menu, responsive/accessibility | `ux` | author / reviewer |
| Visual hierarchy and product quality | `designer` | reviewer |
| Automated/browser/exact acceptance | `tester` | gatekeeper |
| Local publication, backup, rollback | `ops` | runtime gatekeeper |

## Workstream Register

| ID | Role | Scope | Write scope | Status | Integration gate |
| --- | --- | --- | --- | --- | --- |
| `WS-CORE-01` | Docara owner | workflow, current renderer/menu reproduction and critical path | this worktree | Batch 1 implementation complete, gates pending | root integration review |
| `WS-UX-01` | UX/designer review | official-platform comparison and bounded menu/product shell gate | none | Batch 1 PASS | `batch-1-ux-design-verdict.md` |
| `WS-SF5-01` | `/root/smart_component_inventory` | pinned utilities/components/Smart-components for menu, search, TOC, settings, landing and catalogue | none | completed | `framework-building-block-map.md` |
| `WS-DOCS-01` | `/root/docs_catalog_audit` | Retype-style component capability inventory and Docara docs coverage/map | none | completed | `product-capability-matrix.yaml` and catalogue/docs decision |
| `WS-SEARCH-02` | Docara owner | deterministic local search, schema, runtime, verifier and documentation | this worktree | implementation complete, exact gates pending | Batch 2 tester plus UX/designer verdicts |

Subagents must stop after their bounded read-only deliverable. Extra ideas go
to backlog unless they are required by this Goal.

## Batch 0 Contract

Allowed:

- update this workflow, launch record, project memory and evidence;
- read current/legacy Docara, pinned Framework repositories and official public
  documentation-platform sources;
- inspect `docara.test` and build disposable copies;
- add tests only after the reference and component decisions are recorded.

Forbidden:

- product implementation before the menu/Framework/acceptance decision is
  evidence-backed;
- writes to Framework owner repositories;
- release, push, merge, tag, archive or default-branch mutation;
- local served-site replacement without staging, verification and backup.

Batch 0 evidence:

- exact baseline and worktree status;
- current menu defect reproduction at desktop and mobile;
- official reference pattern matrix;
- pinned Framework building-block map;
- component/documentation capability matrix;
- test-first Batch 1 acceptance checklist.

## Evidence Policy

Store concise evidence under:

`source/workflow/evidence/2026-07-19-docara-product-completion/`

Record exact revisions, commands, browser dimensions/scenarios, screenshots or
structured observations, source references, component decisions, tests,
digests, verdicts and limitations. Generated dependency trees, browser profiles
and disposable builds remain outside Git.

## Evidence Plan

- bind all evidence to exact baseline/candidate revisions;
- record source-backed component decisions and official reference observations;
- keep automated commands, semantic assertions and static-link results;
- cover desktop/mobile, cold-load/resize, keyboard, focus, light/dark and
  overflow scenarios in browser evidence;
- inventory every candidate diff file in final HCS evidence;
- require independent exact-archive tester acceptance before Goal completion;
- record staging, backup, rollback and matching tree digests for local
  publication.

## Human-Centered Simplicity

- quality_controls: `[human_centered_simplicity]`
- simplicity_repository_refs: `[repo://docara-consolidation]`
- simplicity_repository_baselines:
  `[repo://docara-consolidation@31f468be85d015b962fccc2b4c089204aab1410b]`
- the final changed-surface inventory must cover every file in the exact
  baseline-to-candidate diff; selected-surface PASS is insufficient for Goal
  acceptance.

Primary outcome: a reader can understand where they are, find the next useful
content and read it comfortably without learning Docara's internal model.
Protective complexity includes accessibility, native semantics, schema
validation, deterministic build, migration, rollback and exact Framework locks.

## Risks And Stop Conditions

- stop if the accepted base changes or unrelated work appears in this worktree;
- stop a batch if a required Framework primitive is only available through a
  moving or unpublished reference; record the gap and open a separate owner
  workflow before any Framework write;
- stop local publication if staging verification, backup, rollback, digest,
  HTTPS smoke or browser acceptance is missing;
- stop before any public release, default-branch mutation, repository archive,
  secret access or destructive action;
- a failing test opens a correction batch and never lowers acceptance scope.

## Kaizen

After every milestone classify lessons as: current Goal requirement, Docara
roadmap, Framework owner proposal, documentation improvement, QA improvement or
no reusable lesson. Do not expand the active batch merely because a useful idea
was discovered.

## Next Safe Batch

Implement Batch 2 test-first from accepted candidate `d4ce688b…`:
deterministic local search derived from published content, with a canonical
local index, Framework presentation, keyboard/mobile behavior and no external
runtime service. Batch 1 is already published locally with rollback evidence.

## Last Completed Batch

The first product vertical was accepted on implementation candidate `83d677c7eb5f22d9ca2f4ac16990fe16eddbe985` and closure commit `31f468be85d015b962fccc2b4c089204aab1410b`.

Batch 0 product/Framework decisions and Batch 1 navigation correction are
complete. UX/designer and exact-archive tester returned bounded PASS for
candidate `d4ce688b38c6a29c2b57aaac2f8fe132f05b26b9`; this does not accept later
product stages or the whole Goal. The matching build is served at
`docara.test`; source, staging and served tree digest
`e14c35e5852e2ec44375d22621d6fe704b8270ec2eb5cf45cb6f262dc0cd5530`
matches, and the previous tree remains available for rollback.
