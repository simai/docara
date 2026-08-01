# Goal: единый content-first контур `/ru/components/`

Date: 2026-08-01

Status: in-progress

Process model: `general_delivery`

Current state: `launch_record_ready`

Target state: `repository_prepared`

Project mode: `product architecture migration`

Requested level: `goal`

Recommended level: `goal`

Memory decision: `skip`

Memory reason: historical Docara evidence was consulted for preservation
boundaries; current repository specification, graph, workflow, Git and fresh
build evidence are authoritative and no memory write was requested.

Workflow ID: `2026-08-01-docara-m3-ru-components-goal`

Track Goal: завершить принятую архитектурную миграцию русского публичного
раздела компонентов без изменения URL, продукта и внешнего поведения.

First Batch: M3.1 durable execution contract, inventory and baseline.

Completion Gate: 32/32 route имеют ровно один физический Markdown-owner;
generated component page count и русский component-route allowlist равны нулю;
русский language pack не содержит page prose; full/single используют один
PageBuilder/registry/gateway; две clean full builds byte-identical; static и
browser matrices зелёные; graph/workflow/handoff синхронны; worktree чистый.

Current Remaining: 30 generated routes из 32; Badge и Syntax уже физические.

Do Not Complete Until: все критерии Completion Gate подтверждены свежей
evidence на интегрированном HEAD.

Next Safe Batch: M3.2, ранний route selector и общий runtime-контракт перед
Alert vertical slice.

## Goal

Полностью перевести весь публичный русский раздел компонентов Docara на
архитектуру:

```text
Markdown owner -> typed in-memory Document IR -> DocumentRendererRegistry
-> SmartComponentGateway -> LayoutComposer -> HTML
```

Full и isolated builds используют один `PageBuilder`; отличается только
выбранный набор route. Производные представления читают PageBuilder results и
route metadata, а не второй реестр page prose.

## Track

Track ID: `docara-unified-architecture`

## Current Goal

Перевести все 32 фактически существующих route раздела `/ru/components/` на
один content-first runtime и закрыть интеграционную приёмку M3.

## Final Outcome

Автор редактирует одну физическую Markdown-страницу для каждого русского
component route. Docara компилирует только выбранные route в типизированный IR,
разрешает Smart nodes через единые registry/gateway и строит full/isolated
результаты одним PageBuilder. Индекс и reader navigation выводятся из тех же
page results. Активных русских prose projections не остаётся, а сохранённый
legacy имеет доказанного потребителя и rollback boundary.

## Project mode and owners

- mode: product architecture migration;
- delivery/process owner: `teamlead`;
- implementation/repository owner: `dev`;
- graph structure owner: `graph`;
- QA and final verdict owner: `tester`;
- canonical domain sources: repository specification, project graph, workflow
  evidence and handoff;
- explicitly excluded: disabled obsolete `docara` skill.

## Federation and action-gate state

- federation route selected disabled `docara`; this is a recorded routing gap,
  not authority to enable or use it;
- central route also misclassified this goal-sized migration as one publication
  batch;
- central process resolver failed while parsing an inherited legacy launch
  record; project-local workflow/launch/graph are the recovery contract;
- preflight gates `release_context_boundary_gate`, `runtime_naming_gate` and
  `source_policy_gate`: PASS;
- no live/deploy/access/secret action is in scope;
- deletion is allowed only as bounded Git-tracked legacy retirement after
  parity, zero-reference evidence and a commit rollback path.

## Done When

1. All 32 existing Russian component-section routes have one physical owner
   under `docs/site/content/ru/components/`.
2. Every component page has useful reader-facing purpose, working general
   example, parameters/values, meaningful variants, call and useful limits.
3. Page prose and call snippets are absent from Russian language packs, PHP
   config and catalog manifests.
4. `docs/site/content/ru/lang.json` contains shared UI strings only.
5. One generic block-component typed IR contract serves all Smart components.
6. All Smart calls resolve through one alias registry, renderer registry and
   Smart gateway; unknown inputs fail with route/file/line/column.
7. Full and isolated builds use one `PageBuilder`; isolated selection occurs
   before compilation and irrelevant catalog/example projections.
8. Component index, navigation, search, outline, breadcrumbs and transitions
   are derived from the same page result/route metadata contour.
9. Replaced projections, language-pack prose and allowlist entries reach zero
   active references before deletion; retained legacy has a proved consumer.
10. Two clean full builds are byte-identical and static verification reports
    zero broken links/assets.
11. Browser matrix covers desktop/mobile, light/dark, keyboard/focus, copy,
    tabs/example/code, responsive tables and representative Smart components.
12. Specification, graph, roadmap, workflow, evidence and handoff match the
    actual implementation without readiness inflation.
13. The branch contains logical checkpoint commits, worktree is clean, and no
    merge/push/tag/release/deploy occurred.

## Scope and non-goals

Allowed: repository runtime/content/resources/tests/specification/graph/
workflow/handoff required by this goal.

Forbidden: other locales, SIMAI Framework or dependency-lock changes unless a
proved external blocker requires a user decision; a second engine/PageBuilder/
registry/gateway/content registry; manual generated-output edits; product URL
or meaning changes; merge/push/tag/release/deploy; destructive cleanup without
parity and rollback.

## Stages

- M3.1 — durable execution contract, inventory, sequence and baseline.
- M3.2 — common runtime contract, early selection and Alert slice.
- M3.3 — all Russian component pages migrated by families.
- M3.4 — derived views from PageBuilder results and route metadata.
- M3.5 — zero-reference legacy reduction with rollback mappings.
- M3.6 — integrated deterministic, static, browser and reverse acceptance.

| Milestone | Outcome | Completion evidence | Status |
| --- | --- | --- | --- |
| M3.1 | durable contract, inventory, family order and baseline | inventory + hashes + browser baseline + graph | completed |
| M3.2 | common runtime contract and Alert vertical slice | focused/full/single/static/browser parity | pending |
| M3.3 | all Russian component pages Markdown-owned | 32/32 ownership and family evidence | pending |
| M3.4 | derived views use the same page-result contour | index/nav/search/outline/transitions tests | pending |
| M3.5 | inactive Russian component legacy retired | zero-reference scans and rollback map | pending |
| M3.6 | integrated goal acceptance | deterministic/static/browser/audit evidence | pending |

## Batches

Green batches continue automatically. Each implementation batch has focused
checks, evidence update and a separate commit.

- Batches 01–02 establish M3.1 contract, inventory and reproducible baseline.
- Batches 03–06 establish the shared runtime and migrate Alert.
- Batches 07–22 migrate the remaining route families and component index.
- Batches 23–24 move derived views to the unified result contour.
- Batches 25–26 remove Russian prose projections and proved-unused legacy.
- Batches 27–30 perform integrated acceptance and close the goal only after
  the Completion Gate.

| Batch | Scope | Required check | Status |
| --- | --- | --- | --- |
| 01 | M3.1 workflow, launch, inventory and evidence index | graph/JSON/hygiene | completed |
| 02 | M3.1 clean full plus all-route isolated baseline hashes | deterministic/static/browser baseline | completed |
| 03 | early selector and route/source plan before projections | call-spy and full/single regression | in-progress |
| 04 | generic block-component IR and compiler contract | typed snapshot and negative locations | pending |
| 05 | generic renderer-registry/Smart-gateway block artifact | registry/gateway focused tests | pending |
| 06 | Alert Markdown owner | exact Alert full/single/browser parity | pending |
| 07 | native headings/text and lists/quotes | route parity | pending |
| 08 | native links/images and table | route parity + responsive table | pending |
| 09 | native code and footnotes/sources | copy/code/anchors parity | pending |
| 10 | details and backlinks | disclosure/navigation parity | pending |
| 11 | banner and download | variant/asset parity | pending |
| 12 | button and icon/kbd | interactive/focus parity | pending |
| 13 | card and hero | layout parity | pending |
| 14 | grid and figure | layout/media parity | pending |
| 15 | media and logos | asset/responsive parity | pending |
| 16 | diagram and math | runtime/asset parity | pending |
| 17 | code-from-file and HTML | safe rendering parity | pending |
| 18 | embed and example | embed/example tabs/copy parity | pending |
| 19 | steps and tree | structure/responsive parity | pending |
| 20 | tabs | keyboard/focus/tabset parity | pending |
| 21 | component index physical owner | list/URL/content parity | pending |
| 22 | remaining discovered route gaps | inventory reaches 32/32 | pending |
| 23 | PageBuilder-result metadata for index/nav/breadcrumbs/transitions | derived-view integration tests | pending |
| 24 | search/outline and isolated global-artifact update | full/single semantic tests | pending |
| 25 | Russian language-pack prose removal and boundary tests | zero forbidden prose | pending |
| 26 | projector/allowlist/example zero-reference retirement | zero active refs + rollback map | pending |
| 27 | full PHPUnit/lint/JSON/graph/static verification | all deterministic gates | pending |
| 28 | two clean full builds and representative isolated matrix | byte equality + route hashes | pending |
| 29 | browser matrix and all-route smoke | accessibility/console/visual evidence | pending |
| 30 | reverse-outcome audit, spec/graph/handoff and clean history | goal Completion Gate | pending |

Batch grouping may change when inventory proves a safer smaller/larger family,
but milestone outcomes and Completion Gate do not change.

## Evidence Plan

Canonical index:
`source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/INDEX.md`.

Every checkpoint records parent/candidate binding, routes/owners, commands,
semantic results, hashes, browser artifacts where relevant, deviations,
rollback and next batch. Screenshots are evidence only. Failed attempts remain
recorded when they affect reproducibility.

## Stop conditions

Stop only for: live baseline contradiction that risks data loss; required
Framework/dependency tuple change; irreversible migration without parity and
rollback; unavoidable user-change conflict; or a product decision changing
public URLs/content meaning. Ordinary implementation/test/browser defects are
fixed inside the goal.

## Recovery protocol

After compaction or interruption, read this file, the evidence index, current
handoff, graph state and Git HEAD/status. Resume the first non-green batch.
Never infer completion from a checkpoint commit or chat summary.

## Progress

### Batch 01 — durable execution contract

- status: completed;
- parent: `b14fe4e1e70a5465fe382bd5ced1de26cb65a315`;
- action gates: PASS;
- federation gaps: disabled obsolete owner, wrong work scale and legacy launch
  parser crash recorded above;
- inventory: 32 routes, 2 physical owners and 30 generated projections;
- graph/process/JSON/hygiene checks: PASS;
- evidence: `m3-ru-components/M3.1-EXECUTION-CONTRACT.md`.

### Batch 02 — reproducible baseline

- status: completed;
- two disposable full builds: 103 pages and 321 files, byte-identical;
- all 32 component routes: full/isolated page and output-tree parity PASS;
- static verification: 206 HTML documents, 18,866 local references and zero
  broken references;
- browser baseline: index plus five representative Smart/native families,
  desktop/mobile and light/dark, 24 captures, zero console errors/warnings;
- evidence: `m3-ru-components/baseline.json` and
  `m3-ru-components/browser-captures.json`.

### Batch 03 — early route selection

- status: in-progress;
- parent: M3.1 checkpoint commit;
- objective: select the isolated route before irrelevant catalog/example
  projections while retaining one PageBuilder and full-build behavior.

## Kaizen

No reusable lesson accepted yet. Project-local process gaps remain evidence,
not permission to modify federation or owner skills.
