# Кнопка-ссылка

Кнопка выделяет важный переход, но остаётся обычной ссылкой: она открывает
страницу или файл и работает с клавиатуры без дополнительного JavaScript.

## Общий пример

:::example {label="Общий пример"}
```markdown
:button[Посмотреть компоненты]{href=../ type=outline scheme=on-surface size=1 icon=arrow_forward}
```
:::

## Вид

Параметр `type` задаёт визуальный акцент действия.

| Значение | Назначение |
| --- | --- |
| `default` | Основное действие с заливкой · **По умолчанию** |
| `tonal` | Более спокойное действие |
| `outline` | Контурная кнопка |
| `link` | Действие, похожее на текстовую ссылку |

:::example {label="Варианты"}
```markdown
:button[Начать]{href=../../ type=default scheme=primary}
:button[Документация]{href=../../ type=tonal scheme=secondary}
:button[Компоненты]{href=../ type=outline scheme=on-surface}
:button[Подробнее]{href=../../ type=link scheme=primary}
```
:::

## Размер и значок

`size` принимает `1/2`, `1` или `2`. Параметр `icon` добавляет справа значок
Material Symbols; указывайте его системное имя без пробелов.

:::example {label="Размеры"}
```markdown
:button[Компактная]{href=../../ size=1/2}
:button[Обычная]{href=../../ size=1 icon=arrow_forward}
:button[Крупная]{href=../../ size=2}
```
:::

## Вызов

```markdown
:button[Понятное действие]{href=/target/ type=default scheme=primary size=1 icon=arrow_forward}
```

`href` обязателен и должен содержать безопасный URL. Для команды без перехода
используйте интерактивный компонент приложения, а не кнопку-ссылку в статье.
