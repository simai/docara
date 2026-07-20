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

После построения навигационной топологии builder передаёт компилятору
`PageCompositionContext`: нормализованные branding, активное дерево навигации
и outline текущего документа. Контекст неизменяем, не содержит разметку и
отклоняет небезопасные URL, некорректные состояния и меню глубже четырёх
уровней.

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

Layout владеет областями и безопасным View Tree, но не внутренним устройством
section. `docara.article` собирает блоки документа в именованный слот, а блок
`content.smart` ссылается на уже разрешённый Smart-план. Общий
`section.docara.shell` является безопасным presentation view, а
зарегистрированные определения
`docara.header`, `docara.navigation` и `docara.outline` размещают продуктовые
Smart-компоненты в своих областях; footer пока остаётся явно пустой
необязательной областью.

Состав оболочки больше не выбирается жёсткими ветками компилятора.
`layout.regions` после наследования site -> section -> page задаёт:

- включена ли область;
- какие зарегистрированные Section calls с устойчивыми ID она содержит.

Слоты, Block calls, Smart и фиксированный binding принадлежат переиспользуемому
Section definition. Они не копируются в пользовательские настройки.

Resolver дополняет структурные defaults, проверяет обязательный `main` и
fail closed отклоняет Smart не в своей области или с неправильным binding.
Renderer не выводит HTML-контейнер отключённой области.

## Где находятся контракты

| Слой | Источник |
| --- | --- |
| Layout | `resources/layouts/docara.docs.json` |
| Sections | `resources/sections/docara.article.json`, `docara.header.json`, `docara.navigation.json`, `docara.outline.json` |
| Blocks | `resources/blocks/*.json` |
| Safe View Trees | `resources/views/*.json` |
| Framework utility projection | `resources/framework/view-utilities.json` |
| Smart manifests и views | `resources/smart/*/manifest.json`, `resources/smart/*/views/default.json` |
| JSON schemas | `resources/schemas/declarative-*.schema.json` |
| Typed AST | `src/Declarative/Document` |
| Immutable plan | `src/Declarative/Plan` |
| Compiler | `src/Declarative/DeclarativePageCompiler.php` |
| Safe View Tree и registered Blade rendering | `src/Declarative/Rendering` |
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
5. template registry допускает только известный локальный renderer ID;
6. template получает готовый immutable view model;
7. renderer возвращает HTML, assets и provenance.

Автор не может передать путь к PHP/Blade-файлу, исходник Blade, HTML, callback
или класс. Template registry
использует фиксированный allowlist и дополнительно отклоняет symlink,
необычный hard link, путь вне `resources` и нетипизированный context.

Шаблон отвечает только за представление. Defaults, проверка props, вычисление
ID, выбор assets и подготовка данных выполняются до шаблона. CSS и JavaScript
приходят через manifest asset graph.

## Составные Smart-компоненты оболочки

Шапка, меню и оглавление принадлежат продукту Docara:

- `docara.header`;
- `docara.navigation`;
- `docara.outline`.

Они используют тот же manifest schema
`larena.ui.smart_manifest.v1`, что и Larena, и имеют `kind: composite`.
Renderer `docara.smart.template` честно указывает на backend composition
Docara. Эти компоненты не объявляют себя нативными web components Simai
Framework: `frontend.runtime` и `frontend.tag` равны `null`.

При этом presentation-only templates строятся из известных утилит Framework,
а нужные Framework assets указаны в manifest. Так общий контракт, продуктовый
владелец и frontend implementation layer не смешиваются.

`docara.navigation` получает каноническое дерево после `visible()` и
`activate()`. В плане сохраняются и проверяются:

- вложенность до четырёх уровней;
- активная страница;
- активный предок;
- текущий раздел;
- раскрытый путь.

View model превращает дерево в безопасную плоскую проекцию с явным
`data-docara-navigation-depth`. Это позволяет шаблону оставаться
presentation-only и не выполнять рекурсивную подготовку данных.

## Shadow-интеграция

`PortableSiteBuilder` пока сохраняет принятый путь публикации. Для страницы,
которая использует только поддержанный в срезе `ui.alert`, он дополнительно:

1. после разрешения branding, navigation и outline создаёт typed composition
   context;
2. строит новый plan со всеми областями;
3. рендерит новый artifact;
4. сравнивает title, текст, regions, headings, links и семантику alert со старым
   результатом;
5. отдельно доказывает structural parity branding, дерева навигации,
   active-state и outline;
6. преобразует тот же plan через Larena contract adapter;
7. записывает hashes, provenance и verdict в
   `.docara/resolved-page-plans.json`.

Если страница содержит Smart-компонент вне текущего среза, diagnostics получает
статус `not_in_vertical_slice`; публикация не меняется и readiness не
расширяется молча.

## Граница с Larena

`ResolvedRenderPlan` v2 полностью раскрывает Layout/Region/Section/Slot/Block/
Smart, safe View Trees, assets, provenance и diagnostics. Это генерируемый
артефакт, а не второй редактируемый источник.

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
php vendor/bin/phpunit tests/Unit/DeclarativeShellCompositionTest.php
php vendor/bin/phpunit tests/Unit/LarenaContractAdapterTest.php
php vendor/bin/phpunit tests/Unit/DeclarativeArchitectureBoundaryTest.php
php vendor/bin/phpunit tests/PortableSiteBuilderTest.php
```

Старый `PortableHtmlRenderer` нельзя удалять или переписывать до отдельной
приёмки полного нового конвейера. Текущий тест фиксирует его принятый SHA-256
байт-в-байт.
