---
description: "Как поставить в текст страницы кнопку SIMAI Framework: вызов ui.button с надписью и видом прямо в Markdown."
---

# Кнопка в тексте страницы

В тексте появляется кнопка в стиле SIMAI Framework. Она задаётся в Markdown
тремя строками, без HTML.

::::example {src="/ru/demonstrator-results/smart-button/" label="Результат"}
### Markdown страницы

:::code {src="../demonstrator-results/smart-button.md" lang=markdown highlight="10-12"}
:::
::::

## Как это устроено

1. `:::ui.button` вызывает кнопку из SIMAI Framework.
2. `text` — надпись, `preset` — готовый вид кнопки. Остальные параметры
   берутся из вида.
3. Это кнопка, а не ссылка: она никуда не ведёт сама. Для перехода на другую
   страницу используйте [кнопку-ссылку Docara](/ru/components/button/).

## Что можно поменять

| Параметр | Что меняет |
|---|---|
| `text` | Надпись, до 120 знаков |
| `preset` | Готовый вид: `primary`, `secondary`, `outline`, `link` и их варианты `_on_surface` |
| `size` | Размер: `1/3`, `1/2`, `1`, `2` или `3` |
| `disabled` | Кнопка недоступна для нажатия |
| `aria-label` | Название кнопки для экранного диктора |

## См. также

- [Шапка с кнопкой действия](../region-header/)
- [Уведомление в тексте страницы](../smart-alert/)
- [Компоненты SIMAI Framework](/ru/components/smart-ui/)
