# Компоненты

Docara использует два разных механизма. `ui.alert` и `ui.button` являются
Smart-компонентами с manifest, проверкой props и зафиксированными ассетами.
Карточки и шаги — семантические Markdown-блоки, оформленные утилитами Simai
Framework. Код и таблицы остаются стандартным Markdown.

| Элемент | Способ | Статус в текущем контуре |
| --- | --- | --- |
| Alert | `:::ui.alert` | Доступен |
| Button | `:::ui.button` | Доступен |
| Card | `:::card` | Доступен |
| Steps | `:::steps` | Доступен |
| Code | fenced code | Доступен |
| Table | Markdown table | Доступен |
| Tabs | Smart/slot contract | Временно недоступен |

## Справочник

- [Синтаксис и границы](/components/syntax/)
- [Alert](/components/alert/)
- [Button](/components/button/)
- [Card](/components/card/)
- [Steps](/components/steps/)
- [Code](/components/code/)
- [Table](/components/table/)
- [Tabs](/components/tabs/)

Не используйте произвольный HTML вместо отсутствующего компонента: raw HTML
в переносимом Markdown удаляется.
