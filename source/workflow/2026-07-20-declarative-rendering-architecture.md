# Docara → Larena: декларативная архитектура сборки страниц

Дата: 2026-07-20
Статус: accepted direction; first vertical slice implemented in shadow mode
Контур: Docara как первый статический compiler/runtime для подхода, который
позже должен использовать Larena через собственный platform adapter.

Реализация первого среза и её границы зафиксированы в
`source/workflow/2026-07-20-declarative-rendering-pipeline.md`. Этапы извлечения
всего legacy shell, landing и полного набора Smart-компонентов не заявлены
выполненными.

## 1. Решение

Предложенная владельцем продукта модель реализуема и является правильным
направлением.

Текущая Docara доказала переносимый формат, наследование настроек, Markdown,
компонентные вызовы, статическую публикацию и работу Simai Framework. Однако
финальная стадия сборки пока слишком монолитна:

- `PortableHtmlRenderer` одновременно оркестрирует страницу, формирует shell,
  содержит HTML, CSS и JavaScript;
- `PortableMarkdownRenderer` одновременно разбирает директивы и формирует HTML
  нескольких Docara-компонентов;
- `PortableSiteBuilder` одновременно обнаруживает источники, разрешает
  конфигурацию, собирает навигацию, готовит страницы, публикует assets и передаёт
  нетипизированные массивы renderer-у;
- текущий `presentation.schema.json` описывает только несколько настроек
  внешнего вида, но не layout profiles, regions и композицию sections/blocks.

Это допустимо как proof/MVP, но не должно становиться общей архитектурой
Docara/Larena.

Целевая формула:

```text
Source content
  -> parser
  -> typed document AST
  -> data binding
  -> Page -> Section -> Block -> Smart composition
  -> resolved layout/render plan
  -> trusted template registry
  -> HTML + assets + hydration + diagnostics
```

Docara в этой модели является статическим compiler/publisher, а Larena —
динамическим runtime/editor. Их source storage, routing, access, persistence и
publication различаются, но промежуточный декларативный контракт и правила
композиции должны быть одинаковыми.

## 2. Что подтверждено существующими реализациями

### Docara

Уже существуют:

- `site.json` / `section.json` / `*.page.json` и наследование настроек;
- `ResolvedPagePlan`;
- Markdown и typed directives;
- manifest-backed вызовы `ui.alert` и `ui.button`;
- framework lock и asset publication;
- docs/landing presets.

Не хватает:

- отдельного layout/region contract;
- отдельного document AST;
- `Page -> Section -> Block -> Smart` render plan;
- template registry и presentation-only templates;
- разделения parser и renderer;
- разделения builder orchestration и publisher.

### Larena

В `larena/layout` уже есть полезные typed contracts:

- `LayoutDescriptor`;
- `LayoutRegion`;
- `PageDescriptor`;
- `SectionDefinition`;
- `ResolvedLayoutPlan`.

В `larena/ui` уже есть:

- `SmartComponentManifest`;
- `SmartRegistry`;
- `SmartManager`;
- `SmartBackendRenderer`;
- `FrontendRenderArtifact`;
- backend facade для вызова Smart.

Это пока contract/proof layer, а не завершённый production renderer. Значит,
Docara не должна копировать текущий HTML proof из Larena, но должна использовать
те же понятия и совместимые fixtures.

### Bitrix / `bx-simai.main`

Там уже сформулированы и частично реализованы нужные принципы:

- layout cascade отделён от region content cascade;
- layout задаёт shell и доступные regions, но не хранит содержимое sections;
- page хранит route/meta и вызовы sections, а не внутренности sections;
- section композирует blocks;
- block описывает сценарий, источник данных и связь со Smart;
- Smart отвечает за визуализацию и интерактивность;
- `Smart::render()`, `Block::render()`, `Section::render()` дают единый backend
  API;
- Smart artifact может содержать manifest, view, preset и template;
- platform-neutral contract отделён от Bitrix и Larena adapters.

Bitrix-код является доказательством реализуемости, но не образцом для
буквального копирования: крупный `UI\Smart` и часть PHP templates также содержат
лишнюю подготовительную логику. В целевой модели эту логику нужно вынести в
resolver/presenter, оставив template только представлением.

Локальный демонстратор `storage.test/demo/framework` подтверждает работающий
backend-вызов Smart и layout experiments, но его demo-страницы закономерно
смешивают PHP и HTML. Их следует использовать как API/evidence, а не как шаблон
архитектуры генератора.

## 3. Уточнение шести исходных пунктов

### 3.1. Настройки макета

Да. `LayoutProfile` определяет:

- стабильный key;
- regions и их контракт;
- обязательные/необязательные regions;
- shell template;
- wrappers;
- responsive placements;
- допустимые section types;
- asset requirements.

Layout не содержит контент, HTML страницы, menu data или настройки конкретного
Smart.

### 3.2. Описание областей

Логически у каждой region должен быть декларативный descriptor. Физически не
следует требовать отдельный файл для каждой region всегда:

- простая region может быть inline в page/section descriptor;
- большая или повторно используемая region может подключаться через безопасный
  `$ref`;
- site, section/path и page уровни могут применять операции
  `replace`, `append`, `prepend`, `remove`, `disable`.

Так сохраняется чистая модель без сотен пустых JSON-файлов.

Region descriptor содержит только вызовы sections и их параметры. Он не хранит
сырой HTML и не знает внутренности section.

### 3.3. Parser

Parser должен возвращать не произвольный `array`, а типизированный
`DocumentAst`:

- metadata;
- native Markdown nodes;
- Smart calls;
- Docara semantic directives;
- stable node IDs;
- source path и source spans;
- diagnostics/warnings;
- content references.

`toArray()` допустим как transport/debug representation. Сам runtime должен
работать с DTO/value objects, иначе ошибки снова будут обнаруживаться только в
template.

Нужно хранить два независимых дерева:

1. content tree (`DocumentAst`);
2. composition tree (`Page -> Section -> Block -> Smart`).

Они соединяются data binding-ом, например `@page.document`, но не смешиваются.

### 3.4. Smart templates

Template хранится отдельно от manifest, view и preset:

```text
smart/ui.button/
  manifest.json
  views/default.json
  presets/primary.json
  templates/default.php
```

Правила:

- authored JSON указывает template ID, но никогда произвольный filesystem path;
- template выбирается только allowlisted `TemplateRegistry`;
- props/slots/view/preset валидируются до вызова template;
- template получает готовый immutable `SmartViewModel`;
- в template разрешены вывод, экранирование, простые условия и циклы;
- class calculation, defaults, schema validation, data loading и asset
  resolution выполняются вне template;
- raw HTML доступен только через явный trusted value;
- CSS/JS не встраиваются в PHP template, а приходят через asset graph.

Не каждому frontend custom element нужен отдельный PHP template. Для простых
Simai Framework elements может использоваться один проверенный host renderer.
Отдельный template нужен для backend-rendered или composite Smart.

### 3.5. Backend API

Базовый API:

```php
Smart::render('ui.button', $props, $slots, $context);
Block::render('content.markdown', $data, $settings, $context);
Section::render('docara.article', $params, $context);
Page::render($pageDescriptor, $context);
```

Метод Smart возвращает не только строку HTML, а `RenderArtifact`:

- html;
- required assets;
- hydration descriptor;
- cache tags;
- diagnostics;
- manifest/template/version provenance.

Удобные методы `Smart::button()` допустимы, но лучше генерировать их из
manifest/registry, а не поддерживать вручную второй набор контрактов.

### 3.6. Builder

Builder становится тонким orchestrator-ом:

1. discover sources;
2. load and validate descriptors;
3. resolve site/path/page inheritance;
4. parse Markdown в `DocumentAst`;
5. resolve layout и region operations;
6. resolve sections/blocks и bind data;
7. resolve Smart manifests/views/presets/templates;
8. получить immutable `ResolvedRenderPlan`;
9. render trusted templates;
10. собрать asset graph, search, redirects;
11. publish files;
12. записать diagnostics, hashes и provenance.

Каждый этап имеет отдельный input/output contract и может тестироваться без
полной сборки сайта.

## 4. Целевая модель объектов

### Source/config layer

- `SiteDefinition`
- `LayoutProfile`
- `LayoutOverride`
- `RegionAssignment`
- `PageDefinition`
- `SectionDefinition`
- `BlockDefinition`
- `SmartManifest`
- `SmartView`
- `SmartPreset`
- `DataSourceDefinition`

### Parsed data layer

- `DocumentAst`
- `DocumentNode`
- `MarkdownNode`
- `SmartCallNode`
- `ContentReference`
- `SourceProvenance`

### Resolved plan layer

- `ResolvedLayoutPlan`
- `ResolvedPagePlan`
- `ResolvedSectionPlan`
- `ResolvedBlockPlan`
- `ResolvedSmartPlan`
- `ResolvedAssetGraph`
- `ResolvedRenderPlan`

### Runtime result layer

- `SmartViewModel`
- `RenderArtifact`
- `BuildReceipt`
- `DiagnosticCollection`

## 5. Пример декларативной композиции

Layout profile:

```json
{
  "schema": "simai.layout_profile.v1",
  "key": "docara.docs",
  "template": "layout.docs",
  "regions": {
    "header": {"required": true, "allows": ["navigation"]},
    "sidebar": {"required": false, "allows": ["navigation"]},
    "main": {"required": true, "allows": ["content", "marketing"]},
    "outline": {"required": false, "allows": ["navigation"]},
    "footer": {"required": false, "allows": ["navigation", "content"]}
  },
  "assets": ["simai.framework", "docara.reader"]
}
```

Page descriptor:

```json
{
  "schema": "simai.page.v1",
  "layout": "docara.docs",
  "document": "install.md",
  "regions": {
    "header": {
      "$ref": "regions/header.json"
    },
    "main": {
      "operations": [
        {
          "op": "replace",
          "sections": [
            {
              "section": "docara.article",
              "params": {"document": "@page.document"}
            }
          ]
        }
      ]
    },
    "outline": {
      "operations": [
        {
          "op": "replace",
          "sections": [
            {
              "section": "docara.outline",
              "params": {"headings": "@page.document.headings"}
            }
          ]
        }
      ]
    }
  }
}
```

Section definition:

```json
{
  "schema": "simai.section.v1",
  "key": "docara.article",
  "layout": "stack",
  "blocks": [
    {
      "block": "content.markdown",
      "data": {"document": "@params.document"}
    }
  ]
}
```

Smart call inside parsed content:

```json
{
  "type": "smart",
  "smart": "ui.alert",
  "view": "default",
  "props": {
    "scheme": "info",
    "title": "Важно",
    "supportingText": "Перед обновлением создайте резервную копию."
  }
}
```

## 6. Границы владения

### Docara owns

- filesystem source discovery;
- Markdown/directive parsing;
- static-site route generation;
- search index and redirect generation;
- portable build/publish;
- static build receipt.

### Larena owns

- database/storage repositories;
- draft/publish/versioning;
- admin editor;
- access and policy checks;
- dynamic routing;
- Laravel lifecycle and runtime adapter.

### Simai Framework / Smart provider owns

- Smart manifests;
- props/slots/events schemas;
- views and presets;
- trusted templates/host renderers;
- utilities/components;
- frontend assets and hydration contracts.

### Shared platform-neutral contract

Общими должны быть:

- versioned JSON schemas;
- typed DTO/interfaces;
- resolution semantics;
- Page/Section/Block/Smart vocabulary;
- render artifact and asset graph contracts;
- compatibility fixtures.

Не следует делать Docara зависимой от пакетов с namespace/ownership Larena, а
Larena — зависимой от генератора Docara. Сначала контракт v2 можно стабилизировать
в Docara как experimental portable contract с parity fixtures в Larena. После
доказательства на двух consumers его следует вынести в маленький
dependency-free neutral package. Название и canonical repository нужно
утвердить отдельно; не следует создавать новый repository до стабилизации
контракта.

## 7. Инварианты

1. HTML — render artifact, не source of truth.
2. Layout не хранит section internals или raw page content.
3. Page не хранит block/Smart internals.
4. Section композирует blocks, но не становится Smart.
5. Block владеет сценарием и data binding; Smart не знает источник данных.
6. Smart владеет UI contract, template и assets, но не page structure.
7. Parser не рендерит HTML.
8. Resolver не исполняет template.
9. Template не читает filesystem/database/services.
10. Builder не содержит HTML/CSS/JS.
11. Assets выводятся из manifests/resolved plan.
12. Любое наследование объяснимо через resolution trace.
13. Author content не может указать исполняемый PHP path/class/callback.
14. Один и тот же fixture даёт эквивалентный resolved plan в Docara и Larena.

## 8. Безопасный переход без переписывания целиком

### A0. Architecture decision record

- утвердить vocabulary и ownership;
- зафиксировать JSON schema v2 и PHP interfaces;
- определить backward compatibility v1 -> v2;
- подготовить positive/negative fixtures.

### A1. Golden master текущего поведения

- сохранить semantic snapshots docs и landing;
- зафиксировать desktop/mobile, light/dark, navigation, search, outline;
- отделить output expectations от текущей внутренней реализации.

### A2. Parser split

- ввести `DocumentParser -> DocumentAst`;
- оставить временный adapter `DocumentAst -> legacy HTML`;
- вынести render methods из `PortableMarkdownRenderer`.

### A3. Layout and composition plan

- добавить layout profiles, regions, section/block definitions;
- реализовать cascade и region operations;
- сформировать immutable `ResolvedRenderPlan`;
- текущий renderer временно потребляет новый plan.

### A4. Smart runtime split

- выделить manifest/view/preset resolver;
- добавить presenter/view-model factory;
- добавить allowlisted template registry;
- вернуть `RenderArtifact`, включая assets/hydration/provenance.

### A5. Template and asset extraction

- вынести shell, docs, landing, navigation, outline, search и settings в
  trusted templates/Smart artifacts;
- вынести CSS/JS из `PortableHtmlRenderer` в package assets;
- оставить renderer без inline implementation details.

### A6. Thin builder

- разделить compiler и publisher;
- builder только связывает stages;
- добавить deterministic plan/build hashes и explain output.

### A7. Docara migration

- первой мигрировать docs page;
- затем landing;
- сохранить v1 adapter на один deprecation cycle;
- удалить legacy renderer только после independent acceptance.

### A8. Larena parity adapter

- загрузить те же fixtures в `larena/layout` и `larena/ui`;
- доказать semantic parity resolved plans;
- подключить Larena storage/access/routing adapters;
- не переносить Docara static publishing в Larena.

## 9. Acceptance gates

- JSON schemas: positive и negative fixtures;
- неизвестные region/section/block/smart/template IDs fail closed;
- parser AST snapshots сохраняют stable IDs и source provenance;
- exact fixture parity между Docara и Larena;
- template presentation-only static check;
- отсутствие inline CSS/JS в builder/templates, кроме явно разрешённого
  bootstrap policy;
- deterministic resolved plan и build receipt;
- asset graph не имеет moving/unresolved production references;
- no arbitrary include/path/class/callback from authored content;
- browser regression: docs + landing, desktop + mobile, light + dark;
- independent tester acceptance до удаления legacy renderer;
- отсутствие claims о production readiness до отдельной release acceptance.

## 10. Что не следует делать

- переписывать Docara целиком одним batch;
- создавать JSON-копию HTML DOM;
- складывать готовый HTML в page/region JSON;
- заставлять каждую region всегда иметь отдельный физический файл;
- передавать между стадиями неописанные associative arrays;
- создавать отдельный ручной PHP helper для каждого Smart;
- копировать Smart templates между Docara и Larena;
- разрешать template paths из пользовательского контента;
- переносить Docara generator/static publisher в Larena;
- объявлять текущие Larena/Bitrix proof implementations готовым общим runtime.

## 11. Рекомендуемая следующая цель

Не начинать с визуального переноса всех templates. Следующий bounded outcome —
архитектурный slice для одной документационной страницы:

1. Markdown -> typed `DocumentAst`;
2. `docara.docs` layout с `header/sidebar/main/outline/footer`;
3. одна `docara.article` section;
4. один `content.markdown` block;
5. один `ui.alert` Smart через manifest/view/template registry;
6. immutable `ResolvedRenderPlan`;
7. существующий и новый pipeline дают семантически одинаковую страницу;
8. никаких HTML/CSS/JS в builder/parser;
9. fixture проходит также через Larena contract adapter.

После этого slice можно масштабировать на menu, breadcrumbs, search, landing и
остальные Smart без повторной смены архитектуры.
