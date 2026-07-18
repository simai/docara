# Наследование настроек

Для `content/guides/install.md` настройки применяются в фиксированном порядке:

:::steps
1. Встроенные значения Docara.
2. Корневой `docara.json`.
3. Необязательный корневой `_section.json`.
4. `content/_section.json`.
5. `content/guides/_section.json`.
6. `content/guides/install.page.json`.
7. Markdown как содержание страницы.

:::

Объекты объединяются рекурсивно, скалярное значение заменяет родительское, а
массив целиком заменяет предыдущий массив. `{"$reset": true}` очищает
наследуемую ветку перед применением соседних полей.

Каждое итоговое значение сохраняет источник в `provenance`; это можно увидеть
в [resolved page plan](/reference/resolved-plan/).
