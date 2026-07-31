# Аудит упрощения архитектуры Docara

Дата: 2026-07-31

Статус: аудит завершён, целевая архитектура предложена; системная миграция ещё не выполнена
Связанные документы:

- `2026-07-30-docara-content-architecture.md`;
- `2026-07-30-docara-single-pipeline.md`.

## Итоговый вердикт

Предложенная владельцем продукта модель правильная. Главная архитектурная
ошибка текущей Docara не в использовании массивов, а в том, что внутренние
массивы и служебные реестры стали дополнительными источниками авторского
контента. В результате Markdown, language packs, component catalog, примеры и
PHP-проекторы одновременно участвуют в создании одной публичной страницы.

Целевая Docara должна иметь:

1. один авторский источник страницы — Markdown;
2. один наследуемый источник настроек композиции — JSON-конфигурацию;
3. один внутренний типизированный формат — Document IR;
4. один registry рендереров узлов;
5. один gateway для всех Smart-компонентов;
6. один `PageBuilder` для полной и частичной сборки.

Document IR является временным результатом компиляции. Автор его не создаёт и
не редактирует. Его можно сохранить в cache для диагностики, но удаление cache
не должно приводить к потере страницы.

## Что подтверждено в текущем worktree

- найдено 59 физических Markdown-файлов; все они находятся в русской локали;
- в `docs/site/content/ru/components` физическими страницами сейчас являются
  только `badge.md` и `syntax.md`;
- `ru.json` и `en.json` содержат по 42 component records, остальные три
  language pack — по 8;
- `PortableMarkdownRenderer`, `PortableComponentCatalogProjector` и
  `PortableDeclarativeExampleProjector` вместе содержат 3845 строк;
- `PortableSiteBuilder` отдельно запускает Markdown renderer, component catalog
  projector, declarative example projector и declarative pipeline;
- параллельно существуют `InlineComponentRenderer`, `SmartRenderer` и
  `FrameworkComponentRuntime`;
- для обычной Markdown-страницы используется путь `build()`, а для страниц,
  созданных projectors, — отдельный `buildGenerated()` с передачей уже готового
  `trustedMainHtml` в основной renderer;
- текущий `DocumentParser` сохраняет большую часть документа как сырой
  `MarkdownNode`, а CommonMark AST использует преимущественно для headings и
  links; это ещё не полный типизированный Document IR;
- `SmartComponentGateway` уже умеет разрешать и `ui.*`, и `docara.*`, но
  `DocumentParser` распознаёт только жёстко перечисленные `ui.alert` и
  `ui.button`; короткие authoring aliases (`badge`, `alert` и другие) и часть
  продуктовых директив всё ещё обходят общий IR/gateway;
- component catalog и его example/typed resources остаются самостоятельным
  источником данных для публичных component pages.
- фильтр partial build применяется после построения component projections,
  examples, topology и поисковых планов; поэтому сборка одной страницы пока не
  является настоящим вызовом одного `PageBuilder` с минимальными зависимостями.

Это означает, что текущая система уже умеет выполнять нужные операции, но
делает их несколькими конкурирующими путями.

## Четыре слоя и их границы

| Слой | Что в нём хранится | Чего в нём быть не должно |
| --- | --- | --- |
| Контент | Markdown, front matter, вызовы компонентов, текст и примеры | HTML страницы, PHP-массивы с прозой, записи component catalog с текстом страницы |
| Композиция | `docara.json`, `section.json`, `<page>.page.json` | Абзацы документации, примеры, переводы содержимого страницы |
| Компоненты и дизайн | manifest, props schema, templates, assets, SIMAI Framework | Текст документации компонента и page-specific layout |
| Движок | discovery, parsing, IR, validation, rendering, indexes, build | Редакторские тексты и специальные генераторы отдельных страниц |

Производные файлы образуют отдельную пятую физическую категорию, но не новый
слой авторинга: cache Document IR, search/backlink indexes и HTML являются
результатами сборки. Их всегда можно удалить и восстановить из четырёх слоёв
выше.

### Пять легко различимых физических категорий

```text
content/                 # авторские Markdown и локализованный контент сайта
docara.json + *.json     # композиция и наследуемые настройки
resources/smart/         # manifest, templates и assets Smart-компонентов
src/                     # код универсального движка
var/cache/ + build/      # удаляемые производные файлы
```

Один файл не должен одновременно принадлежать двум категориям. В частности,
`language-pack` не может быть одновременно словарём интерфейса и базой
документационных страниц, а PHP-projector не может владеть редакторской прозой.

### Важное уточнение про дизайн

JSON не должен содержать CSS, HTML или текст статьи. Он выбирает layout,
области, presets и параметры. Сами templates, утилиты и визуальные токены
принадлежат Docara и SIMAI Framework. Поэтому конфигурация остаётся простой и
декларативной, но не становится вторым шаблонизатором.

Граница трёх файлов строгая:

- `docara.json` задаёт сайт целиком: локали, Framework lock, базовый layout,
  глобальные области и функции;
- `section.json` переопределяет эти настройки для каталога и всех потомков;
- `<page>.page.json` нужен только для исключительных настроек конкретной
  страницы.

Если странице не нужны отдельные настройки, соседний `.page.json` не создаётся.

## Единственный источник контента

Каждый публичный route каждой локали соответствует одному файлу:

```text
content/<locale>/<route>.md
```

Пример:

```text
content/
├── ru/
│   ├── index.md
│   └── components/
│       ├── section.json
│       ├── badge.md
│       └── alert.md
└── en/
    ├── index.md
    └── components/
        ├── section.json
        ├── badge.md
        └── alert.md
```

Страница компонента — обычная Markdown-страница. Manifest компонента описывает
его машинный контракт, но не владеет текстом документации.

### Front matter

Front matter содержит только короткие метаданные самого материала. Настройки
дизайна и областей сюда не попадают:

```markdown
---
title: Бейдж
description: Короткая метка для статуса или версии.
tags: [ui, status]
draft: false
---
```

Дата изменения, автор, revision и версия пакета по возможности вычисляются при
сборке из Git и lock-файлов. Они не дублируются вручную в Markdown.

## Переводы

Нужно разделить три вида текста:

1. `resources/i18n/<locale>.json` пакета Docara — встроенные системные сообщения
   движка:
   «Поиск», «Закрыть», «Скопировано», ошибки и accessibility labels;
2. `content/<locale>/**/*.md` — вся документация и весь page-owned контент;
3. необязательный `content/<locale>/site.json` — переводы конкретного сайта,
   которые не принадлежат одной странице: название, глобальная навигация,
   подписи форм, CTA шапки и допустимые переопределения системного UI.

Описания компонентов, параметры, примеры, ограничения и редакторские разделы
нельзя хранить в language pack. Пакет Docara поставляет безопасные UI defaults,
а сайт при необходимости переопределяет их рядом со своей локализованной
документацией.

## Внутренний формат: Document IR

Markdown компилируется в типизированное дерево. Это не новый формат авторинга и
не JSONL. Для дерева документа подходит обычный JSON; JSONL можно использовать
только для журналов и поискового индекса.

Минимальные узлы:

```text
document, heading, paragraph, text, emphasis, strong, inline_code,
link, image, list, list_item, blockquote, table, code_block,
thematic_break, component
```

Пример узла компонента:

```json
{
  "type": "component",
  "name": "badge",
  "props": {
    "type": "tonal",
    "scheme": "primary",
    "size": "1"
  },
  "slots": {
    "default": [
      {"type": "text", "value": "Новое"}
    ]
  },
  "source": {
    "file": "content/ru/components/badge.md",
    "line": 18,
    "column": 1
  }
}
```

`source` обязателен: ошибка неизвестного параметра должна указывать на исходную
строку Markdown, а не на generated HTML.

## Единственный конвейер

```text
PageSourceLocator
  -> ConfigResolver
  -> MarkdownCompiler
  -> Document IR
  -> NodeRendererRegistry
       -> component node: SmartComponentGateway
  -> LayoutComposer
  -> PageBuilderResult
```

`PageBuilderResult` содержит:

- HTML;
- assets;
- metadata;
- headings и исходящие ссылки;
- diagnostics и provenance;
- hashes для incremental build.

`SiteBuilder` не рендерит страницы. Он только перечисляет routes и вызывает тот
же `PageBuilder`, которым собирается одна страница. Это гарантирует одинаковый
результат full build и single-page build.

### Полная и частичная сборка

Должны существовать две команды, но не два конвейера:

```text
build-site
  -> перечислить routes
  -> для каждого route вызвать PageBuilder

build-page <locale> <route>
  -> найти один source
  -> разрешить только его настройки и общие зависимости
  -> вызвать тот же PageBuilder
```

`build-page` не должен сначала генерировать весь component catalog, все examples
и весь поисковый план, а затем отбрасывать лишние страницы. Глобальный индекс
может быть передан `PageBuilder` как уже вычисленная dependency, но создание
публичной страницы всегда остаётся локальной операцией над одним Markdown.

### Что происходит с одной страницей

1. `PageSourceLocator` находит ровно один физический Markdown-файл по route и
   locale.
2. `ConfigResolver` объединяет настройки сайта, всех родительских разделов и
   необязательной page-конфигурации.
3. `MarkdownCompiler` один раз разбирает front matter, Markdown и Smart-синтаксис
   в типизированное дерево.
4. `DocumentRenderer` проходит дерево; `NodeRendererRegistry` выбирает renderer
   для каждого типа узла.
5. Узел `component` всегда передаётся в `SmartComponentGateway`.
6. `LayoutComposer` помещает результат документа в настроенные области.
7. `PageBuilder` возвращает HTML и все производные данные одной атомарной
   структурой.

Ни на одном шаге готовый HTML не возвращается обратно в parser и не
подставляется в обход renderer registry.

## Рендеринг обычных Markdown-узлов

`NodeRendererRegistry` сопоставляет тип IR-узла маленькому renderer. Центральный
цикл не знает деталей таблицы, кода или компонента:

```text
heading    -> HeadingRenderer
paragraph  -> ParagraphRenderer
table      -> TableRenderer
code_block -> CodeBlockRenderer
component  -> ComponentNodeRenderer
```

Это не требует отдельного большого класса для каждой HTML-мелочи. Простые узлы
могут обслуживаться компактными чистыми render functions. Важна одна точка
регистрации и отсутствие второго renderer.

## Единый Smart Component Gateway

Автор пишет:

```markdown
:badge[Новое]{type=tonal scheme=primary size=1}

:::alert{type=info}
Настройка применяется после новой сборки.
:::
```

Парсер создаёт одинаковый `component` node. Gateway:

1. разрешает alias и владельца;
2. загружает manifest;
3. валидирует props и slots;
4. при необходимости запускает чистый normalizer;
5. выбирает template;
6. возвращает `RenderArtifact`.

```php
render(ComponentInvocation $call, RenderContext $context): RenderArtifact
```

`RenderArtifact` должен содержать не только HTML, но и assets, diagnostics и
provenance. Тогда скрипты и стили компонента не подключаются скрытым побочным
путём.

Граница владельцев сохраняется:

- `ui.*` — компоненты SIMAI Framework;
- `docara.*` — продуктовые компоненты Docara;
- короткие aliases для Markdown разрешает общий registry;
- оба вида компонентов проходят один gateway.

## Минимальная структура кода движка

```text
src/
├── Content/
│   ├── PageSourceLocator.php
│   └── FrontMatterParser.php
├── Config/
│   └── ConfigResolver.php
├── Document/
│   ├── MarkdownCompiler.php
│   └── Node/
├── Rendering/
│   ├── DocumentRenderer.php
│   └── NodeRendererRegistry.php
├── Smart/
│   ├── SmartComponentGateway.php
│   ├── SmartComponentRegistry.php
│   └── SmartComponentDefinition.php
├── Layout/
│   └── LayoutComposer.php
└── Build/
    ├── PageBuilder.php
    └── SiteBuilder.php
```

Это логические модули, а не требование раздробить код на максимальное число
файлов. Упрощение достигается одним направлением данных и одной ответственностью
модуля.

## Предлагаемая структура проекта сайта

```text
my-docs/
├── docara.json
├── redirects.json
├── simai-framework.lock.json
├── content/
│   ├── ru/
│   │   ├── site.json
│   │   ├── index.md
│   │   ├── section.json
│   │   └── components/
│   │       ├── index.md
│   │       ├── section.json
│   │       ├── badge.md
│   │       └── alert.md
│   └── en/
│       └── ...
└── assets/
```

Правило маршрута простое:

- `content/ru/index.md` -> `/ru/`;
- `content/ru/components/index.md` -> `/ru/components/`;
- `content/ru/components/badge.md` -> `/ru/components/badge/`.

`index.md` является обычной страницей, а не скрытым projector. Если на ней
нужен автоматически обновляемый список компонентов, Markdown вызывает один
продуктовый Smart-компонент каталога. Сам список строится из manifests и
metadata Markdown, но вводный текст страницы остаётся в `index.md`.

## Где живёт реализация Smart-компонента

```text
resources/smart/docara.alert/
├── manifest.json
├── templates/
│   └── default.php
├── views/
└── assets/
```

Manifest содержит только машинный контракт: имя, props, slots, views, assets и
renderer/normalizer. Текст страницы «Уведомление», описание параметров и
примеры находятся в `content/<locale>/components/alert.md`.

Короткий authoring alias (`alert`, `badge`) разрешается центральным registry в
полное имя. Alias не меняет владельца:

- `ui.*` остаётся собственностью SIMAI Framework;
- `docara.*` остаётся продуктовым слоем Docara;
- центральный builder не содержит `if`/`switch` для конкретного компонента.

## Приоритет настроек

```text
engine defaults
  -> docara.json
  -> section.json от корня к ближайшему разделу
  -> <page>.page.json
```

Front matter объединяется отдельно как metadata материала и не переопределяет
layout/regions. Это сохраняет реальное разделение контента и дизайна.

`.page.json` нужен только когда у страницы действительно есть отдельная
настройка. Не следует создавать пустой JSON рядом с каждым Markdown-файлом.

## Что оставить, преобразовать и удалить

| Текущая часть | Действие |
| --- | --- |
| CommonMark parsing | Оставить как основу `MarkdownCompiler`. |
| `DocumentParser` / `DocumentAst` | Эволюционировать в полный immutable Document IR. |
| `PortableMarkdownRenderer` | Разделить на compiler, registry и node renderers. |
| `SmartRenderer`, `FrameworkComponentRuntime`, `InlineComponentRenderer` | Свести за один `SmartComponentGateway`. |
| `PortableComponentCatalogProjector` | Оставить только генерацию derived index; убрать генерацию публичной прозы. |
| `PortableDeclarativeExampleProjector` | Удалить после переноса примеров в Markdown. |
| language-pack `components` | Перенести в Markdown и удалить из schema/model/repository. |
| `component-catalog/examples` | Перенести page-owned examples в Markdown. |
| `component-catalog/typed` | Оставить только если это manifest/contract; не хранить там прозу страницы. |
| trusted/generated page HTML path | Удалить после parity-проверки нового `PageBuilder`. |

### Точные ворота удаления старого кода

Старые механизмы удаляются не по дате, а после проверяемой замены:

1. генерация public pages в `PortableComponentCatalogProjector` удаляется,
   когда каждый component route имеет физический Markdown и совпадает с
   baseline;
2. `PortableDeclarativeExampleProjector` удаляется, когда примеры принадлежат
   Markdown-страницам или вызываемым из них универсальным Smart-компонентам;
3. `DeclarativePipeline::buildGenerated()` и `trustedMainHtml` удаляются, когда
   generated и authored sources проходят один `PageBuilder`;
4. ранний `FrameworkComponentRuntime::extract()`, `InlineComponentRenderer` и
   параллельный `SmartRenderer` удаляются, когда все aliases компилируются в
   один `component` node и проходят `SmartComponentGateway`;
5. поле `components` удаляется из language-pack schema/model только после того,
   как все необходимые локализованные страницы существуют физически;
6. coarse `MarkdownNode` удаляется после появления типизированных block/inline
   nodes и их renderer tests.

До прохождения этих ворот старый путь может оставаться временным read-only
baseline, но в него больше нельзя добавлять новый контент или новые
component-specific ветки.

## Производные артефакты

Все следующие файлы можно удалить и восстановить:

```text
var/cache/ir/**/*.json
var/cache/site-index.json
var/cache/search-index.jsonl
build/**/*.html
```

Component catalog, backlinks, search и navigation строятся из Markdown,
manifest и resolved configuration. Они не являются источниками истины.

## Порядок миграции

Миграция выполняется вертикальными срезами, а не одновременной переписью всех
классов. Первый срез — `components/badge.md`: от физического файла до итогового
HTML без projector и trusted HTML. Только после parity-проверки этот путь
расширяется на остальные страницы.

### M1. Закрыть границы источников

- запретить prose/HTML в config schemas;
- сократить language packs до runtime/UI messages;
- ввести проверку «один public route — один Markdown»;
- сохранить representative HTML как parity evidence.

### M2. Один вертикальный срез

- провести `components/badge.md` через новый compiler, IR, registry, gateway и
  `PageBuilder`;
- подтвердить равенство full и single-page сборки;
- проверить assets, links, светлую/тёмную тему, desktop/mobile и LTR/RTL.

### M3. Перенести страницы

- создать физический Markdown для всех component pages и generated examples;
- мигрировать локали независимо;
- строить каталог из manifest + Markdown metadata.

### M4. Удалить параллельные пути

- удалить component page projection;
- удалить declarative example projection;
- удалить trusted/generated HTML path;
- удалить component-specific branches из старого Markdown renderer;
- удалить `components` из language-pack schema и PHP model.

### M5. Упростить публичный API

- одна команда full build;
- одна команда single-page build;
- обе вызывают один `PageBuilder`;
- diagnostics всегда ссылаются на Markdown source location.

## Критерии приёмки

Архитектура считается упрощённой, когда:

1. любой текст публичной страницы находится в одном Markdown-файле;
2. удаление `var/cache` и `build` не удаляет исходные данные;
3. language packs не содержат документационную прозу;
4. component manifest не содержит текста своей документационной страницы;
5. новый компонент не требует нового projector или ветки в центральном
   renderer;
6. full и single-page build дают одинаковый HTML и assets для одной revision;
7. неизвестный node, component или prop завершается понятной ошибкой с файлом и
   строкой;
8. контент, композиция, templates и engine code можно изменять независимо;
9. новый разработчик находит текст, layout и renderer без поиска по всему
   репозиторию.

## Связь с Larena

Docara не должна превращаться в уменьшенную CMS. Общим с Larena является
конвейер:

```text
source data -> typed presentation model -> renderer registry
            -> Smart gateway -> layout composer -> HTML
```

Различается только source adapter:

- Docara получает authored content из Markdown и файловой конфигурации;
- Larena в будущем получает уже структурированные данные из базы и API.

Document IR Docara должен быть концептуально совместим с presentation model
Larena, но не обязан повторять Laravel runtime или хранить страницы в базе.

## Короткое правило для последующей разработки

Если для новой страницы приходится добавлять текст в language pack, PHP,
component catalog или отдельный projector, архитектура снова отклонилась от
целевой модели. Новый публичный материал начинается с Markdown, а новый
визуально-функциональный кирпич — с manifest/template и регистрации в общем
Smart gateway.

Ещё короче:

```text
Пишем страницу в Markdown.
Настраиваем композицию в JSON.
Реализуем повторно используемый блок как Smart-компонент.
Всё остальное движок вычисляет и может пересоздать.
```
