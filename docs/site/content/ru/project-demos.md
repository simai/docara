# Полезные компоненты проекта

Эти сценарии лежат в `smart/` самого сайта и подключены без изменения engine. Их owner/support/provider видны в Atlas; Framework controls остаются exact-pinned owner artifacts.

## Команда установки

Выберите ОС, Composer method, package/version и allowlisted options. Компонент только формирует и копирует экранированную команду — ничего не скачивает и не запускает.

:::project.install-builder
{"title":"Соберите команду установки","package":"simai/docara","version":"^2.0"}
:::

## Конфигуратор продукта

Dropdown меняет базовый тариф, checkboxes — локальные опции и summary. Демо не отправляет данные, не создаёт заказ и не выполняет payment/backend action.

:::project.product-configurator
{"title":"Настройте пример продукта","base_price":2500,"team_price":4500,"business_price":8000,"currency":"₽"}
:::

## Effective project entries

:::atlas_index {support=project}
:::
