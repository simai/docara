# Smart-компоненты Docara

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

Project template считается trusted developer source. Его путь выводится только
из фиксированного provider root и manifest/view записи. CSS/JavaScript также
объявляются в manifest, проверяются по физическим файлам и публикуются через
общий asset plan.

Интерфейсные подписи сайта приходят из `content/<locale>/lang.json`. Текст
страниц остаётся в Markdown; manifest и project config не являются источником
публичной документационной прозы.

## Проверка

```bash
php vendor/bin/phpunit tests/Unit/ProjectLocalSmartRuntimeTest.php
php vendor/bin/phpunit tests/Unit/SmartProviderRegistryTest.php
cd docs/site
php ../../docara build production
php ../../docara verify-static build_production
```

Full и `--page` используют один PageBuilder. После изменения структуры routes
нужна full build; для содержимого существующей страницы доступен single-page
rebuild.
