---
description: "Как поставить в текст страницы заметное уведомление SIMAI Framework: вызов ui.alert с параметрами прямо в Markdown."
---

# Уведомление в тексте страницы

Посреди текста появляется заметное уведомление: здесь — предупреждение, что в
тестовом режиме деньги не списываются. Оно задаётся в Markdown, без HTML.

::::example {src="/ru/demonstrator-results/smart-alert/" label="Результат"}
### Markdown страницы

:::code {src="../demonstrator-results/smart-alert.md" lang=markdown highlight="10-12"}
:::
::::

## Как это устроено

1. `:::ui.alert` вызывает уведомление из SIMAI Framework.
2. На следующей строке — параметры в JSON. Docara проверяет их при сборке:
   неизвестный параметр или неверное значение остановит сборку.
3. Уведомление следует теме сайта.

## Что можно поменять

| Параметр | Что меняет |
|---|---|
| `type` | Смысл: `info`, `success`, `warning`, `danger` или `clear` |
| `variant` | Вид: `default`, `flat` или `outlined` |
| `title` | Заголовок, до 160 знаков |
| `supporting-text` | Текст, до 500 знаков |
| `closable` | Можно ли закрыть уведомление |
| `aria-label` | Название уведомления для экранного диктора |

Для простых заметок без SIMAI Framework есть [блок «Уведомление»](/ru/components/alert/).

## См. также

- [Заметка рядом с оглавлением](../region-aside/)
- [Кнопка в тексте страницы](../smart-button/)
- [Компоненты SIMAI Framework](/ru/components/smart-ui/)
