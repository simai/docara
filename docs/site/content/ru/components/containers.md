# Контейнеры и композиция

Контентный контейнер не равен page Layout. Layout распределяет shell regions; внутри document контейнер принимает только объявленные child kinds/slots и проходит тот же typed IR.

## Контейнерные записи Atlas

:::atlas_index {kind=block origin=docara authoring=container support=supported}
:::

Atlas является источником правил `allowed_children`, `slots`, `min_children`, `max_children`, `order` и `max_depth`. Fence length не определяет тип и не ослабляет ограничения.

## Валидное вложение

:::example {label="Grid with cards"}
````markdown
::::grid {columns=2 gap=2}
:::card
### Первый шаг

Проверьте конфигурацию.
:::
:::card
### Второй шаг

Соберите выбранную страницу.
:::
::::
````
:::

## Fail-closed примеры

| Нарушение | Пример | Диагностика |
| --- | --- | --- |
| Неподдерживаемый child | `grid` содержит обычный текст вместо `card` | `MARKDOWN_GRID_CARD_REQUIRED` |
| Неверное количество | `columns` содержит одну область | `MARKDOWN_COLUMNS_REGION_COUNT_INVALID` |
| Неверный порядок/тип | `steps` содержит маркированный список | `MARKDOWN_STEPS_ORDERED_LIST_REQUIRED` |
| Превышение contract | child/slot/count/depth не соответствует Atlas | registration/admission error до render |
| Незакрытый fence | отсутствует завершающий `:::` | `MARKDOWN_BLOCK_UNCLOSED` |

Для Framework container применяются его manifest slots: например, принятый `ui.dropdown` допускает в `options` только exact-pinned `ui.list-item`. Raw `items` и непроверенный HTML не допускаются.
