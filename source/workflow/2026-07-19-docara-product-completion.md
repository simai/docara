# Workflow: Docara product completion

Date: 2026-07-19
Status: in-progress
Process model: `general_delivery`
Current state: `tests_recorded`
Target state: `evidence_recorded`
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
2. Use Framework utilities for layout, components for presentation and
   Smart-components only through their exact canonical manifest/runtime
   contract. Rendering, data binding and effect readiness remain separate
   machine-readable capabilities and are never inferred from the word Smart.
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

- Milestones remaining: 3 of 5.
- Batches remaining: 3 of 10.
- Active batch: Batch 7.
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
| `WS-CORE-01` | Docara owner | workflow, current renderer/menu reproduction and critical path | this worktree | Batch 1 accepted and published locally | Batch 1 exact tester, UX/design and publication PASS |
| `WS-UX-01` | UX/designer review | official-platform comparison and bounded menu/product shell gate | none | Batch 1 PASS | `batch-1-ux-design-verdict.md` |
| `WS-FRAMEWORK-01` | `/root/smart_component_inventory` | pinned utilities/components/Smart-components for menu, search, TOC, settings, landing and catalogue | none | completed | `framework-building-block-map.md` |
| `WS-DOCS-01` | `/root/docs_catalog_audit` | Retype-style component capability inventory and Docara docs coverage/map | none | completed | `product-capability-matrix.yaml` and catalogue/docs decision |
| `WS-SEARCH-02` | Docara owner | deterministic local search, schema, runtime, verifier and documentation | this worktree | accepted and published locally | Batch 2 exact tester, HCS, UX/designer and publication PASS |
| `WS-READING-03` | Docara owner with UX/designer | breadcrumbs, page outline and previous/next navigation from one navigation tree | this worktree | accepted and published locally | Batch 3 exact tester, HCS, UX/design/browser and publication PASS |
| `WS-SETTINGS-04` | Docara owner with UX/designer | simple reader settings and responsive integration | this worktree | accepted and published locally | exact HCS/tester/browser, independent visual/evidence audit and served smoke PASS |
| `WS-LANDING-05` | Docara owner with UX/designer | landing recipe and responsive demonstration from pinned Framework building blocks | this worktree | accepted and published locally | exact tester, HCS/source/security, native-Chrome UX/design and served smoke PASS |
| `WS-CATALOG-CONTRACT-06` | Docara owner with Framework consultation | one effective component projection, typed definitions and lifecycle/gap contract | this worktree | accepted and published locally | schemas, negative matrix, deterministic projection, exact tester, complete-diff HCS and browser/publication PASS |
| `WS-LIVE-CATALOG-07` | Docara owner with docs, UX and designer | generated component catalogue, generic detail page, exact examples and `docara.columns` | this worktree | mutable implementation verified; immutable candidate pending | deterministic pages/examples, schema and negative matrix, exact tester, complete-diff HCS, browser/UX/design and publication PASS |

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

Commit the verified Batch 7 implementation as one immutable candidate. Require
independent exact-archive tester, complete-diff HCS/source/security and
native-Chrome UX/design acceptance for that exact SHA, then publish through
staging with rollback and matching digests. Continue directly to Batch 8 only
after the bounded Batch 7 closure. Keep the accepted Batch 6 build served until
all Batch 7 gates pass.

## Last Completed Batch

The first product vertical was accepted on implementation candidate `83d677c7eb5f22d9ca2f4ac16990fe16eddbe985` and closure commit `31f468be85d015b962fccc2b4c089204aab1410b`.

Batch 0 product/Framework decisions and Batch 1 navigation correction are
complete. UX/designer and exact-archive tester returned bounded PASS for
candidate `d4ce688b38c6a29c2b57aaac2f8fe132f05b26b9`; this does not accept later
product stages or the whole Goal. The matching build is served at
`docara.test`; source, staging and served tree digest
`e14c35e5852e2ec44375d22621d6fe704b8270ec2eb5cf45cb6f262dc0cd5530`
matches, and the previous tree remains available for rollback.

Batch 2 deterministic local search is accepted on candidate
`df82a5fa82a96263c3f8af4e900a9c5a665f9412` (tree
`224e9acf512ec8d53f50a6275a594423e34ddf3c`). Exact tester, HCS/source and
UX/designer/browser gates returned bounded PASS. The verified build is served
at `docara.test`; source, staging and served tree digest
`81945efbe610a54986586a2a61f16e64556528e235596fb016330923f450fd1f`
matches, while the Batch 1 tree remains preserved for rollback. This does not
accept Batch 3 or the wider Goal.

Batch 3 reading context is accepted on candidate
`73eae43b9e8f715c0dc978390f4e60a1011465c9` (tree
`0f1aa5544dabf9631550a349caa9641f04384bd3`). Exact tester, complete-diff HCS
and exact UX/designer/browser gates returned bounded PASS. The verified build
is served at `docara.test`; source, staging and served digest
`826c8a0dac97bc1a17f7b5926d05908d14424fa410631a35f6e374403656654b`
matches, while the Batch 2 tree remains preserved for rollback. This does not
accept Batch 4 or the wider Goal.

Batch 4 reader settings and responsive integration are accepted on candidate
`d26fa66c6d6a5a36ec113288e6fce29f2f6b1a0e` (tree
`aa20ad5d0d95f82149a30d189dd5fa9d78163d4a`). Exact HCS, independent
non-browser tester, native-Chrome browser, independent visual/evidence review
and served-site gates returned bounded PASS. The verified build is served at
`docara.test`; source, staging and served digest
`9cf966409f87a568fbd6a79efc12c6922369dc5ce5fe92adcbdff074e297e67f`
matches, while the accepted Batch 3 tree remains preserved for rollback at
`.docara-backups/product-completion-settings-d26fa66-20260719-094411`.
This closes Milestone 2, not Batch 5 or the wider Goal.

The first Batch 5 candidate
`68fba097d6d629ad77937a09a6c16b25ea709850` remains rejected and unpublished.
Its corrected successor `918919046a2863a67a306678ad225dbda4549666`
passed independent exact tester, native-Chrome UX/design and complete-diff
HCS/source/security gates. Closure commit
`8c241f5b088ed92934274cad28659060a892a514` records the accepted local
publication. Source, staging and served digest
`c0d38e6badc833eaa29cf0f0482d4306c10aca943e993e79ffa629497a5b3060`
matches, and the accepted Batch 4 tree remains available for rollback. Batch 6
and the wider Goal are not accepted by this result.

Batch 6 effective component contract is accepted on candidate
`68a960ff1debde48664aa8541413dbef208612ee` (tree
`9363f21c63e516ee4b97772f097b70aa52ff412f`). Independent exact-archive
tester, complete-diff Human-Centered Simplicity/source/security and
native-Chrome UX/design gates returned bounded PASS. The verified build is
served at `docara.test`; source, staging and served digest
`16bbdd52e2dc0e0c058c02dfbc61e3dd824fa2e59be23b45848f587d83a3fc50`
matches, and the accepted Batch 5 tree remains preserved for rollback at
`.docara-backups/product-completion-catalog-contract-68a960f-20260719-152452`.
This accepts and publishes Batch 6 only; Batch 7 and the wider Goal remain
in progress.

Batch 7 mutable implementation is complete and candidate preparation is
recorded in `batch-7-live-catalog-implementation.md`. The single generated
projection contains 17 records, 12 supported live examples, one index and 12
generic details; `docara.columns` closes the required responsive layout recipe.
Two clean production builds are byte-identical at
`dc6e2a997314a2497da29af3e696937d7f46aee2a832ed3166e2862cdd963675`
with 70 files, 60 HTML pages and 6477 verified local references. The complete
sequential suite passes with 534 tests and 3923 assertions. This is mutable
pre-acceptance evidence only: no Batch 7 candidate, publication or wider Goal
claim exists until the exact gates pass.
