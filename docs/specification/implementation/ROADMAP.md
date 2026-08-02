# Дорожная карта упрощения Docara

Статус: R1-C independently accepted с verdict `PASS_WITH_NOTES`. Artifact
`83afd355…` остаётся `superseded_after_audit`; единственный принятый local
release candidate — source `56a2abf8…`, ZIP `04c18c95…`. R2 готовит
production dossier; tag, release и production не заявлены.

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
PageSourceLocator -> PortableConfigurationLoader -> MarkdownCompiler -> Document IR
-> DocumentRendererRegistry -> SmartComponentGateway -> layout composition
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

Статус: implementation runtime и bounded product-candidate acceptance
завершены. R1 audit позже переоткрыл semantic docs/artifact acceptance; release
approval отсутствует.

Результат:

- один `build` для сайта и тот же `PageBuilder` для `--page`;
- чистый `init` создаёт понятный переносимый проект;
- update отделяет engine-owned файлы от project-owned content/config/assets;
- runtime-команды и ownership lifecycle доказаны; semantic public docs требуют
  R1-C correction;
- diagnostics ссылаются на source location;
- immutable M5 evidence сохраняется, но не подменяет R1-C release evidence.

После M5 выполняется R1: детерминированная упаковка, versioned update/rollback,
fresh-consumer и release verification. Даже зелёный R1 лишь готовит отдельное
release review; merge, tag, публикация и deploy не выполняются автоматически.

## R1. Подготовить локальный release candidate

Статус: `correction_pending` после independent audit. Прежние reproducibility,
consumer и update evidence остаются историческими, но local release readiness
отозвана из-за obsolete public language-pack contract и broken packaged links.

Доказано: два clean clone создают byte-identical ZIP/manifest/checksums; два
fresh dist consumer проходят init/build/static; текущий публичный сайт даёт
103/103 full/single parity; реальный predecessor/current update атомарно
применяется и точно откатывается с сохранением project-owned файлов; artifact
policy и security tests проходят. Эти положительные результаты сохраняются как
история, но следующий шаг — R1-C correction и новый independent artifact retest.

## R1-C. Устранить semantic drift перед новым candidate

Статус: independently accepted `PASS_WITH_NOTES`. Public `language_pack`
удалён, public authoring docs и negative gates переписаны, front
matter/missing-page contracts работают, ссылки внутри ZIP проверяются, новый
immutable artifact независимо воспроизведён. Evidence source:
`source/workflow/2026-08-02-docara-r1c-semantic-correction-goal.md`.

## R2. Подготовить production-readiness dossier

Статус: выполняется без live cutover. R2 повторяет exact package в disposable
production-like consumer, закрывает совместимость/security, классифицирует
current/candidate delta и проверяет same-filesystem atomic rename/rollback на
зеркале. Будущий planned tag однозначно относится к source `56a2abf8…`, потому
что именно этот SHA записан в принятом artifact manifest; последующие
governance commits являются dossier, а не подменой release source.

## Порядок продолжения

Текущий recovery source —
`source/workflow/2026-08-02-docara-r2-production-readiness.md`. M0–M5 и прежний
R1 не переигрываются; старый R1 ZIP остаётся immutable negative baseline.
После R2 единственным следующим шагом может быть отдельное user-approved
развёртывание exact artifact на `docara.test`.

## Запрещённые сокращения

- не генерировать Markdown-страницы из language pack;
- не передавать готовый page HTML в основной renderer;
- не создавать второй parser/renderer/build path ради компонента;
- не считать generated catalog источником редакторского текста;
- не переносить в Docara Laravel или базу данных;
- не переписывать generated `ui`/`ui-smart` вручную;
- не удалять baseline до parity evidence.
