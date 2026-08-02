# Техническое задание: единая архитектура Docara

Версия: 1.0
Дата: 2026-08-01
Статус: реализованный архитектурный контракт; release требует отдельного gate
Базовый снимок: исторический M0 `a3ba9a4d04429f1f2046b8415764fe7bc89962c7`

## 1. Назначение продукта

Docara — простой переносимый генератор статических сайтов для документации,
справочников, баз знаний и продуктовых лендингов. Автор работает с Markdown и
не обязан знать устройство PHP-рендерера. Дизайн и композиция задаются
небольшими JSON-файлами. Повторно используемые визуально-функциональные блоки
создаются как Smart-компоненты и собираются на SIMAI Framework.

Docara должна быть достаточно простой для одиночного автора и достаточно
строгой для большой многоязычной документации, автоматической сборки и работы
ИИ-агента.

## 2. Пользовательский результат

Из пустого каталога пользователь создаёт сайт, пишет страницы в Markdown,
настраивает внешний вид декларативно, запускает одну команду и получает
детерминированный статический сайт. Он может пересобрать весь сайт или одну
страницу, переключить язык и тему, найти материал, использовать компоненты,
обновить движок без перезаписи своего контента и повторить сборку с тем же
результатом.

## 3. Главный принцип

```text
Пишем страницу в Markdown.
Настраиваем композицию в JSON.
Реализуем повторно используемый блок как Smart-компонент.
Всё остальное движок вычисляет и может пересоздать.
```

## 4. Четыре независимых слоя

### 4.1. Контент

Контентом являются физические Markdown-файлы в `content/<locale>`. Один
публичный route одной локали имеет ровно один исходный Markdown-файл по
контракту `content/<locale>/<route>.md`. Для раздела рекомендуется плоский
owner рядом с одноимённым каталогом: `/ru/components/` принадлежит
`content/ru/components.md`, а дочерние страницы живут в `components/`.
Совместимая форма `components/index.md` разрешена, но обе формы одновременно
запрещены как неоднозначные. Заголовки, абзацы,
таблицы, код, примеры, описания параметров и вызовы компонентов находятся в
этом файле.

В контенте запрещены:

- готовый HTML страницы, кроме явно разрешённого безопасного HTML-компонента;
- PHP-массивы с редакторским текстом;
- ссылки на внутренние generated-artifacts как на источник;
- дублирование той же страницы в language pack или catalog record.

### 4.2. Композиция

Композиция выбирает layout, области, presets, функции сайта и параметры
представления:

- `docara.json` — сайт целиком;
- `section.json` — каталог и все его потомки;
- `<page>.page.json` — только исключения конкретной страницы.

JSON не содержит абзацы статьи, CSS или шаблонный HTML. Если отдельная
страница не имеет исключений, `.page.json` рядом с ней не создаётся.

### 4.3. Компоненты и дизайн

SIMAI Framework владеет токенами, утилитами, базовыми и универсальными
Smart-компонентами `ui.*`. Docara владеет только продуктовыми блоками
`docara.*`, нужными для документационных сайтов. Каждый компонент имеет
manifest, schema параметров, template, assets и тесты, но не текст своей
публичной документационной страницы.

### 4.4. Движок

Движок выполняет discovery, config resolution, parsing, построение Document IR,
валидацию, rendering, composition и генерацию производных индексов. В коде
движка нет редакторской прозы и component-specific projectors публичных
страниц.

## 5. Физическая структура сайта пользователя

```text
my-docs/
├── docara.json
├── redirects.json
├── simai-framework.lock.json
├── content/
│   ├── ru/
│   │   ├── lang.json
│   │   ├── index.md
│   │   ├── section.json
│   │   ├── components.md
│   │   └── components/
│   │       ├── section.json
│   │       ├── badge.md
│   │       └── alert.md
│   └── en/
│       └── ...
└── assets/
```

Маршруты выводятся без скрытой магии:

- `content/ru/index.md` -> `/ru/`;
- `content/ru/components.md` -> `/ru/components/`;
- `content/ru/components/index.md` -> `/ru/components/` как совместимая
  альтернативная форма, если `components.md` отсутствует;
- `content/ru/components/badge.md` -> `/ru/components/badge/`.

## 6. Мультиязычность

Количество локалей не ограничено. Каждая локаль имеет своё полное дерево
страниц. Route между локалями связывается относительным путём, а при
необходимости — явным translation key в front matter.

В целевой архитектуре текст делится на два публичных контура:

1. `content/<locale>/**/*.md` — редакторский контент страниц;
2. `content/<locale>/lang.json` — только повторяющиеся видимые интерфейсные
   строки локали: поиск, содержание, копирование, переходы, accessibility
   labels и другие общие подписи.

Публичного `resources/i18n` нет. Бренд, меню, CTA, layout и поведение задаются
структурированными полями `docara.json`, `section.json` и `.page.json`, но эти
файлы не владеют статьями. Обратная совместимость с `site.json` не требуется.

Сообщения CLI, сборщика и внутренних ошибок не смешиваются с контентом сайта.
Если им нужна локализация, это package-owned системный контур, который не
загружается `PageBuilder` и не участвует в сборке публичных страниц.

Отсутствующий перевод страницы не заменяется содержимым другой локали без
явной политики. Сборка должна либо пропустить route, либо завершиться
диагностикой согласно `locales.missing_page_policy`. Значение `skip` публикует
только существующие owner, `error` требует route во всех объявленных локалях и
возвращает `LOCALE_PAGE_MISSING`; editorial fallback отсутствует.

## 7. Единственный конвейер страницы

```text
PageSourceLocator
  -> PortableConfigurationLoader
  -> MarkdownCompiler
  -> typed Document IR
  -> DocumentRendererRegistry
       -> component node: SmartComponentGateway
  -> LayoutComposer
  -> PageBuilderResult
```

`PageBuilderResult` содержит HTML, assets, metadata, headings, links,
diagnostics, provenance и hashes для incremental build. Готовый HTML не
возвращается в parser и не подставляется через `trustedMainHtml`.

## 8. Document IR

IR — immutable производный формат между Markdown и HTML. `MarkdownCompiler`
создаёт типизированный `Document` только в памяти, автор не пишет его вручную.
Обязательных промежуточных JSON/JSONL-файлов страницы нет. Сериализация
разрешена лишь как полностью удаляемый cache, поисковый индекс,
диагностический `--dump-ir` или test evidence; удаление любого такого файла не
меняет источник истины и не препятствует полной пересборке.

Обязательные типы узлов:

```text
document, heading, paragraph, text, emphasis, strong, inline_code,
link, image, list, list_item, blockquote, table, code_block,
thematic_break, component, raw_html
```

Каждый узел содержит source location: файл, строку и колонку. Любая ошибка
неизвестного синтаксиса, компонента, параметра или slot должна указывать на
исходный Markdown, а не на generated HTML.

`raw_html` создаётся только безопасным, явно разрешённым механизмом. По
умолчанию произвольный HTML sanitizes или запрещается в соответствии с
профилем сайта.

## 9. Markdown и front matter

Front matter содержит метаданные материала, но не layout:

```markdown
---
title: Бейдж
description: Короткая метка для статуса или версии.
tags: [ui, status]
draft: false
translation_key: components.badge
---
```

Runtime принимает только `title`, `description`, `tags`, `draft` и
`translation_key`. `draft: true` исключает страницу из полной публикации.
Неизвестное поле, неверный тип, незакрытый блок и ошибочный identifier
fail-closed с source file, line и column. Front matter вырезается до
`MarkdownCompiler`, сохраняя номера строк исходного файла.

Дата изменения, автор, commit, версия пакета и совместимость вычисляются из Git,
manifest и lock-файлов, если источник доступен. Ручное значение допускается
только как явный override и отмечается provenance.

Обычные возможности Markdown не превращаются в отдельные компоненты:
заголовки, абзацы, списки, ссылки, изображения, таблицы, цитаты, inline code,
code fences и thematic break остаются Markdown.

## 10. Smart-компоненты

Inline-вызов начинается одним двоеточием:

```markdown
:badge[Новое]{type=tonal scheme=primary size=1}
```

Block-вызов использует контейнер:

```markdown
:::alert{type=info}
Настройка применяется после новой сборки.
:::
```

Парсер превращает оба варианта в один IR node `component`. Gateway:

1. разрешает короткий alias и полное имя владельца;
2. загружает manifest;
3. валидирует props, slots и вложенность;
4. запускает чистый normalizer, если он объявлен;
5. выбирает template/preset;
6. возвращает `RenderArtifact` с HTML, assets, diagnostics и provenance.

Граница владельцев:

- `ui.*` — SIMAI Framework;
- `docara.*` — продукт Docara;
- объявленный namespace проекта — фиксированный project provider из `smart/`;
- `badge`, `alert`, `example` и другие aliases — только удобные authoring names;
- центральный builder не содержит `if`/`switch` для конкретного компонента.

Project Smart использует tracked SF5 Smart artifact v1: `manifest.json`,
`view/`, необязательный `preset/`, `template/` и объявленные `assets/`.
Namespace задаётся в `docara.json`, но путь к PHP/template никогда не приходит
из authored config или Markdown. Provider ownership выбирает resolver внутри
единственного Gateway.

## 11. Layout и области

Layout является декларативным деревом областей. Базовые области документации:
`header`, `navigation`, `main`, `outline`, `footer`, `overlay`. Лендинг может
использовать `header`, `main`, `footer` без документационной оболочки.

Область не является Smart-компонентом. Она содержит упорядоченный список
blocks, каждый из которых может быть Smart-компонентом, native node, slot или
условным include. Область можно включить, отключить или переопределить на
уровне сайта, раздела или страницы.

Composition config определяет «что и где», template определяет «как
отрисовать», а Markdown определяет «что сказано».

## 12. SIMAI Framework

Сборка использует точный immutable Core/Smart tuple из
`simai-framework.lock.json`. Moving references (`main`, `latest`) в
воспроизводимой сборке запрещены.

Docara сначала использует framework tokens, utilities, components и Smart
components. Локальный CSS допустим только для продуктовой композиции или
временного доказанного gap. Универсальная недостающая возможность оформляется
как предложение в Framework, а не как скрытый костыль Docara.

В пользовательских текстах продукт называется «SIMAI Framework». Техническое
обозначение версии не включается в названия компонентов, aliases и CSS-классы.

## 13. Компонентная документация

Каждый документируемый компонент имеет отдельный Markdown-route вида
`/components/<alias>/`. Страница компонента состоит только из полезных частей:

1. название;
2. короткое объяснение назначения;
3. общий интерактивный пример без лишнего заголовка «Пример»;
4. по одному разделу на параметр: понятное название, имя параметра в тексте,
   краткое назначение, значение по умолчанию, таблица допустимых значений,
   компактный пример с исходным кодом;
5. только реальные ограничения и accessibility notes.

Служебные разделы «Источник», «Состояния», «О компоненте» и пустое «Важно» не
выводятся. Index `/components/` — краткий список ссылок по категориям, без
технического реестра на экране.

Компонент `example` объединяет результат и исходники во вкладках. Первая
вкладка называется «Пример», остальные — `Markdown`, `HTML`, `CSS`,
`JavaScript` или иным реальным языком. Код копируется, иконка временно меняется
на подтверждение. Вкладки доступны с клавиатуры и не меняют внешнюю высоту
без необходимости.

## 14. Полная и частичная сборка

Полная и одиночная сборка используют один `PageBuilder` и один и тот же
конвейер. Различается только набор route, переданный в него:

```text
docara build
docara build-page <locale> <route>
```

`build-page` не строит сначала весь сайт. Селектор выбирает один route до
компиляции, после чего тот же `PageBuilder` находит source, разрешает его
config chain и необходимые shared dependencies, строит страницу и локально
обновляет зависимые indexes. Результат route при full и single-page build для
одной revision обязан быть семантически и байтово одинаковым, кроме явно
документированных timestamp-free manifests.

## 15. Производные артефакты

Следующее всегда можно удалить и восстановить:

```text
var/cache/ir/**/*
var/cache/site-index.json
var/cache/search-index.jsonl
var/cache/backlinks.json
build/**/*.html
```

IR-cache необязателен. Search, navigation, outline, backlinks и component catalog строятся из
Markdown metadata, manifests и resolved config. Ни один индекс не становится
авторским источником страницы.

## 16. Обновление Docara

Движок, starter и пользовательский проект разделены. Обновление пакета:

- никогда не перезаписывает `content/`, `assets/` и пользовательские config;
- публикует versioned schemas и migration notes;
- предоставляет `verify`, `diff/dry-run` и только затем явный `apply` для
  структурных миграций;
- сохраняет rollback package/lock;
- проверяет совместимость SIMAI Framework tuple до сборки.

Starter создаёт новый проект. Он не является шаблоном, который молча
накладывается на существующий сайт при каждом обновлении.

## 17. Безопасность

- raw HTML по умолчанию запрещён или sanitizes;
- внешние embeds проходят allowlist и sandbox policy;
- URL, paths и includes не могут выходить за разрешённые roots;
- generated HTML экранирует пользовательский текст;
- secrets не попадают в output, search index и diagnostics;
- режим serve не открывается наружу без явного host-параметра;
- ошибки не раскрывают private absolute paths в публичной сборке.

## 18. Производительность и детерминизм

- одинаковые inputs и locks дают byte-identical output;
- сборка не зависит от сети после подготовки immutable dependencies;
- hashes учитывают Markdown, config chain, manifests, templates и assets;
- изменившаяся страница перестраивается вместе только с доказанными
  зависимостями;
- search/index generation не должен заставлять page preview компилировать все
  public routes;
- порядок filesystem discovery нормализован и стабилен.

## 19. Закрытая legacy-граница

После parity и rollback evidence удалены public `resources/i18n`, package
language packs, `site.json` compatibility, generated component/example page
projectors, `trustedMainHtml` и отдельный `buildGenerated()` path. Текущий
Framework Smart directives больше не извлекаются и не гидратируются до
`MarkdownCompiler`: `ui.*`, `docara.*` и project-local Smart создают один
`smart_component` узел typed Document IR и разрешаются одним
`SmartComponentGateway`. `FrameworkComponentRuntime` после Gateway только
формирует детерминированный asset plan и diagnostics уже выполненных вызовов;
он не рендерит HTML страницы.
`SourceNode` представляет отдельные типизированные native blocks; whole-page
coarse Markdown node отсутствует.

Дальнейшее удаление допустимо только после zero-reference и parity, но новые
возможности в legacy-путь больше не добавляются.

## 20. Не-цели

- Docara не становится CMS и не получает базу данных;
- Docara не встраивает Laravel ради шаблонизации;
- пользователь не редактирует IR или generated HTML;
- config не превращается во второй язык программирования;
- в первую стабильную версию не входит визуальный drag-and-drop builder;
- спецификация не заявляет production readiness до независимой приёмки.

## 21. Definition of Done

Цель достигнута, когда все критерии из [ACCEPTANCE.md](ACCEPTANCE.md) имеют
PASS evidence, legacy deletion gates закрыты, документация соответствует
реальным командам, а чистая установка строит демонстрационный многоязычный
сайт полной и частичной сборкой.

## 22. Управление изменениями

Архитектурное изменение сначала оформляется в `graph/specs/decisions` с
причиной, альтернативами и последствиями. Реализация начинается только после
привязки requirement -> batch -> code/tests/evidence. Длинный чат может дать
контекст, но не может незаметно изменить этот контракт.
