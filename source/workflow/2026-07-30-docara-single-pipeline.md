# Docara single content pipeline

Date: 2026-07-30
Status: active
Workflow ID: `2026-07-30-docara-single-pipeline`
Track: `docara-consolidation`
Primary owners: `dev`, `docs`
Companions: `teamlead`, `tester`

## Goal

Упростить Docara до одного прозрачного конвейера:

`Markdown -> typed Document IR -> Node Renderer Registry -> Smart Component Gateway -> Layout Composer -> HTML`.

Физический Markdown-файл является source of truth каждой публичной страницы.
JSON-файлы описывают только конфигурацию и композицию. Language packs содержат
только системные сообщения интерфейса. Внутренний Document IR существует только
как результат компиляции и не становится вторым форматом авторского контента.

## Primary user outcome

Автор открывает один файл `content/<locale>/<page>.md`, видит в нём весь контент
страницы и меняет именно его. Разработчик может проследить один путь от Markdown
до HTML, добавить универсальный renderer или Smart-компонент без отдельного
projector для каждой страницы. Полная и частичная сборка используют один и тот
же `PageBuilder` и дают одинаковый HTML одной страницы.

## Source-of-truth boundaries

### Design and composition

- `docara.json` — настройки сайта и локалей;
- `section.json` — наследуемые настройки раздела;
- `<page>.page.json` — необязательные настройки конкретной страницы;
- эти файлы не содержат документационные абзацы, примеры и описания компонентов.

### Content

- `content/<locale>/**/*.md` — одна публичная страница на один физический файл;
- front matter хранит короткие технические метаданные страницы;
- Smart-директивы остаются внутри Markdown рядом с объясняющим их содержанием;
- деревья локалей зеркальны по URL, но каждая локаль может иметь собственный
  перевод и явно проверяемое состояние полноты.

### UI translations

- language pack содержит только chrome/runtime copy: поиск, навигацию,
  настройки чтения, ошибки и доступность;
- названия, описания, примеры и параметры документационных компонентов живут в
  Markdown соответствующей локали.

### Runtime

- `MarkdownCompiler` преобразует Markdown в типизированный иерархический IR;
- `NodeRendererRegistry` выбирает renderer по типу узла;
- `SmartComponentGateway` единственным способом обрабатывает `ui.*` и
  `docara.*` через общий контракт;
- `LayoutComposer` соединяет отрисованный документ с областями и оболочкой;
- `PageBuilder` строит одну страницу, `SiteBuilder` только перечисляет страницы
  и вызывает `PageBuilder`.

## Canonical Document IR

IR является деревом, а не авторским JSON и не language pack. Минимальные типы:

- `document`, `heading`, `paragraph`, `text`, `emphasis`, `strong`;
- `inline_code`, `link`, `image`, `list`, `list_item`;
- `table`, `code_block`, `quote`, `thematic_break`, controlled `raw_html`;
- generic `component` with `name`, `props`, `slots` and source location.

Каждый узел сохраняет `source.file`, `source.line` и `source.column`, чтобы
ошибка сборки указывала на реальный Markdown-файл.

## Done when

- каждый публичный document route подтверждён физическим `.md` в
  `content/<locale>`;
- component detail pages больше не синтезируются из language packs или PHP;
- language packs проходят тест, запрещающий документационные тексты;
- Markdown компилируется в typed IR до рендеринга;
- один registry рендерит все типы узлов;
- один Smart gateway обрабатывает `ui.*` и `docara.*`;
- full build и single-page build вызывают один `PageBuilder` и для одинакового
  входа производят byte-identical page HTML;
- старые generated-page, component-specific и trusted-generated-HTML пути
  удалены после доказанной эквивалентности;
- документация объясняет структуру проекта, локализацию, front matter, IR и
  расширение renderer/Smart registry;
- PHPUnit, deterministic builds, static verification, locale coverage и
  browser smoke проходят;
- `https://docara.test/` пересобран из принятого локального кандидата.

## Constraints

- сохранить незавершённые пользовательские изменения dirty worktree;
- не выполнять reset/checkout и не удалять legacy до parity evidence;
- не использовать устаревший skill `docara`;
- не редактировать generated Framework repositories вручную;
- не заявлять release/production readiness и не выполнять merge/tag/release;
- публичный URL и внешний вид сохраняются, если миграция не требует явно
  согласованного изменения.

## Graph gap

Central federation route incorrectly classified this product architecture task
as `skill_federation_change` owned by `graph`. No federation, skill or canonical
graph source is in scope. Per the weak/conflicting-route rule, execution falls
back to the raw `dev` and `docs` owner methods with `teamlead` coordination and
`tester` acceptance. The obsolete `docara` skill is explicitly excluded by the
user.

## Milestones and batches

## Stages

1. `contract` — зафиксировать границы source of truth и воспроизводимый baseline;
2. `vertical-slice` — провести одну физическую Markdown-страницу через новый
   IR, registry, Smart gateway и общий PageBuilder;
3. `content-migration` — перенести остальные публичные страницы и локали в
   физические Markdown-файлы;
4. `legacy-retirement` — удалить проекторы, trusted HTML и дублирующие пути
   только после parity evidence;
5. `acceptance` — подтвердить полную и частичную сборку, локали, ссылки и
   браузерную матрицу.

## Batches

- `B0-contract-baseline` — M0, read-only inventory, ADR, route/source map;
- `B1-badge-single-pipeline` — M1/M3/M4/M5 для
  `content/ru/components/badge.md`;
- `B2-component-content-migration` — остальные component detail pages и
  компонентный индекс;
- `B3-language-pack-boundary` — UI-only schema и fail-closed проверки;
- `B4-public-content-migration` — оставшиеся public routes и locale coverage;
- `B5-legacy-retirement` — M6 после подтверждённой эквивалентности;
- `B6-acceptance` — M7, документация, deterministic/static/browser проверки.

Текущий batch: `B1-badge-single-pipeline`. Следующий batch не начинается, пока
текущий не имеет тестов полного и частичного пути и evidence результата.

### M0 — Baseline and architecture contract

- inventory all authored and generated routes;
- map current Markdown, language-pack, projector and Smart paths;
- capture current full-build output for semantic/parity comparison;
- publish ADR and schemas for Document IR and page build result.

### M1 — Physical Markdown content

- create `content/<locale>/components/<slug>.md` for every public component;
- move prose and examples from component language-pack records/projectors;
- make the component index an authored Markdown page;
- redirect old `/components/catalog/*` routes only where compatibility is
  intentionally retained.

### M2 — Language-pack boundary

- reduce language packs to system/UI strings;
- update schema, repository and documentation;
- add fail-closed tests that reject documentation content in language packs.

### M3 — Typed Document IR

- introduce immutable node/value objects and source spans;
- compile Markdown and Smart directives into one tree;
- add deterministic serialization for diagnostics and tests only;
- validate unknown nodes and invalid component calls before rendering.

### M4 — Renderer registry and Smart gateway

- introduce one `NodeRendererRegistry`;
- move native Markdown rendering behind node renderers;
- merge Framework and Docara Smart resolution behind one gateway;
- keep component templates/logic separate from authored page content.

### M5 — One page builder

- introduce one `PageBuilder` result with meta, configuration, regions,
  assets, diagnostics and HTML;
- make full-site build loop over `PageBuilder`;
- make single-page build call the same method;
- prove byte equality for the same page input.

### M6 — Legacy retirement

- remove component detail page projection from
  `PortableComponentCatalogProjector`;
- remove early `FrameworkComponentRuntime::extract()` rendering;
- remove `DeclarativePipeline::buildGenerated()` and trusted generated HTML;
- remove component-specific methods from the legacy Markdown renderer only
  after all routes pass parity and negative tests.

### M7 — Documentation and acceptance

- rewrite author/developer documentation around the single pipeline;
- verify all locale trees and public routes;
- run full test, deterministic build, static link and browser matrices;
- rebuild the local ServBay site with backup/rollback evidence.

## Verification matrix

- unit: IR parsing, source spans, node registry, Smart gateway, config merge;
- contract: one public route -> one physical Markdown source;
- negative: missing page, unknown node, unknown Smart component, invalid props,
  docs prose in language pack, locale gap;
- metamorphic: full vs partial build equality, repeated build byte equality,
  locale-independent runtime structure;
- integration: existing URLs, search index, navigation, assets and redirects;
- browser: light/dark, desktop/mobile, LTR/RTL for representative pages;
- removal: repository scan proves retired entry points are absent.

## Current status

- M0: architecture inventory and decision recorded;
- M1: first authored component page vertical slice started;
- M2-M7: pending;
- current batch: typed IR and renderer/gateway vertical slice for authored
  `components/badge.md`;
- external blockers: none; route mismatch recorded as non-product graph gap;
- internal implementation gap: `DocumentParser` still emits a coarse
  `MarkdownNode` for ordinary content and recognises only hard-coded
  `ui.alert`/`ui.button` directives, while short aliases and product components
  still use parallel paths.

## Evidence

- workflow: this file;
- architecture decision:
  `source/workflow/2026-07-30-docara-content-architecture.md`;
- evidence root:
  `source/workflow/evidence/2026-07-30-docara-single-pipeline/`;
- implementation and verification records are appended per milestone.

## Next action

Replace the coarse `MarkdownNode` in the badge vertical slice with typed nodes,
replace the hard-coded directive list with the shared alias/component registry,
route every component call through one gateway, and prove that full and partial
builds remain byte-identical. Expand only after this slice passes.

## Kaizen

The current architecture made generated product documentation convenient but
blurred authorship, localization and runtime ownership. The corrective rule is:
author content is always visible in authored Markdown; machine structures are
derived, typed and disposable; page-specific behavior never creates a second
content store.
