---
description: "Как устроены Smart-компоненты: кому принадлежат ui.*, docara.* и компоненты проекта, как их объявить, проверить и подключить."
---

# Smart-компоненты

Smart-компонент — переносимый UI-артефакт с проверяемым manifest,
представлениями, шаблоном и assets. Все компоненты проходят один путь:

```text
Markdown -> typed Document IR -> renderer registry
-> SmartComponentGateway -> LayoutComposer -> PageBuilder
```

Docara не выбирает renderer по имени компонента. Provider registry определяет
владельца namespace, затем Gateway проверяет manifest, view, preset, props,
template и assets. Неизвестное или небезопасное значение останавливает сборку.

## Встроенные владельцы

| Namespace | Provider | Назначение |
| --- | --- | --- |
| `ui.*` | source-pinned `framework.lock` | Smart-компоненты SIMAI Framework |
| `docara.*` | `docara.package` | shell и навигация Docara |
| namespace проекта | `project.<namespace>` | компоненты конкретного сайта |

`ui.alert`, `ui.button`, `docara.brand`, `docara.navigation`, `docara.toc` и
`docara.preferences` разрешаются тем же Gateway. Deprecated aliases
`docara.header` и `docara.outline` хранятся в artifact metadata, а не в PHP
ветвлении.

## Компонент проекта

Объявите один namespace в `docara.json`:

```json
{"smart":{"namespace":"project"}}
```

Корень фиксирован: `smart/`. Config и Markdown не могут выбрать PHP-класс,
callback или путь к template.

```text
smart/project.notice/
├── manifest.json
├── view/default.json
├── template/default.php
└── assets/notice.css
```

Manifest использует tracked SIMAI Framework Smart artifact v1. Минимальный пример уже есть
после `docara init`. В Markdown он вызывается так:

```markdown
:::project.notice
{"title":"Компонент проекта","text":"Собран общим конвейером."}
:::
```

Добавление нового `project.*` артефакта не требует правок `src/`. Duplicate ID,
чужой namespace, symlink, traversal, неизвестный view/preset/prop, template или
asset завершают сборку ошибкой.

После `docara init` страница `/ru/project-demos/` показывает два полезных
локальных примера: `project.install-builder` только формирует и копирует
Composer-команду, а `project.product-configurator` считает демонстрационный
итог в браузере. Их совместимые authoring aliases —
`project.install_builder` и `project.product_configurator`; канонические ABI IDs
используют дефисы. Оба примера обходятся без fetch, backend, заказа, оплаты или
выполнения команды.

`project.install-builder` использует принятые `ui.input` и `ui.checkbox`.
`project.product-configurator` показывает заполненный `ui.dropdown`; варианты
в его slot `options` — только принятые дочерние `ui.list-item` с `type=text`.
Все четыре Framework-компонента проходят тот же registry, Gateway и renderer,
что и остальные Smart-компоненты. Их manifest, view, preset, template, assets
и hydration contract проверяются по exact lock до render.

Поддержка `ui.list-item` здесь намеренно узкая: это текстовый option для
`ui.dropdown`. Icons, avatars, tags и самостоятельное использование
`ui.list-item` не входят в контракт. Нельзя заменять options массивом `items`,
raw-разметкой или локальной копией Framework-компонента.

View выбирается одинаково для любого provider: явно указанный view имеет
приоритет; иначе named preset выбирает зарегистрированный view; если не задано
ни то ни другое, используется `default`. Это данные артефакта, поэтому новый
компонент или вариант не требует условия по component ID в engine.

## Контекст шаблона и assets

Docara передаёт portable PHP-шаблону целевой array ABI SIMAI Framework 5 Smart v1 и не
вводит собственный template dialect.

| Переменная | Форма |
| --- | --- |
| `$id` | строковый ID узла |
| `$smart` | полное имя компонента |
| `$manifest` | проверенный массив manifest |
| `$view` | выбранный массив view |
| `$preset` | выбранный массив preset либо пустой массив |
| `$props` | проверенный массив author props |
| `$childrenHtml` | готовая строка дочернего HTML |
| `$slot` | строка slot либо пустая строка |

Например, безопасный шаблон читает `<?= htmlspecialchars((string)
($props['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>`. Объект
`$view` и `SmartTemplateContext` относятся только к ограниченному legacy
host-adapter старых package-owned шаблонов и не являются portable ABI.

Переносимый артефакт использует нейтральный Framework-owned контракт
`sf.smart_artifact_abi` версии `1.0.0` и compatibility id
`sf-smart-artifact-abi-v1`. Историческое имя `sf5.smart.artifact.v1`
обозначает только совместимый layout файлов, а не второй формат. Exact pin
`b3cdff87563ff78e7eddf044048a4b298fc69036` передаёт выбранные view, preset,
slot и hydration одинаково под SIMAI Framework 5 и Docara. Не создавайте отдельный Docara
template dialect.

Команда `docara schema smart --json` возвращает authoritative
byte-exact Framework-owned `smart.manifest.schema.json`; неизменённый manifest из
scaffold проходит её без преобразования. Дополнительные требования Docara
проверяет отдельная policy `docara.smart_admission.v1`, а не альтернативная
публичная схема. Manifest из
`docara scaffold smart ... --dry-run` проходит именно эту схему. Команда
`inspect smart ... --json` одинаково сообщает neutral contract identity для
`ui.*`, `docara.*` и project namespace. Host-bound преобразование старых
package manifests видно отдельно в `provider_adapter`, а форма template — в
`template_abi`. Ни одно из этих полей не создаёт новый публичный dialect.

Project template считается trusted developer source. Его путь выводится только
из фиксированного provider root и manifest/view записи. CSS/JavaScript также
объявляются в manifest, проверяются по физическим файлам и публикуются через
общий asset plan.

Интерфейсные подписи сайта приходят из `content/<locale>/lang.json`. Текст
страниц остаётся в Markdown; manifest и project config не являются источником
публичной документационной прозы.

## Проверка

```bash
docara doctor
docara inspect smart project.notice --json
docara validate smart project.notice
php vendor/bin/phpunit tests/Unit/ProjectLocalSmartRuntimeTest.php
php vendor/bin/phpunit tests/Unit/SmartProviderRegistryTest.php
cd docs/site
php ../../docara build production
php ../../docara verify-static build_production
```

Для нового артефакта используйте hash-bound workflow `docara scaffold smart
project.name --dry-run`, затем `docara scaffold --apply=<plan_id>`. Полный путь
описан в [Developer и AI SDK](/development/developer-sdk/).

Full и `--page` используют один PageBuilder. После изменения структуры routes
нужна full build; для содержимого существующей страницы доступен single-page
rebuild.

## Компонент SIMAI Framework

Новый `:::ui.*` вызов нельзя добавить только изменением parser, schema или
метаданных каталога. Единственный admission authority — точный
`simai-framework.lock.json`.

Сначала пройдите общий выбор уровня в
[обзоре раздела](/ru/development/#как-развивать-возможности). Этот раздел
описывает только случай, когда native Markdown и typed-компонент Docara уже не
решают задачу.

Минимальный принимаемый набор:

1. подтвердить, что задачу нельзя решить native Markdown или существующим
   typed-компонентом Docara;
2. для нового переносимого компонента добавить manifest по публичному
   `sf.smart_artifact_abi` / `smart.manifest.schema.json`, props и host renderer
   и immutable upstream revision;
3. добавить manifest в точный SIMAI Framework lock с provider revision и
   SHA-256;
4. описать полный immutable asset dependency plan, включая транзитивные
   зависимости;
5. добавить узкие consumer metadata в
   `resources/component-catalog/smart/`: они описывают использование в Docara,
   но не могут расширять manifest;
6. добавить positive и negative props, constraint, dependency и lock tests;
7. проверить в браузере поведение, клавиатуру, темы, адаптивность и
   доступность;
8. добавить один физический Markdown-owner публичной страницы и отдельный
   exact fixture; manifest и `lang.json` не владеют prose страницы.

Каждый critical asset из manifest должен либо присутствовать в вычисленном
asset plan, либо быть назван в сужающей consumer-policy с конкретной причиной.
Static verifier сверяет каждый материализованный Framework asset с точной
проекцией lock: путь, безопасный тип файла, SHA-256 и полный набор файлов.
До очистки каталога назначения build preflight проверяет bytes каждого
зафиксированного файла и равенство проекции полному dependency closure всех
допущенных компонентов.

Core runtime и utilities публикуются отдельной hash-bound проекцией с
сохранением путей относительно upstream `distr`. Это позволяет webpack и Core
лениво находить chunks без сетевого fallback. Наличие файла в этой локальной
проекции не является admission нового Smart-компонента: authoring support
по-прежнему требует отдельный принятый manifest и consumer policy.
Текущий portable-контур явно исключает backend event bridge коммерческих пакетов SIMAI: Docara не
допускает backend handlers, data binding или выполнение эффектов. Это
ограничение отражается в машинном каталоге, а не скрывается.

Поля `authoring.parameters` в эффективном каталоге описывают ввод автора, а
не уже нормализованные host props. `required: false` означает только, что
автор может не указывать параметр. Поле `default` присутствует, когда точное
базовое значение есть в `atlas.example_props`; без него свойство остаётся
отсутствующим до явного ввода или применения preset. `preset` остаётся
отдельным необязательным параметром. `validation` переносит строковые и
числовые правила свойства, а `mirrors` явно показывает детерминированное
заполнение связанного свойства (например, текста доступного имени). Поле
`authoring.constraints` без потерь переносит
`allowed_combinations` и `requires` из manifest, чтобы разработчик и ИИ не
генерировали формально допустимые, но несовместимые сочетания.

Сборка требует точного равенства между Smart metadata и manifest keys,
допущенными lock. Поэтому лишний metadata-файл, отсутствующий manifest,
несовпадающий hash или moving revision завершает сборку ошибкой. Схема
`docara.component_call.v1` проверяет только форму `ui.*` вызова и не является
реестром допуска.

Consumer metadata не может повысить readiness или расширить название,
категорию и состояния manifest. Поддерживаемый компонент требует
`safe_to_suggest` и `safe_to_render`. Его состояния точно равны состояниям
manifest в исходном порядке за вычетом
`consumer_policy.excluded_states`; каждое исключение связано с конкретной
запрещённой парой property/value, которая сама проверяется по точной схеме
свойства. Описание берётся из policy-owned ограниченного контракта поведения
Docara, а не копируется из более широкого upstream-описания.

Для простых семантических конструкций сначала проверьте, достаточно ли
обычного Markdown или typed-рецепта Docara. Точные поддерживаемые вызовы,
параметры и примеры находятся в [справочнике компонентов](/components/).

### Как зафиксировать ещё не готовую потребность

Если renderer или admission-набор ещё не готов, добавьте неисполняемую запись
в `resources/component-catalog/requirements/` с lifecycle
`admission_pending`, `framework_gap` или `deferred`. Запись должна содержать
owner, reason, fallback и admission condition. Она делает дефицит видимым, но
не разрешает директиву.

Requirement-запись никогда не переключается в `supported` на месте. После
готовности она удаляется, а исполняемая возможность появляется из своего
authoritative source: native profile, typed definition либо Smart metadata с
точным lock admission. Для исполняемой записи обязательны renderer, тесты,
документация и example fixture. Каталог
`_docara/component-catalog.json` в output сборки не является каноническим
реестром SIMAI Framework и не подтверждает production или public-release
readiness.
Устаревшая схема smart manifest v1 встречается только во внутренних package-owned
артефактах старого storage adapter. Это не публичная authoring-схема и не
альтернативный Portable Smart ABI.
