# Бейдж

Бейдж добавляет короткую метку статуса, версии или категории внутрь текста и
интерфейсных блоков.

:::example {label=Пример}
```markdown
:badge[Новое]{type=tonal scheme=primary size=1}
```
:::

## Тип бейджа

Параметр `type` определяет, насколько заметно бейдж выделяется на странице.

| Значение | Назначение |
| --- | --- |
| `main` | Основной вариант с наиболее заметной заливкой |
| `tonal` | Мягкий тональный вариант · **По умолчанию** |
| `outline` | Контурный вариант без заливки |

:::example {label=Пример}
```markdown
:badge[Основной]{type=main scheme=primary size=1}
:badge[Тональный]{type=tonal scheme=primary size=1}
:badge[Контурный]{type=outline scheme=primary size=1}
```
:::

## Цветовая схема

Параметр `scheme` передаёт назначение бейджа цветом. Он не заменяет понятную
текстовую подпись.

| Значение | Назначение |
| --- | --- |
| `primary` | Основная |
| `secondary` | Вторичная |
| `tertiary` | Третичная |
| `neutral` | Нейтральная |
| `info` | Информация |
| `success` | Успех |
| `warning` | Предупреждение |
| `danger` | Ошибка или опасность |
| `on-surface` | Контрастная метка на поверхности |

:::example {label=Пример}
```markdown
:badge[Основная]{scheme=primary size=1}
:badge[Вторичная]{scheme=secondary size=1}
:badge[Третичная]{scheme=tertiary size=1}
:badge[Нейтральная]{scheme=neutral size=1}
:badge[Информация]{scheme=info size=1}
:badge[Успех]{scheme=success size=1}
:badge[Предупреждение]{scheme=warning size=1}
:badge[Ошибка]{scheme=danger size=1}
:badge[На поверхности]{type=main scheme=on-surface size=1}
```
:::

## Размер

Параметр `size` выбирает размер из общей шкалы SIMAI Framework.

| Значение | Назначение |
| --- | --- |
| `1/3` | Маленький |
| `1/2` | Средний |
| `1` | Обычный · **По умолчанию** |

:::example {label=Пример}
```markdown
:badge[Маленький]{size=1/3}
:badge[Средний]{size=1/2}
:badge[Обычный]{size=1}
```
:::
