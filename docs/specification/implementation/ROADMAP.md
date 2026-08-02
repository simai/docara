# Дорожная карта упрощения Docara

Статус: M0-M4 завершены с evidence; M5 product stabilization выполняется перед
отдельной read-only acceptance. Другие локали, release и production не заявлены

Переход выполняется вертикальными срезами. Цель — не переписать весь код за
один раз, а доказать новый единственный конвейер на одной реальной странице,
после чего последовательно удалить старые пути.

## Нулевая точка

- исходная revision: `a3ba9a4d04429f1f2046b8415764fe7bc89962c7`;
- ветка спецификации: `codex/docara-unified-architecture`;
- текущий старый runtime остаётся временным read-only baseline;
- никакая готовность к релизу или production не заявляется;
- несовместимые изменения допустимы: Docara 2 ещё не опубликована.

## M0. Зафиксировать контракт и карту реализации

Результат:

- утверждены ТЗ, authoring contract, архитектура и acceptance matrix;
- каждый целевой модуль сопоставлен текущим классам, тестам и deletion gates;
- сняты воспроизводимые baseline-артефакты для `components/badge`;
- запрещено добавлять новый контент в language packs и projectors;
- принят отдельный `badge_source_ready` gate: он разрешает M2 после закрытия
  badge-среза и zero-growth границ, не выдавая ложный PASS глобальной миграции.

На этом этапе продуктовый runtime не переписывается.

## M1. Закрыть границы источников

Статус: реализован ограниченными checkpoint M1A и M1B; переход к M2 разрешён
только scoped gate `docara.gate.badge_source_ready`, не глобальным
`source_ownership`.

Результат:

- публичная страница требует физический Markdown-файл;
- config schemas отклоняют prose, HTML и CSS;
- `content/<locale>/lang.json` является единственным public i18n source;
- public `resources/i18n` и `site.json` исключены без compatibility layer;
- package-owned CLI/build messages отделены от public build inputs;
- component manifest не может владеть текстом своей документационной страницы;
- generated/cache каталоги явно объявлены disposable.

Ключевые тесты:

- route collision;
- route без Markdown;
- forbidden content in config/language pack/manifest;
- удаление cache + полное воспроизведение результата;
- отсутствие обязательных page IR JSON/JSONL.

## M2. Вертикальный срез `components/badge`

Статус: принят ограниченный вертикальный срез. Typed in-memory IR, общий
registry, существующий Smart gateway и единый PageBuilder доказаны на Badge;
глобальная миграция и удаление legacy не заявлены.

Страница `content/ru/components/badge.md` проходит полный целевой путь после
локального gate `badge_source_ready`:

```text
PageSourceLocator -> ConfigResolver -> MarkdownCompiler -> Document IR
-> NodeRendererRegistry -> SmartComponentGateway -> LayoutComposer
-> PageBuilderResult
```

Результат:

- физический Markdown владеет всей прозой и примерами страницы;
- все native и component nodes имеют source location;
- alias `badge` разрешается registry, а не условием в parser;
- assets возвращаются через `RenderArtifact`;
- full и single-page build byte-identical для выбранной страницы;
- оба режима используют один PageBuilder pipeline и различаются только route
  selection;
- новый путь не принимает `trustedMainHtml`.

Приёмка: PHP/unit tests, exact HTML/assets parity, светлая/тёмная тема,
desktop/mobile, LTR и broken links 0. RTL остаётся locale-wide проверкой M3/M5,
поскольку M2 ограничен русской страницей.

## M3. Перенести публичные страницы

Статус: русский component-раздел завершён — 32/32 route имеют физический
Markdown-owner, производные представления используют результаты PageBuilder,
русские prose projections удалены после parity/zero-reference, интеграционная
приёмка PASS. Этот checkpoint не заявляет миграцию других локалей.

Результат:

- у каждого route есть Markdown в своей locale;
- страницы компонентов и examples больше не создаются projectors;
- каталог строится из manifest + metadata Markdown и вставляется вызовом
  одного продуктового компонента;
- локали мигрируются независимо, без silent fallback редакторского текста;
- single-page build не генерирует полный каталог и все примеры перед фильтром.

Миграция выполняется небольшими пакетами страниц. После каждого пакета
сохраняется parity evidence и обновляется implementation mapping.

## M4. Удалить параллельные пути

Статус: завершён для всех 103 текущих русских публичных route; projectors,
generated owners/allowlist и trusted-main bypass удалены после parity и
zero-reference evidence.

Удаление разрешено только после закрытия соответствующего gate:

1. публичная page projection в component catalog — после физического Markdown
   и parity всех component routes;
2. declarative example projector — после переноса примеров в Markdown и
   универсальные Smart-компоненты;
3. `buildGenerated()` и `trustedMainHtml` удалены в M4 после перехода всех 103
   русских страниц на один typed `PageBuilder` artifact;
4. ранние/параллельные Smart renderers — после единого IR component node и
   gateway;
5. public `resources/i18n`, `site.json` compatibility и поле `components` в
   language-pack — после физического контента всех локалей;
6. coarse raw Markdown node — после типизированного block/inline IR и тестов.

Временный compatibility layer не становится публичным API и удаляется в том же
миграционном треке.

## M5. Стабилизировать публичный продукт

Статус: implementation batch активен; независимый acceptance batch остаётся
заблокированным до exact clean candidate.

Результат:

- один `build` для сайта и тот же `PageBuilder` для `--page`;
- чистый `init` создаёт понятный переносимый проект;
- update отделяет engine-owned файлы от project-owned content/config/assets;
- документация описывает только реально работающие команды;
- diagnostics ссылаются на source location;
- все acceptance criteria имеют immutable evidence.

Только после M5 разрешён отдельный release gate. Merge в default branch, tag,
GitHub release и production deployment не являются частью этой дорожной карты.

## Порядок работы нового треда

Новый тред начинает не с массовой переписи, а с M0:

1. читает `source/handoff/docara-unified-architecture/START.md`;
2. проверяет exact revision и clean worktree;
3. строит mapping текущий код -> целевой модуль -> тест -> deletion gate;
4. снимает baseline badge page;
5. предлагает bounded implementation batch M1/M2;
6. только после проверки mapping меняет runtime.

## Запрещённые сокращения

- не генерировать Markdown-страницы из language pack;
- не передавать готовый page HTML в основной renderer;
- не создавать второй parser/renderer/build path ради компонента;
- не считать generated catalog источником редакторского текста;
- не переносить в Docara Laravel или базу данных;
- не переписывать generated `ui`/`ui-smart` вручную;
- не удалять baseline до parity evidence.
