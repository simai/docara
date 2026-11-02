---
description: "Как задать общие настройки для всего раздела в section.json и изменить одну из них для отдельной страницы."
---

# Общие настройки раздела и исключение для страницы

Раздел «Справочник API» читается узкой колонкой без оглавления. Одной
странице с широкой таблицей нужна колонка пошире. Страница меняет только
ширину, остальное берёт у раздела.

::::example {src="/ru/demonstrator-results/inheritance/page/" label="Результат"}
### section.json раздела

:::code {src="../demonstrator-results/inheritance/section.json" lang=json highlight="4-10"}
:::

### page.json страницы

:::code {src="../demonstrator-results/inheritance/page.page.json" lang=json highlight="5-7"}
:::

### Markdown страницы

:::code {src="../demonstrator-results/inheritance/page.md" lang=markdown}
:::
::::

## Как это устроено

1. `section.json` лежит в папке раздела. Его настройки получают все страницы
   этой папки и вложенных папок.
2. Раздел делает колонку узкой (`container.max: 4`) и выключает оглавление и
   переходы «Предыдущая / Следующая».
3. Файл `page.json` с именем страницы меняет одну настройку — ширину.
   Оглавления и переходов на странице по-прежнему нет: их страница не
   трогала.

Порядок такой: `docara.json` сайта, затем `section.json` разделов от
внешнего к внутреннему, затем `page.json` страницы. Каждый следующий меняет
только то, что в нём записано.

## Что можно поменять

| Параметр | Что меняет |
|---|---|
| `layout.container.max` | Ширина колонки текста, от 1 до 8 |
| `reading.toc` | Оглавление страницы |
| `reading.previous_next` | Переходы на соседние страницы внизу |
| `reading.breadcrumbs` | Строка «где я» над заголовком |

## См. также

- [Настройки на трёх уровнях](../composition-inheritance/)
- [Сайт, раздел и страница](/ru/settings/levels-and-inheritance/)
