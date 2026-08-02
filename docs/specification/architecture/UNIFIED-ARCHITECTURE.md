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
translation pack не хранит страницу компонента. Публичного package-owned
translation pack вообще нет: общие видимые подписи локали читаются только из
`content/<locale>/lang.json`.

## 2. Ответственности и фактические классы

| Логическая ответственность | Фактический namespace/class |
| --- | --- |
| Поиск Markdown-owner | `Simai\Docara\Content\PageSourceLocator` |
| Route из физического пути | `Simai\Docara\Content\RouteMapper` |
| Front matter | `Simai\Docara\Content\FrontMatterParser` |
| JSON inheritance/schema | `Simai\Docara\Portable\PortableConfigurationLoader`, `ConfigurationMerger`, `SchemaRepository`, `JsonSchemaValidator` |
| Typed Document IR | `Simai\Docara\Document\MarkdownCompiler`, `DocumentIr`, typed node classes |
| Renderer registry | `Simai\Docara\Document\DocumentRendererRegistry` |
| Один component gateway | `Simai\Docara\Declarative\Smart\SmartComponentGateway` |
| Component aliases/contracts | `Simai\Docara\Document\ComponentAliasRegistry`, `ContentComponentRegistry` и общий declarative Smart registry |
| Одна страница | `Simai\Docara\PortableSite\PageBuilder`, `PageBuilderResult` |
| Полная/одиночная сборка | `Simai\Docara\PortableSite\PortableSiteBuilder` с одним `PageBuilder` |
| Navigation/search/backlinks | `PortableNavigationBuilder`, `PortableSearchIndexBuilder`, `PortableBacklinkHydrator` |
| Публикация HTML/assets | `DeclarativePortablePagePublisher`, `FrameworkAssetPublisher` |

Таблица описывает текущий код, а не желаемое дерево каталогов. Маленькие value
objects могут объединяться, но второй compiler, registry, gateway или
PageBuilder запрещён.

## 3. Контракт исходной страницы

`PageSourceLocator::forLocale()` возвращает отсортированный список физических
`PageSource(locale, path, route)`. Фактические discovery codes:

| Code | Условие |
| --- | --- |
| `PAGE_SOURCE_LOCALE_ROOT_MISSING` | нет content root локали |
| `PAGE_SOURCE_SYMLINK_FORBIDDEN` | symlink внутри публичного content |
| `PAGE_SOURCE_EXTENSION_INVALID` | публичный source не `.md` |
| `PAGE_SOURCE_ROUTE_AMBIGUOUS` | два файла дают один locale route |
| `PAGE_SOURCE_OUTSIDE_LOCALE_ROOT` | путь не принадлежит content root |
| `PAGE_SOURCE_PATH_INVALID` | parent traversal или невалидный путь |
| `LOCALE_NOT_CONFIGURED` | route ссылается на необъявленную локаль |
| `LOCALE_PAGE_MISSING` | strict missing-page policy не нашла owner |

Route не зависит от title и UI-перевода. Front matter errors используют
`FRONT_MATTER_*` codes и всегда называют relative source, line и column.

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
  "schema": "docara.document_ir.v1",
  "source": "content/ru/index.md",
  "nodes": [
    {
      "type": "heading",
      "source": {"file": "content/ru/index.md", "line": 1, "column": 1, "end_line": 1},
      "data": {"level": 1, "text": "Docara"},
      "children": []
    }
  ]
}
```

Общие поля узла: `type`, `source`, `data`, `children`. Component nodes имеют
отдельные typed classes с alias/component, props, source location и block body.
`MarkdownCompiler` создаёт typed IR только в памяти, и PHP runtime работает с
immutable objects. Обязательного page-level JSON/JSONL нет.
Сериализация допустима только как удаляемый cache, search projection,
`--dump-ir` или test evidence.

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

Unknown node fail-closed с `DOCUMENT_IR_RENDERER_UNKNOWN`. Неизвестный alias
возвращает `DOCUMENT_COMPONENT_ALIAS_UNKNOWN`; неизвестный registered content
component — `CONTENT_COMPONENT_UNKNOWN`. Renderer получает `RenderContext` и
возвращает `RenderArtifact`, не пишет глобальные assets побочным эффектом.

## 7. Smart gateway

`SmartComponentGateway` — единственная точка разрешения Smart-компонентов.
Manifest registry сначала определяет provider owner, затем зарегистрированный
resolver проверяет полное имя, props, slots, view/preset, template и assets.
Gateway не содержит веток по namespace или component ID.

View выбирается общим зарегистрированным механизмом, а не compiler branch:
явно заданный view имеет приоритет; иначе выбранный preset может ссылаться на
зарегистрированный view; при отсутствии обоих используется `default`. Binding
может передать семантическое имя preset, но не выбирает Smart по component ID.
Этот порядок одинаков для Framework, Docara и project providers.

Portable template ABI совпадает с source-pinned SF5 Smart artifact v1:
`$id` и `$smart` — строки; `$manifest`, `$view`, `$preset`, `$props` — массивы;
`$childrenHtml` и `$slot` — строки. Typed objects остаются внутри Docara
runtime. Нейтральная Framework-owned идентичность артефакта:
`contract_id=sf.smart_artifact_abi`, `schema_version=1.0.0`,
`compatibility_id=sf-smart-artifact-abi-v1`. Значение
`sf5.smart.artifact.v1` остаётся только явно названным storage compatibility
alias для исторического layout файлов; это не второй dialect и не новый
владелец. Template surface версии 1 обозначается `sf5.smart.template.v1`.
Framework consumer narrowing хранится в exact immutable lock рядом с manifest
pin, а не в PHP-карте component IDs.

Exact adapter pin `b3cdff87563ff78e7eddf044048a4b298fc69036` сохраняет
resolved view, preset, slot и hydration. Постоянный cross-host regression
рендерит один неизменённый tracked artifact под Docara и SF5 и требует
byte-identical HTML без warnings. Исторический дефект pin `d6f90bba…`
сохранён только в evidence; Docara-only обход и отдельный template dialect
запрещены.

Normalizer чистый: одинаковый call/context даёт одинаковый normalized call.
Template не читает глобальные config напрямую. Любая зависимость приходит в
context и отражается в provenance/hash.

`RegionCompositionResolver` пока сохраняет ограниченный список shell region
components. Это boundary будущего Goal 2 Design Registry, а не завершённая
часть Goal 1; он не участвует в Markdown component parsing, search projection
или Framework admission.

## 8. Layout composition

`LayoutComposer` получает уже отрендеренный материал `main`, resolved layout и
region plan. Он не разбирает Markdown и не знает component authoring syntax.

Region item имеет стабильный id, kind, params, visibility condition и source
provenance. Поддерживаемые kinds первой версии: `slot`, `component`, `native`,
`include`. Произвольный PHP callback в пользовательском JSON запрещён.

## 9. PageBuilder и PortableSiteBuilder

`PageBuilder` является атомарной единицей. `PortableSiteBuilder` только
формирует набор routes, вызывает тот же `PageBuilder` для каждого выбранного
route и объединяет site-level indexes. Single-page режим передаёт набор из
одного route; отдельной ветки parsing/rendering/composition у него нет.

Локализованные сообщения CLI/сборщика, если появятся, принадлежат пакету и
живут вне public content pipeline. `PageBuilder` их не загружает.

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
- новый Smart manifest/template через provider-owned artifact root;
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

Legacy publication paths удалены после M3/M4 parity. Target config и component
manifests не принимают prose/Markdown/HTML/CSS, `lang.json` проверяется
отдельной versioned schema, package-owned system messages исключены из
PageBuilder inputs, generated public owners равны нулю. Отрицательные guards
могут называть retired paths, но сами paths не входят в release package.
