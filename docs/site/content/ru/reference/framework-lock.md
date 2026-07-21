# Framework lock

`simai-framework.lock.json` связывает точные Core/Smart revisions, registry
identity, manifests, consumer policy, hashes и asset projection.

## Почему lock отдельный

Presentation JSON выбирает внешний вид страницы, но не может допустить новый
Smart-компонент. Runtime surface определяется отдельным immutable input,
который можно проверить и сравнить между сборками.

`main`, `master`, `latest` и другие moving references запрещены. Локальные
projected bytes сверяются по SHA-256 до публикации.

## Что проверить

1. Lock имеет schema `docara.framework_lock.v1`.
2. Каждая revision immutable.
3. Bundled manifest и provider revision совпадают с lock.
4. Asset plan содержит только разрешённые зависимости и omission contracts.
5. Build и `verify-static` проходят на одном exact input.

Наличие имени компонента или runtime-файла не означает admission. Нужны полный
authoring contract, manifest, dependencies, host renderer, accessibility,
tests и consumer policy.

Точную identity текущего сайта смотрите в самом
`simai-framework.lock.json`, в `.docara/resolved-page-plans.json` и
`_docara/component-catalog.json`. Не переносите commits, pair IDs и список
компонентов в ручной справочник: generated artifacts отражают конкретную
сборку без drift.

## Browser storage fallback

Если зафиксированный Core использует browser storage, интеграционный слой может
предоставить ограниченный in-memory fallback только для текущей страницы. Такой
fallback не является persistent storage и не создаёт отдельную реализацию
Simai Framework.

## Граница

Framework lock доказывает ограниченный consumer contract конкретной сборки. Он
не доказывает готовность всей экосистемы, production, public release или право
распространить все upstream bytes.
