# Workflow: Docara legacy replacement readiness

Date: 2026-07-20
Status: active
Workflow ID: `2026-07-20-docara-legacy-replacement-readiness`
Track ID: `docara-consolidation`
Process model: `full_qa`
Current state: `tests_recorded`
Target state: `evidence_recorded`
Project mode: `productization`
Requested level: `goal`
Owner: `docara`
Coordinator: `teamlead`
QA owner: `tester`
Companions: `dev`, `docs`, `sf5`, `ux`, `designer`, `ops`
Memory decision: `inject`
Memory reason: prior Docara local-build and legacy-layout evidence is
useful orientation, but every mutable source/runtime fact is reverified.
Memory context pack:
`evidence/2026-07-20-docara-legacy-replacement-readiness/personal-memory-context.md`

## Goal

Довести текущую portable Docara до состояния replacement-ready относительно
legacy: зафиксировать решения по каждой старой возможности, подготовить
redirects, облегчить документационный shell, сделать мобильную навигацию без
сдвига контента, упростить code blocks, согласовать locale/version и выполнить
сравнительную exact-candidate приёмку.

## Track

Track ID: `docara-consolidation`

This is the replacement-readiness Goal after the accepted local-product Goal.
It advances the same productization track but does not include the later public
release/default-branch/repository-retirement Goals.

## Current Goal

Make the accepted portable product a proven replacement for the retained
legacy contour without reviving the legacy frontend runtime.

## Final Outcome

Portable Docara является доказанной заменой legacy для принятого продуктового
контура: автор понимает, что мигрируется, чем заменяется и что сознательно
выводится; обязательные старые URL имеют детерминированный redirect или
зафиксированное исключение; документационный shell остаётся функционально
полным, но становится компактнее; mobile navigation не сдвигает статью;
code blocks имеют одну визуальную поверхность; locale/version имеют явный
продуктовый контракт. Один immutable candidate проходит сравнительную
exact-archive, build, static, browser, UX/design, HCS и независимую tester
приёмку.

## Accepted Baseline

- closure HEAD:
  `a065fd46f941c77d6cde1b45c73578020488e2f0`;
- immutable product candidate:
  `de87bdef224d518d1c707286d4640be0238d34bc`;
- exact accepted build digest:
  `502e43119ea2f2fc6ce358042858937060a67c4aa3d4d5ac0295e3d19c8e782f`;
- current local target:
  `https://docara.test/`;
- comparison reference:
  `https://docara-legacy.test/en/`.

The baseline remains a regression sentinel. This Goal may improve it, but must
not rewrite its evidence or claim that previous acceptance covered the new
replacement-ready boundary.

## Done When

- every legacy capability in the retained legacy source/docs inventory has one
  machine-readable decision: `replace`, `migrate`, `retire`, or `defer`, with
  rationale, user impact, migration path and acceptance evidence;
- locale and version behavior have one explicit contract covering source tree,
  generated URLs, navigation/search isolation, fallback and unsupported states;
- redirects are declared data, schema-validated, collision-safe, base-aware,
  emitted deterministically and verified for the accepted legacy route corpus;
- redirects do not execute arbitrary external redirects, shadow generated
  pages, create loops or depend on a server-specific runtime;
- desktop docs shell is content-first: main and TOC are not decorative cards,
  ordinary menu rows are quiet, active trail and accessibility remain clear;
- documentation typography and spacing are compact without changing landing
  hero intent;
- at 390 and 768 pixels navigation/outline opens as an overlay surface and does
  not push the article; `Escape`, close, outside dismissal where appropriate,
  focus containment and focus restoration are verified;
- code blocks use one content surface with language/copy chrome, local
  horizontal scroll, syntax highlighting and line numbers where admitted;
- Framework utilities/components remain source-backed and no new generic
  application primitive silently duplicates a Framework capability;
- user/developer/migration documentation and schemas match the implementation;
- route crawl, old-to-new redirect checks, deterministic build, zero required
  broken links, responsive/browser/keyboard/theme/search/catalog regression and
  comparative legacy invariants pass for one exact candidate;
- complete-diff HCS evidence covers every changed visible and engineering
  surface and an independent tester returns `PASS` for that same candidate;
- after a separate local runtime preflight, the exact accepted build is served
  at `docara.test` with a verified rollback copy and matching digests.

## Non-Goals

- public package or website release;
- merge, push, tag, default-branch migration or Git history rewrite;
- archive or deletion of `docara-mix`, `docara-template`, legacy worktrees or
  any repository;
- restoring the legacy Blade/Mix frontend runtime;
- claiming production readiness or readiness of the complete Simai Framework
  ecosystem;
- adding every Retype-like component without a demonstrated author/reader job;
- writes to Framework owner repositories.

## Product Decisions To Record

1. Legacy capability disposition and migration path.
2. Redirect data model, generation strategy and route-collision policy.
3. Locale/version product contract.
4. Documentation shell necessity map and simplest complete alternative.
5. Code rendering/admission contract, including the current enhanced-code
   catalogue/runtime discrepancy.

## Milestones

### M1 — Replacement contract

Result: capability, redirect and locale/version decisions are explicit and
testable.

- Batch 0: baseline, QA workspace, legacy inventory and reference block map;
- Batch 1: capability-retirement ledger and migration documentation;
- Batch 2: redirect and locale/version schemas, generators and negative tests.

### M2 — Human-centered shell

Result: current semantic/navigation strengths remain while visual and mobile
surface is simplified.

- Batch 3: flatten desktop shell, compact typography/nav/TOC;
- Batch 4: overlay mobile navigation/outline with focus and keyboard contract;
- Batch 5: one-surface code blocks and catalogue contract correction.

### M3 — Unified replacement acceptance

Result: one exact candidate proves replacement readiness and is safely visible
on the local stand.

- Batch 6: docs, full deterministic/static/regression and complete-diff HCS;
- Batch 7: exact-archive comparative browser/UX/design/tester acceptance;
- Batch 8: local publication with backup, rollback and served digest proof.

## Stages

1. Replacement contract.
2. Human-centered shell.
3. Unified replacement acceptance.

## Batches

1. Batch 0: baseline, QA workspace, legacy inventory and reference block map.
2. Batch 1: capability-retirement ledger and migration documentation.
3. Batch 2: redirect and locale/version schemas, generators and negative tests.
4. Batch 3: flatten desktop shell and compact typography/navigation/TOC.
5. Batch 4: overlay mobile navigation/outline with complete focus contract.
6. Batch 5: one-surface code blocks and catalogue contract correction.
7. Batch 6: documentation, full regression and complete-diff HCS.
8. Batch 7: exact-archive comparative browser/UX/design/tester acceptance.
9. Batch 8: local publication with backup, rollback and served digest proof.

## Evidence Plan

Each requirement maps to the committed QA artifacts below. Implementation
batches add source/test/docs evidence; Batch 7 binds every final verdict to one
immutable candidate SHA and exact archive; Batch 8 records source, staging and
served manifests plus rollback proof. No validator or page-load smoke can
replace the requirement-to-evidence matrix.

## Human-Centered Simplicity

- quality_controls: `[human_centered_simplicity]`
- simplicity_repository_refs: `[repo://docara-consolidation]`
- simplicity_repository_baselines:
  `[repo://docara-consolidation@a065fd46f941c77d6cde1b45c73578020488e2f0]`

The final simplicity review will be added only after a candidate exists. Its
changed-surface inventory must cover every file in the exact
baseline-to-candidate diff. Current UX audit facts are planning evidence, not a
tester verdict.

Primary human outcome: a reader reaches and reads the requested documentation
quickly while an author can migrate legacy content without hidden loss.
Protective complexity includes semantic navigation, focus management, schema
validation, safe redirects, deterministic output, Framework locks, migration
evidence and rollback.

## Required QA Artifacts

- `qa-plan.md`;
- `scope-matrix.md`;
- `role-access-matrix.md`;
- `reference-block-map.md`;
- `findings-register.md`;
- `readiness-matrix.md`;
- `capability-retirement-ledger.yaml`;
- `redirect-corpus.json`;
- exact-candidate build, browser, HCS and tester verdicts.

Evidence root:
`source/workflow/evidence/2026-07-20-docara-legacy-replacement-readiness/`.

## Owner Map

| Surface | Owner | Role |
| --- | --- | --- |
| Goal, batches, boundaries | `teamlead` | coordinator |
| Portable generator, schemas, renderer | `docara` | owner / implementer |
| Repository code and hygiene | `dev` | implementer |
| Migration and user/developer docs | `docs` | author / gatekeeper |
| Framework selection/admission | `sf5` | consulted owner |
| IA, mobile interaction, accessibility | `ux` | author / gatekeeper |
| Visual hierarchy and design quality | `designer` | reviewer |
| QA matrix and final verdict | `tester` | independent gatekeeper |
| Local publication/rollback | `ops` | gatekeeper |

## Workstream Register

| Workstream | Owner | State | Evidence |
| --- | --- | --- | --- |
| Legacy capability/redirect/locale inventory | `docs` + `docara` | completed | capability ledger, product decisions and redirect corpus |
| Shell/mobile/code implementation map | `ux` + `designer` + `sf5` | completed | comparative audit and Framework-owned code decision |
| Replacement QA matrix | `tester` | prepared | QA artifacts and exact-candidate protocol |
| Critical-path implementation | `docara` + `dev` | working-tree verified | changed source/tests/docs; candidate pending |

## Safety And Gates

- source policy gate: passed in preflight on 2026-07-20;
- source work is restricted to this worktree;
- legacy site, legacy detached worktree and Framework repositories are
  read-only references;
- `docara.test` is not changed until an `ops` local-runtime preflight confirms
  staging, backup, rollback and smoke paths;
- secrets and `.env*` are forbidden;
- no release/default-branch/archive action is authorized.

## Current Batch

Batch 7 — create one immutable candidate, rebuild and retest it only from exact
archives, complete the comparative browser/HCS matrix and obtain an independent
tester verdict for the same SHA.

## Current Remaining

- milestones remaining: 1 of 3;
- batches remaining: 2 of 9;
- active batch: Batch 7;
- goal status: active.

Completed in the mutable working tree:

- Batches 0–2: legacy capability ledger, locale/version decision and
  fail-closed deterministic redirects;
- Batches 3–5: content-first shell, native overlay navigation/outline and one
  Framework-owned code surface;
- Batch 6: documentation, PHP lint, structured data, Composer/platform, Pint,
  547 PHPUnit tests / 4,449 assertions, two byte-identical builds, 66 HTML
  pages and 6,033 local references with zero broken.

Mutable build tree digest:
`c1b105efeb75e7688573e18a5aac6a90b9eac386e02c2f5e1d4e4ec33ac0b9e9`.
This is pre-candidate evidence only and cannot replace exact-candidate
acceptance.

## Stop Conditions

- unrelated worktree changes appear;
- implementation needs a Framework owner write or moving/unreleased asset;
- legacy capability disposition would remove user data or a required workflow
  without an explicit migration path;
- locale/version alternatives imply materially different product scope that
  cannot be resolved from current product principles and evidence;
- local publication lacks backup/rollback/digest evidence;
- public release, default-branch or repository-retirement action becomes
  necessary;
- secret or external credential is required.

## Next

Create the immutable candidate, execute Batch 7 from exact archives, then
publish only the accepted build to the local `docara.test` stand under the
Batch 8 ops backup/rollback contract.
