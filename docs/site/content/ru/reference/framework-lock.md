---
description: Справочник simai-framework.lock.json: точные ревизии Core и Smart, manifests, политика потребителя, хеши и проекции ассетов, которые фиксирует lock.
---

# Файл закрепления Framework

`simai-framework.lock.json` (Framework lock) связывает точные Core/Smart revisions, registry
identity, manifests, consumer policy, hashes и asset projections. Помимо
Smart-артефактов и типографики, lock фиксирует локальный runtime-пакет Core:
bootstrap JavaScript, необходимые webpack/language chunks, Material Symbols,
admitted component assets и используемые Docara utilities.

## Почему lock отдельный

Presentation JSON выбирает внешний вид страницы, но не может допустить новый
Smart-компонент. Runtime surface определяется отдельным immutable input,
который можно проверить и сравнить между сборками.

`main`, `master`, `latest` и другие moving references запрещены. Локальные
projected bytes сверяются по SHA-256 до публикации. Manifest runtime-пакета
фиксирует каждый distribution-relative путь и общий path-sorted ledger; путь
сохраняется относительно `distr`, потому что Core разрешает ленивые chunks и
utilities от `window.sfPath`.

## Что проверить

1. Lock имеет schema `docara.framework_lock.v1`.
2. Каждая revision immutable.
3. Bundled manifest и provider revision совпадают с lock.
4. Asset plan содержит только разрешённые зависимости и omission contracts.
5. Build и `verify-static` проходят на одном exact input.

Сборка Docara не требует jsDelivr, Google Fonts или другого внешнего asset
host: Inter, Material Symbols, Core, utilities и допущенные Smart assets
публикуются в `_docara/vendor` и `_docara/framework`. Ссылки на внешние сайты в
самом содержимом остаются обычными ссылками и не являются runtime-зависимостью.

Наличие имени компонента или runtime-файла не означает admission. Нужны полный
authoring contract, manifest, dependencies, host renderer, accessibility,
tests и consumer policy.

Точную identity текущего сайта смотрите в самом
`simai-framework.lock.json`, в `build_<environment>.docara/resolved-page-plans.json` и
`_docara/component-catalog.json`. Не переносите commits, pair IDs и список
компонентов в ручной справочник: generated artifacts отражают конкретную
сборку без drift.

К Framework-owned surface относятся не только Smart-компоненты, но и
поведенческие компоненты Core. Например, `sf-scrollbar` поставляет режимы
`overlay`, `persistent`, `standard` и `hidden`; Docara выбирает preset через
`layout.scrollbar.preset`, но не копирует реализацию полосы прокрутки.

## Поля файла

Файл создаёт `docara init` вместе с проектом. Вручную его обычно не правят:
сборка сверяет закреплённые файлы по хешам. Таблицы ниже показывают, что
именно закреплено.

### Версия и сборка SIMAI Framework

:::schema_reference {schema=framework-lock scope=lock fields=schema,version,runtime,nonclaims}
:::

### Файлы и ресурсы

:::schema_reference {schema=framework-lock scope=lock fields=runtime_projection,asset_projection,dynamic_asset_projection,package_projections}
:::

### Значки и шрифты

:::schema_reference {schema=framework-lock scope=lock fields=icon_projection,typography_projection}
:::

### Описания компонентов

:::schema_reference {schema=framework-lock scope=lock fields=manifests}
:::

## Проверка Smart-компонентов

Effective support смотрите через `docara atlas --json` и `docara inspect smart <id> --json`. Neutral identity — `sf.smart_artifact_abi / 1.0.0 / sf-smart-artifact-abi-v1`; storage compatibility alias/provider adapter/template ABI показываются отдельно.

Принятый form wave: `ui.input`, `ui.dropdown`, `ui.checkbox`; populated dropdown зависит от exact-pinned text-only `ui.list-item`. Related icons/avatars/tags остаются nonclaims.

Каждый Smart-компонент перед публикацией проходит проверку схемы, владельца
поставки, пространства имён, шаблона, ассетов, гидратации и хешей.

## Запасное хранилище браузера

Если зафиксированный Core использует browser storage, интеграционный слой может
предоставить ограниченный in-memory fallback только для текущей страницы. Такой
fallback не является persistent storage и не создаёт отдельную реализацию
SIMAI Framework.

## Граница

Framework lock доказывает ограниченный consumer contract конкретной сборки. Он
не допускает произвольный raw Framework-компонент только потому, что его файл
есть в локальном пакете, и не доказывает готовность всей экосистемы,
production, public release или право распространить другие upstream bytes.
