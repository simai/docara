---
description: "Как настройки сайта, раздела и страницы складываются на одной странице: что берётся сверху, что меняется и как сбросить унаследованное."
---

# Настройки на трёх уровнях

Верхние уровни задают название и тему. Раздел «Платежи» добавляет блок под
меню и убирает оглавление. Одна страница возвращает оглавление со своей заметкой и
включает подвал.

::::example {src="/ru/demonstrator-results/composition-inheritance/page/" label="Результат"}
### section.json раздела

:::code {src="../demonstrator-results/composition-inheritance/section.json" lang=json highlight="8-21"}
:::

### page.json страницы

:::code {src="../demonstrator-results/composition-inheritance/page.page.json" lang=json highlight="7-9,22-23"}
:::

### Markdown страницы

:::code {src="../demonstrator-results/composition-inheritance/page.md" lang=markdown}
:::
::::

## Как это устроено

1. **Верхние уровни.** Тему задаёт `docara.json` сайта, а название «Acme Pay»
   и разделы шапки — общий `section.json` демо-раздела. Поэтому в файлах
   раздела «Платежи» и страницы их нет.
2. **Раздел.** `section.json` ставит под меню свой блок и выключает
   оглавление (`"outline": {"enabled": false}`) для всех страниц раздела.
3. **Страница.** `"$reset": true` в области `outline` отбрасывает то, что
   пришло от раздела, и страница задаёт правую колонку заново: оглавление и
   свою заметку. Подвал страница включает только для себя.

:::code {src="../demonstrator-results/composition-inheritance/page.page.json" lang=json lines="5-21" highlight="3-5" dedent=true}
:::

Без `$reset` поля страницы сложились бы с полями раздела. Со сбросом область
целиком описана в `page.json`, и не нужно помнить, что пришло сверху.

## Что можно поменять

| Параметр | Что меняет |
|---|---|
| `$reset` | Отбросить унаследованную настройку и задать её заново |
| `enabled` | Показывать область или нет |
| `sections` | Что стоит в области, сверху вниз |

## См. также

- [Общие настройки раздела и исключение для страницы](../inheritance/)
- [Подвал страницы](../region-footer/)
- [Сайт, раздел и страница](/ru/settings/levels-and-inheritance/)
