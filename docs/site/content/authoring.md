# Содержание и макет

В переносимом Docara файлы являются источником истины:

- Markdown хранит текст и структуру содержания;
- `docara.json` задаёт сайт;
- `redirects.json` сохраняет обязательные старые внутренние URL;
- `_section.json` задаёт наследуемые значения ветки;
- `<page>.page.json` уточняет одну страницу;
- generator создаёт HTML, indexes и диагностические artifacts.

:::card
### Простое правило

Начинайте с обычного Markdown. Добавляйте JSON только для представления и
навигации. Выбирайте компонент только тогда, когда Markdown недостаточно, и
берите точный вызов из generated catalog.

:::

## Путь автора

1. [Разберитесь с файлами проекта](/authoring/project-files/).
2. [Напишите страницу в Markdown](/authoring/markdown/).
3. [Настройте сайт, раздел или страницу](/authoring/configuration/).
4. [Проверьте наследование и `$reset`](/authoring/inheritance/).
5. [Выберите `docs` или `landing`](/authoring/layout-and-navigation/).
6. При изменении URL [объявите redirect](/authoring/redirects/).
7. При необходимости [найдите компонент](/components/catalog/).
8. [Соберите и проверьте результат](/build/verify/).

## Не редактируйте output

`build_*`, `.docara/resolved-page-plans.json`, `.docara/redirects.json` и
`_docara/component-catalog.json` создаются заново. Если результат неверен,
исправьте исходный Markdown/JSON и повторите сборку.

## Общая технология двух preset

`docs` и `landing` используют один content format, Framework lock и правила
безопасности. Отличается только presentation recipe: документационный контекст
или сфокусированная страница.
