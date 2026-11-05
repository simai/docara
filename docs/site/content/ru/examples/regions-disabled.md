---
description: "Как убрать с длинной текстовой страницы меню и оглавление, чтобы текст занял всю ширину."
---

# Страница без боковых колонок

Для длинного документа — условий, политики, договора — боковые колонки только
мешают. Страница выключает меню и оглавление, и текст занимает всю ширину.

::::example {src="/ru/demonstrator-results/regions-disabled/" label="Результат"}
### page.json

:::code {src="../demonstrator-results/regions-disabled.page.json" lang=json highlight="6-9"}
:::

### Markdown страницы

:::code {src="../demonstrator-results/regions-disabled.md" lang=markdown}
:::
::::

## Как это устроено

1. `"enabled": false` выключает область. Docara не оставляет на её месте
   пустого места.
2. `container.max` задаёт ширину колонки текста по шкале SIMAI Framework от
   1 до 8. Здесь 7 — широкая колонка.
3. Шапка остаётся: в ней название, поиск и настройки.

## Что можно поменять

| Параметр | Что меняет |
|---|---|
| `sidebar.enabled` | Меню слева |
| `outline.enabled` | Оглавление справа |
| `container.max` | Ширина колонки текста: от 1 — узкая до 8 — во весь экран |

## См. также

- [Шапка, меню и оглавление](../regions-composed/)
- [Общие настройки раздела и исключение для страницы](../inheritance/)
- [Области макета](/ru/settings/layout-and-regions/)
