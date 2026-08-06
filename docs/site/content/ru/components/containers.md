# Контейнеры и композиция

Контентный контейнер не равен page Layout. Layout распределяет shell regions; внутри document контейнер принимает только объявленные child kinds/slots и проходит тот же typed IR.

## Контейнерные записи Atlas

:::atlas_index {kind=block origin=docara authoring=container support=supported}
:::

Atlas является источником правил `allowed_children`, `slots`, `min_children`,
`max_children`, `order`, `max_depth` и `depth_semantics`. Единственная семантика
глубины — `relative_subtree_root_level_1`: каждый container считает себя
уровнем 1 и проверяет собственное subtree. Поэтому Surface → Grid → Card имеет
глубину 3 для Surface и 2 для Grid. Fence length не определяет тип и не
ослабляет ограничения.

Для полноширинной контентной полосы используйте [Surface](/ru/components/surface/): её внешняя и внутренняя ширина, локальный декоративный фон и token-настройки проходят тот же typed runtime. Surface принимает только children с зарегистрированной capability `content.embeddable`; вложенные Surface и landing-блоки не допускаются.

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
