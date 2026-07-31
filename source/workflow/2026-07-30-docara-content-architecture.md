# Архитектура Docara: один авторский источник и один конвейер сборки

Дата: 2026-07-30  
Статус: proposed for implementation  
Связанный workflow: `2026-07-30-docara-single-pipeline`

## Решение в одном абзаце

Пользователь создаёт страницу в одном физическом Markdown-файле. Docara читает
этот файл и настройки его окружения, преобразует Markdown в типизированное
дерево Document IR, рендерит каждый узел через единый registry, передаёт вызовы
Smart-компонентов одному gateway, помещает результат в выбранный layout и
публикует HTML. JSON/IR, поисковые индексы, каталоги и HTML являются только
производными артефактами: пользователь не редактирует их и может удалить без
потери исходного контента.

## Почему текущую реализацию нужно упростить

Сейчас один публичный материал может собираться из нескольких источников:

- физического Markdown;
- `resources/language-packs/<locale>.json`;
- записей component catalog;
- отдельных Markdown-фрагментов примеров;
- PHP-проекторов, которые сами формируют текст и HTML;
- нескольких независимых механизмов вызова Smart-компонентов.

Это создаёт четыре практические проблемы:

1. Нельзя открыть один файл и увидеть всю страницу.
2. Переводчик не понимает, какие тексты переводить и где они находятся.
3. Полная и частичная сборка рискуют использовать разные пути.
4. Добавление компонента требует менять каталог, language pack, projector и
   renderer вместо одного контракта компонента и одной Markdown-страницы.

Текущий русский language pack содержит 42 описания компонентов и занимает
около 76 КБ. Это уже не набор системных сообщений, а второй источник
документации. `PortableMarkdownRenderer`,
`PortableComponentCatalogProjector` и
`PortableDeclarativeExampleProjector` вместе превышают 3800 строк и смешивают
парсинг, редакторские тексты, локализацию, HTML и component-specific logic.

## Четыре независимых слоя

### 1. Контент

Source of truth публичной страницы:

```text
content/<locale>/<route>.md
```

Правила:

- одна публичная страница — один физический `.md`-файл;
- заголовки, абзацы, таблицы, примеры, параметры и Smart-директивы находятся в
  этом файле;
- каждая локаль имеет собственное дерево `content/<locale>`;
- отсутствие перевода видно как отсутствие файла или явный статус перевода;
- контент не хранится в PHP, component catalog или language pack.

Короткие технические метаданные допустимы во front matter этого же файла:

```markdown
---
title: Бейдж
description: Короткая метка для статуса или версии.
tags: [ui, status]
version:
  source: package
  package: ui-smart
authors:
  source: git
---
```

Автоматические поля, например дата изменения и автор, вычисляются из Git во
время сборки. Front matter задаёт политику, но не заставляет автора вручную
дублировать изменяемые значения.

### 2. Представление и композиция

JSON-настройки не содержат текст страницы и HTML. Они отвечают только за то,
как уже существующий контент размещается на сайте:

- `docara.json` — локали, маршрутизация, общий preset, branding и настройки
  сайта;
- `section.json` — наследуемый layout и состав областей раздела;
- `<page>.page.json` — редкое исключение или переопределение конкретной
  страницы;
- SIMAI Framework — визуальные токены, утилиты и базовые UI-компоненты;
- templates/views — реализация областей и Docara-owned Smart-компонентов.

Таким образом, JSON выбирает дизайн и параметры, но не реализует дизайн и не
становится вторым форматом контента.

### 3. Системные переводы

Нужно различать тексты самой Docara и тексты конкретного сайта:

- `resources/i18n/<locale>.json` — только встроенные сообщения движка:
  «Поиск», «Скопировано», «Закрыть», ошибки, accessibility labels;
- `content/<locale>/**/*.md` — вся документация и пользовательские примеры;
- `content/<locale>/site.json` — необязательные короткие site-owned подписи,
  если они не принадлежат странице: название сайта, пункты глобального меню,
  подпись CTA в шапке.

`site.json` не обязателен. Если подпись принадлежит конкретной странице, она
остаётся в Markdown. Language pack больше не содержит описаний компонентов,
параметров, примеров, ограничений или редакторских разделов.

### 4. Функциональность

Код движка отвечает только за преобразование данных:

```text
Content discovery
  -> Config resolver
  -> Markdown parser
  -> typed Document IR
  -> Node renderer registry
  -> Smart component gateway
  -> Layout composer
  -> Page builder
  -> HTML + derived indexes
```

Каждый этап имеет один вход, один результат и не пишет авторский контент.

## Рекомендуемая структура проекта

```text
project/
├── docara.json
├── content/
│   ├── ru/
│   │   ├── site.json                 # необязательные site-owned подписи
│   │   ├── index.md
│   │   └── components/
│   │       ├── section.json
│   │       ├── badge.md
│   │       └── alert.md
│   └── en/
│       ├── site.json
│       ├── index.md
│       └── components/
│           ├── section.json
│           ├── badge.md
│           └── alert.md
├── assets/                           # project-owned media
├── var/
│   └── cache/                        # disposable IR and indexes
└── build/                            # disposable public result

docara package/
├── resources/
│   ├── i18n/                         # system UI messages only
│   ├── layouts/
│   ├── regions/
│   └── smart/
│       └── docara.example/
│           ├── manifest.json
│           ├── templates/default.php
│           ├── normalize.php         # optional pure normalization
│           └── assets/               # optional behavior/style assets
└── src/
    ├── Content/
    ├── Config/
    ├── Document/
    ├── Rendering/
    ├── Smart/
    └── Build/
```

`resources/component-catalog` может остаться machine-readable registry, но не
должен содержать публичную прозу страницы. Каталог строится из manifest
компонента и метаданных Markdown-страницы либо генерируется как derived index.

## Document IR

IR — внутреннее типизированное дерево, а не ещё один формат авторинга. Для
страницы лучше обычный JSON, а не JSONL/NDJSON: документ является деревом, и
обычный JSON сохраняет его структуру. JSONL полезен только для потоковых
журналов или поискового индекса.

Минимальные типы узлов:

```text
document
heading
paragraph
text
emphasis
strong
inline_code
link
image
list
list_item
blockquote
table
code_block
thematic_break
component
```

Каждый узел содержит source location, чтобы ошибка указывала на исходный `.md`:

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

IR можно сохранять в `var/cache/ir/...json` для отладки и incremental build.
Он никогда не коммитится как обязательный источник страницы.

## Один registry узлов

`NodeRendererRegistry` сопоставляет тип IR-узла маленькому renderer:

```text
heading    -> HeadingRenderer
paragraph  -> ParagraphRenderer
list       -> ListRenderer
table      -> TableRenderer
code_block -> CodeBlockRenderer
component  -> ComponentNodeRenderer
```

Центральный renderer только проходит дерево и делегирует работу. В нём нет
огромного `match` по всем видам компонентов и нет page-specific HTML.

## Один контракт Smart-компонента

Markdown остаётся удобным для автора:

```markdown
:badge[Новое]{type=tonal scheme=primary size=1}

:::alert{type=info}
Настройка применяется после новой сборки.
:::
```

Парсер превращает обе формы в один `component` node. Затем
`SmartComponentGateway` выполняет одинаковый процесс:

1. разрешает alias и владельца компонента;
2. читает manifest;
3. нормализует и валидирует props/slots;
4. выбирает view/template;
5. рендерит HTML;
6. возвращает HTML, assets, diagnostics и provenance.

Рекомендуемый контракт:

```php
render(ComponentCall $call, RenderContext $context): RenderArtifact
```

В manifest хранятся только технические сведения: имя, provider, props schema,
views, slots, assets и accessibility contract. Шаблон хранит разметку.
Необязательный normalizer является чистой функцией преобразования props. Текст
конкретной документационной страницы в bundle компонента не хранится.

Framework-компоненты и компоненты Docara проходят один gateway:

- `provider=framework`, canonical id `ui.badge`;
- `provider=docara`, canonical id `docara.example`;
- короткий авторский alias `badge` или `example` разрешается registry.

Это устраняет нынешние независимые пути `SmartRenderer`,
`FrameworkComponentRuntime` и component-specific методы Markdown renderer.

## Один PageBuilder для всех режимов

`PageBuilder` принимает route и строит ровно одну страницу:

```text
PageRequest
  + AuthoredPage
  + ResolvedConfiguration
  + SiteIndex
  -> PageBuildResult
```

`PageBuildResult` содержит HTML, meta, assets, headings, outgoing links,
diagnostics и hashes.

- full build только перечисляет маршруты и вызывает `PageBuilder`;
- single-page build вызывает тот же `PageBuilder` для одного route;
- navigation, backlinks и search получают данные из общего `SiteIndex`;
- частичная сборка обновляет только зависимые derived indexes;
- один и тот же вход обязан давать byte-identical HTML в обоих режимах.

Ни full build, ни partial build не имеют собственного renderer или projector.

## Что делать с текущими классами

| Текущая часть | Решение |
| --- | --- |
| `DocumentParser` | Развить до полного typed IR поверх CommonMark AST. |
| `DocumentAst` | Переименовать/эволюционировать в immutable `DocumentIr`. |
| `MarkdownNode` | Удалить после появления отдельных typed Markdown nodes. |
| `PortableMarkdownRenderer` | Разделить на parser, node registry и маленькие renderers. |
| `SmartRenderer` + `FrameworkComponentRuntime` | Объединить за `SmartComponentGateway`. |
| `PortableComponentCatalogProjector` | Оставить только builder derived catalog; убрать генерацию публичных страниц. |
| `PortableDeclarativeExampleProjector` | Убрать как источник страниц; примеры сделать физическими Markdown. |
| `trustedGeneratedMainHtml` | Удалить после parity; layout получает только результат `PageBuilder`. |
| language-pack `components` | Перенести в `content/<locale>/components/*.md` и удалить из schema. |

## Порядок перехода без большого переписывания

### Этап A. Зафиксировать границы

- тест: каждый публичный document route имеет физический Markdown;
- тест: language pack разрешает только system UI keys;
- тест: JSON-конфигурация не содержит prose/HTML;
- сохранить текущий HTML representative pages как parity evidence.

### Этап B. Вертикальный срез

- одна страница `components/badge.md`;
- полноценный typed IR;
- один registry;
- один component gateway;
- одинаковая полная и частичная сборка.

Физическая страница badge уже доказала первую часть: full и single build дали
одинаковый page hash. Следующая работа — заменить coarse `MarkdownNode` на
typed nodes и провести badge через новый gateway.

### Этап C. Перенести authored pages

- перенести остальные component pages из language pack/projector в Markdown;
- перенести 14 generated demonstrator pages в физические Markdown-файлы;
- построить component index из manifest + page metadata;
- перевести локали независимо и проверять coverage.

### Этап D. Удалить старые пути

Удалять projector, trusted HTML и component-specific renderer можно только
после того, как для всех маршрутов пройдены:

- semantic/parity comparison;
- full vs single equality;
- static links;
- light/dark, desktop/mobile и LTR/RTL smoke;
- negative tests для неизвестного узла, компонента и параметра.

## Что намеренно не делаем

- не храним страницу в JSON или PHP;
- не создаём по JSON-файлу на каждый Markdown без реальной настройки;
- не смешиваем UI translations и документационную прозу;
- не позволяем Smart-компоненту становиться владельцем текста страницы;
- не создаём отдельный генератор под каталог, пример или конкретный компонент;
- не поддерживаем два способа full/single rendering;
- не редактируем generated IR и HTML вручную.

## Критерий простоты

Новый разработчик должен суметь ответить на пять вопросов без поиска по всему
репозиторию:

1. Где текст страницы? — В одном `.md`.
2. Где её layout? — В наследуемом JSON config.
3. Как Markdown превращается в HTML? — Через один typed IR и registry.
4. Как добавить компонент? — Manifest, template, optional normalizer и одна
   Markdown-страница документации.
5. Как собрать одну страницу? — Тем же `PageBuilder`, который использует полная
   сборка.

Если для новой страницы или компонента требуется ещё один projector, отдельный
runtime или текст в language pack, архитектурная граница нарушена.
