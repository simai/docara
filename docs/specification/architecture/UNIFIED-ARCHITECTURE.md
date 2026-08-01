# Единая архитектура Docara

## 1. Направление данных

У Docara есть один направленный поток:

```text
файлы автора
  -> discovery
  -> resolved configuration
  -> typed Document IR
  -> validated render artifacts
  -> layout composition
  -> HTML + indexes + diagnostics
```

Ни один поздний слой не становится источником для раннего. В частности, HTML
не парсится обратно, generated catalog не создаёт public prose, а UI
translation pack не хранит страницу компонента.

## 2. Ответственности модулей

```text
src/
├── Content/
│   ├── PageSourceLocator.php
│   ├── RouteMapper.php
│   └── FrontMatterParser.php
├── Config/
│   ├── ConfigResolver.php
│   ├── ConfigMerger.php
│   └── SchemaValidator.php
├── Document/
│   ├── MarkdownCompiler.php
│   ├── Document.php
│   └── Node/
├── Rendering/
│   ├── DocumentRenderer.php
│   ├── NodeRendererRegistry.php
│   └── Renderer/
├── Smart/
│   ├── SmartComponentGateway.php
│   ├── SmartComponentRegistry.php
│   ├── SmartComponentDefinition.php
│   └── RenderArtifact.php
├── Layout/
│   ├── LayoutResolver.php
│   ├── RegionComposer.php
│   └── LayoutComposer.php
├── Index/
│   ├── NavigationIndex.php
│   ├── SearchIndex.php
│   └── BacklinkIndex.php
└── Build/
    ├── PageBuilder.php
    ├── SiteBuilder.php
    └── BuildResult.php
```

Это логические границы. Маленькие value objects можно объединять, но нельзя
возвращать параллельные конвейеры.

## 3. Контракт исходной страницы

`PageSourceLocator(locale, route)` возвращает ровно один `PageSource` или
типизированную ошибку:

- `PAGE_NOT_FOUND`;
- `PAGE_ROUTE_AMBIGUOUS`;
- `LOCALE_NOT_DECLARED`;
- `SOURCE_OUTSIDE_CONTENT_ROOT`.

`PageSource` содержит path, locale, route, raw Markdown и content hash. Route
не зависит от title и перевода интерфейса.

## 4. Config resolution

Порядок наложения:

```text
engine defaults
  -> docara.json
  -> section.json от locale root к ближайшему каталогу
  -> <page>.page.json
```

Merge выполняется по schema, а не произвольным recursive merge:

- scalar заменяется;
- map объединяется только для declared extensible keys;
- list использует declared policy `replace`, `append` или `by-id`;
- `null` имеет явную семантику reset/disable только там, где это разрешено;
- unknown key — ошибка с JSON pointer и source file.

Resolved config содержит provenance каждого значения.

## 5. Document IR contract

Корень:

```json
{
  "type": "document",
  "schema_version": "1.0.0",
  "metadata": {},
  "children": [],
  "source": {"file": "content/ru/index.md", "line": 1, "column": 1}
}
```

Общие поля узла: `type`, `source`, optional `attributes`, optional `children`.
Text node использует `value`. Component node использует `name`, `props`,
`slots`. IR serializable в JSON для диагностики, но PHP runtime работает с
типизированными immutable objects.

Parser не сохраняет целый документ в одном `MarkdownNode`. Native block и
inline constructs получают собственные типы.

## 6. Node renderer registry

Registry связывает node type с renderer interface. Центральный renderer
проходит дерево и не знает HTML конкретного типа:

```text
heading -> HeadingRenderer
table -> TableRenderer
code_block -> CodeBlockRenderer
component -> ComponentNodeRenderer -> SmartComponentGateway
```

Unknown node fail-closed. Renderer получает `RenderContext` и возвращает
`RenderArtifact`, не пишет глобальные assets побочным эффектом.

## 7. Smart gateway

`SmartComponentGateway::render(ComponentInvocation, RenderContext)` —
единственная точка вызова компонентов. Manifest registry разрешает alias,
полное имя, owner, props, slots, view, normalizer и assets.

Normalizer чистый: одинаковый call/context даёт одинаковый normalized call.
Template не читает глобальные config напрямую. Любая зависимость приходит в
context и отражается в provenance/hash.

## 8. Layout composition

`LayoutComposer` получает уже отрендеренный материал `main`, resolved layout и
region plan. Он не разбирает Markdown и не знает component authoring syntax.

Region item имеет стабильный id, kind, params, visibility condition и source
provenance. Поддерживаемые kinds первой версии: `slot`, `component`, `native`,
`include`. Произвольный PHP callback в пользовательском JSON запрещён.

## 9. PageBuilder и SiteBuilder

`PageBuilder` является атомарной единицей. `SiteBuilder` только перечисляет
routes, вызывает `PageBuilder` и объединяет site-level indexes.

Full/single parity проверяется на HTML, asset manifest, headings, links и
diagnostics. Частичная сборка может использовать cache, но cache miss не меняет
результат.

## 10. Assets

Assets компонента объявлены manifest и возвращаются в `RenderArtifact`.
Asset collector deduplicates по immutable identity, сохраняет порядок
зависимостей и пишет manifest. Ручные `<link>`/`<script>` в templates не
являются штатным подключением.

Core и Smart SIMAI Framework фиксируются точными revisions. Portable output не
требует Node.js у читателя; Node допустим только в development/release
toolchain самого пакета.

## 11. Diagnostics

Каждая ошибка содержит code, severity, human message, source location,
component/renderer/config provenance и suggestion. Для CI существует JSON
формат, для автора — краткий CLI вывод.

Warnings не маскируют невозможность построить корректный route. Unknown
component/prop/node/config key является ошибкой по умолчанию.

## 12. Derived indexes

Navigation строится по route tree и metadata, outline — по heading nodes,
search — по text projection, backlinks — по link edges. Они не редактируются
вручную. Component catalog собирает manifests и Markdown metadata, но не
генерирует содержание component pages.

## 13. Extension points

Разрешённые расширения:

- новый node renderer через registry;
- новый Smart manifest/template через registry contribution;
- новый layout preset;
- новый index projector;
- новый source adapter в будущем.

Расширение не может добавлять скрытый второй `PageBuilder`, direct trusted HTML
path или редакторский текст в PHP.

## 14. Связь с Larena

Общий концептуальный контракт:

```text
source adapter -> typed presentation model -> renderer registry
               -> Smart gateway -> layout composer -> HTML
```

Docara source adapter читает Markdown/files. Larena source adapter в будущем
читает database/API. Docara не зависит от Laravel и не имитирует CMS, но её
Document IR и Smart invocation должны быть переносимы как идеи и контракты.

## 15. Миграционная граница

До завершения перехода legacy код может существовать только как frozen
baseline. Новый контент, компонент или renderer реализуется исключительно в
целевом потоке. Каждый legacy path имеет deletion gate и удаляется сразу после
parity evidence; постоянный compatibility layer не создаётся.
