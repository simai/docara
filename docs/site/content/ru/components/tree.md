# Дерево файлов

Блок объясняет структуру каталогов и файлов с явной вложенностью. В
интерактивном режиме читатель может свернуть ветви мышью или клавиатурой.

## Общий пример

:::example {label="Общий пример"}
```markdown
:::tree
- content
  - ru
    - index.md
    - section.json
  - assets
    - logo.svg
- docara.json
:::
```
:::

## Рабочее дерево

:::tree {interactive=true}
- content
  - ru
    - index.md
    - section.json
  - assets
    - logo.svg
    - screenshot.png
- docara.json
:::

Кнопка ветви сохраняет фокус. Стрелка влево сворачивает её, стрелка вправо
раскрывает. При `interactive=false` структура остаётся полностью раскрытой и
не содержит элементов управления.

:::tree {interactive=false}
- content
  - index.md
- docara.json
:::

## Вызов

```markdown
:::tree {interactive=true}
- src
  - Console
    - BuildCommand.php
- composer.json
:::
```

Используйте один ненумерованный Markdown-список. Дерево предназначено для
объяснения структуры, а не для навигационного меню приложения.
