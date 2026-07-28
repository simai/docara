# Содержание и макет

В переносимом Docara файлы являются источником истины:

- Markdown хранит текст и структуру содержания;
- `docara.json` задаёт сайт;
- `redirects.json` сохраняет обязательные старые внутренние URL;
- `section.json` задаёт наследуемые значения ветки;
- необязательный `<page>.page.json` уточняет только одну страницу;
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
4. [Добавьте название, логотип и favicon](/authoring/branding/).
5. [Проверьте наследование и `$reset`](/authoring/inheritance/).
6. [Выберите `docs` или `landing`](/authoring/layout-and-navigation/).
7. Для нескольких языков соберите
   [мультиязычный сайт](/authoring/multilingual-site/) и при необходимости
   [свой language pack](/authoring/language-packs/).
8. При изменении URL [объявите redirect](/authoring/redirects/).
9. [Сравните живые макеты и их точные исходники](/examples/).
10. При необходимости [найдите компонент](/components/catalog/).
11. [Соберите и проверьте результат](/build/verify/).

## Не редактируйте output

`build_*`, `.docara/resolved-page-plans.json`, `.docara/redirects.json` и
`_docara/component-catalog.json` создаются заново. Если результат неверен,
исправьте исходный Markdown/JSON и повторите сборку.

## Общая технология двух preset

`docs` и `landing` используют один content format, Framework lock и правила
безопасности. Отличается только presentation recipe: документационный контекст
или сфокусированная страница.
