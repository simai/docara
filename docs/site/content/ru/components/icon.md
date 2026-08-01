# Значок

Значок помогает быстро узнать действие или состояние. Если он несёт смысл без
соседнего текста, добавьте понятную accessibility-подпись через `label`.

## Общий пример

:::example {label="Общий пример"}
```markdown
:icon[settings]{size=1 family=rounded weight=regular label="Настройки"}
```
:::

## Начертание

`family` выбирает семейство `outlined`, `rounded` или `sharp`, `weight` —
толщину `light`, `regular` или `medium`, а `filled=true` включает заливку.

:::example {label="Начертания"}
```markdown
:icon[settings]{size=1/2 family=outlined weight=light}
:icon[settings]{size=1 family=rounded weight=regular}
:icon[settings]{size=2 family=sharp weight=medium}
:icon[check_circle]{size=3 family=rounded weight=medium filled=true label="Готово"}
```
:::

## Контейнер и цвет

Контейнер `square` или `circle` делает значок заметнее. Для него доступны
варианты `main`, `tonal` и `outline`; `scheme` передаёт назначение цветом.

:::example {label="Контейнеры"}
```markdown
:icon[bolt]{size=1 container=circle variant=main scheme=success label="Быстро"}
:icon[schema]{size=1 container=circle variant=tonal scheme=secondary label="Схема"}
:icon[devices]{size=1 container=square variant=outline scheme=info label="Устройства"}
```
:::

## Вызов

```markdown
:icon[search]{size=1 family=rounded weight=medium label="Поиск"}
```

Имя значка записывается строчными латинскими буквами, цифрами и `_`. Не
передавайте смысл только цветом; декоративному значку `label` не нужен.
