# Декларативный rendering pipeline

Docara развивает общий с Larena принцип сборки интерфейса, но остаётся
статическим генератором документации. Исходный Markdown, настройки композиции,
данные, шаблоны и публикация разделены на независимые слои.

Первый вертикальный срез работает в shadow-режиме: принятый renderer продолжает
публиковать HTML, а новый конвейер параллельно строит план, рендерит его и
проверяет семантическое равенство. Это не заявление о готовности всех
компонентов или production-миграции.

## Поток данных

```text
Markdown
  -> DocumentParser
  -> DocumentAst
  -> DeclarativePageCompiler
  -> ResolvedRenderPlan
  -> DeclarativePageRenderer
  -> RenderArtifact
```

`DocumentAst` — типизированное дерево контента. Оно хранит Markdown-узлы,
Smart-вызовы, заголовки, ссылки и координаты источника, но не содержит HTML.

`ResolvedRenderPlan` — неизменяемый результат разрешения композиции:

```text
Page -> Region -> Section -> Block -> Smart
```

Профиль `docara.docs` объявляет пять областей:

- `header`;
- `sidebar`;
- `main`;
- `outline`;
- `footer`.

Layout владеет областями и shell template, но не внутренним устройством
section. `docara.article` собирает блоки документа, а блок
`content.smart` ссылается на уже разрешённый Smart-план.

## Где находятся контракты

| Слой | Источник |
| --- | --- |
| Layout | `resources/layouts/docara.docs.json` |
| Section | `resources/sections/docara.article.json` |
| Blocks | `resources/blocks/*.json` |
| Smart view | `resources/smart/ui.alert/views/default.json` |
| JSON schemas | `resources/schemas/declarative-*.schema.json` |
| Typed AST | `src/Declarative/Document` |
| Immutable plan | `src/Declarative/Plan` |
| Compiler | `src/Declarative/DeclarativePageCompiler.php` |
| Trusted rendering | `src/Declarative/Rendering` |
| Larena adapter | `src/Declarative/Adapter` |

## Smart-компонент `ui.alert`

Вызов из Markdown:

```markdown
:::ui.alert
{"type":"info","title":"Перед началом","supporting-text":"Создайте резервную копию."}
:::
```

Разрешение выполняется последовательно:

1. exact manifest берётся из зафиксированной пары Simai Framework;
2. view `default` выбирает только зарегистрированный template ID;
3. consumer policy сужает manifest и добавляет управляемые значения;
4. props проверяются по manifest;
5. template registry допускает только известный локальный файл;
6. template получает готовый immutable view model;
7. renderer возвращает HTML, assets и provenance.

Автор не может передать путь к PHP-файлу, callback или класс. Template registry
использует фиксированный allowlist и дополнительно отклоняет symlink,
необычный hard link, путь вне `resources` и нетипизированный context.

Шаблон отвечает только за представление. Defaults, проверка props, вычисление
ID, выбор assets и подготовка данных выполняются до шаблона. CSS и JavaScript
приходят через manifest asset graph.

## Shadow-интеграция

`PortableSiteBuilder` пока сохраняет принятый путь публикации. Для страницы,
которая использует только поддержанный в срезе `ui.alert`, он дополнительно:

1. строит новый plan;
2. рендерит новый artifact;
3. сравнивает title, текст, regions, headings, links и семантику alert со старым
   результатом;
4. преобразует тот же plan через Larena contract adapter;
5. записывает hashes, provenance и verdict в
   `.docara/resolved-page-plans.json`.

Если страница содержит Smart-компонент вне текущего среза, diagnostics получает
статус `not_in_vertical_slice`; публикация не меняется и readiness не
расширяется молча.

## Граница с Larena

`LarenaContractAdapter` не переносит в Docara хранилище, маршрутизацию, права,
редактор или runtime Larena. Он доказывает, что один `ResolvedRenderPlan` можно
без семантической потери представить контрактом
`larena.layout.resolved_render_plan.v1`.

Общими должны оставаться понятия layout, region, section, block, Smart, assets
и provenance. Platform adapter Larena позже связывает этот контракт со своими
моделями, политиками доступа и динамическим runtime.

## Как добавлять следующий Smart-компонент

Нельзя начинать с разметки в builder. Для нового Smart нужны:

1. exact manifest в зафиксированном Framework registry;
2. явное consumer-policy решение без расширения manifest;
3. Smart view descriptor и schema;
4. стабильный template ID в trusted registry;
5. immutable view model;
6. presentation-only template;
7. asset/provenance resolution;
8. положительные, отрицательные и semantic-parity fixtures;
9. Larena adapter fixture.

До прохождения этих проверок компонент остаётся вне декларативного среза.

## Проверка

Из корня репозитория:

```bash
php vendor/bin/phpunit tests/Unit/DeclarativeDocumentParserTest.php
php vendor/bin/phpunit tests/Unit/DeclarativePageCompilerTest.php
php vendor/bin/phpunit tests/Unit/DeclarativeRenderingTest.php
php vendor/bin/phpunit tests/Unit/DeclarativeSemanticParityTest.php
php vendor/bin/phpunit tests/Unit/LarenaContractAdapterTest.php
php vendor/bin/phpunit tests/Unit/DeclarativeArchitectureBoundaryTest.php
php vendor/bin/phpunit tests/PortableSiteBuilderTest.php
```

Старый `PortableHtmlRenderer` нельзя удалять или переписывать до отдельной
приёмки полного нового конвейера. Текущий тест фиксирует его принятый SHA-256
байт-в-байт.
