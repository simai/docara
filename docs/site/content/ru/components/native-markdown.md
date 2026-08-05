# Обычный Markdown

Это самый простой и предпочтительный способ написать страницу. Docara использует CommonMark и только явно включённые расширения; произвольный HTML удаляется и отдельно запрещён policy-проверкой.

## Что поддерживается

| Возможность | Синтаксис | Подробное руководство |
| --- | --- | --- |
| Заголовки, абзацы, emphasis, strong, зачёркивание, smart punctuation | `#`, обычный текст, `*`, `**`, `~~` | [Заголовки и текст](/ru/components/headings-and-text/) |
| Ссылки, reference links и изображения | `[текст](url)`, `![alt](path)` | [Ссылки и изображения](/ru/components/links-and-images/) |
| Маркированные/нумерованные списки и цитаты | `-`, `1.`, `>` | [Списки и цитаты](/ru/components/lists-and-quotes/) |
| Inline code и fenced code | `` `code` ``, тройной backtick | [Код](/ru/components/code/) |
| Таблицы | GFM table | [Таблица](/ru/components/table/) |
| Сноски и источники | `[^id]` | [Сноски и источники](/ru/components/footnotes-and-sources/) |

:::atlas_index {origin=native authoring=markdown support=supported}
:::

Этот перечень соответствует шести `native.*` capability-записям `PortableMarkdownProfile`; длина fence не меняет тип компонента.

## Исходник и результат

:::example {label=Markdown}
```markdown
## Установка

1. Скопируйте команду.
2. Запустите локальную проверку.

| Режим | Результат |
| --- | --- |
| full | весь сайт |
| page | выбранный route |
```
:::

## Параметры и доступность

У native Markdown нет скрытых component props. Семантика задаётся самим Markdown: не пропускайте уровни заголовков, добавляйте осмысленный `alt`, называйте ссылки по действию и снабжайте таблицу понятными заголовками.

## Что будет отклонено

```markdown
<script>alert('unsafe')</script>
```

Ошибка `MARKDOWN_RAW_HTML_FORBIDDEN` появляется до render. Для изолированной демонстрации HTML используйте документированный sandbox-блок, а не raw HTML страницы.
