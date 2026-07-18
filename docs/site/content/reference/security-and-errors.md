# Безопасность и ошибки

Portable builder работает fail closed. Он не пытается угадать намерение при
невалидном JSON, неизвестном prop или опасном пути.

## Основные границы

- Все configuration paths относительны и остаются внутри корня сайта.
- Symlink в корне, content path, lock или destination отклоняется.
- Raw HTML удаляется; небезопасные ссылки запрещаются.
- `_docara` и `.docara` зарезервированы.
- Output collision проверяется до очистки существующей сборки.
- Scalar props экранируются host renderer.
- Поисковый индекс проходит schema до очистки output; browser runtime принимает
  только same-origin URL внутри настроенного `base_url` и создаёт результаты
  через DOM API с `textContent`.
- `navigation.hidden` и `search.indexed: false` не закрывают доступ к HTML.
  Поисковый JSON также публичен. Секретные данные нельзя помещать в portable
  source или собранный сайт.

## Частые коды

| Код | Причина |
| --- | --- |
| `JSON_INVALID` | Файл не является корректным JSON |
| `SCHEMA_VALIDATION_FAILED` | Поле или тип не соответствует schema |
| `FILE_NOT_FOUND` | Обязательный вход отсутствует |
| `SYMLINK_FORBIDDEN` | Путь проходит через символическую ссылку |
| `PORTABLE_OUTPUT_COLLISION` | Две страницы дают один output |
| `FRAMEWORK_COMPONENT_UNSUPPORTED` | Компонент не входит в контракт |
| `FRAMEWORK_PROP_REQUIRED` | Manifest не дал обязательный prop после применения defaults |
| `FRAMEWORK_PROP_MANAGED` | Автор попытался задать prop, которым управляет Docara |
| `FRAMEWORK_DIRECTIVE_INDENTATION_UNSUPPORTED` | Smart-директива вложена в CommonMark-контейнер через отступ |
| `FRAMEWORK_DIRECTIVE_LIMIT_EXCEEDED` | Страница содержит больше 64 строк, похожих на открытие Smart-директивы |
| `FRAMEWORK_PLACEHOLDER_CARDINALITY_INVALID` | Placeholder компонента неоднозначен после Markdown-render |
| `MARKDOWN_BLOCK_UNCLOSED` | `card` или `steps` не закрыт |
| `MARKDOWN_BLOCK_INDENTATION_UNSUPPORTED` | `card` или `steps` начинается с отступа внутри контейнера |
| `MARKDOWN_BLOCK_LIMIT_EXCEEDED` | Страница содержит больше 64 строк, похожих на открытие `card` или `steps` |
| `MARKDOWN_BLOCK_PLACEHOLDER_CARDINALITY_INVALID` | Placeholder блока неоднозначен после Markdown-render |
| `MARKDOWN_STEPS_ORDERED_LIST_REQUIRED` | Steps не содержит один ordered list |
| `SEARCH_RUNTIME_MISSING` | Закреплённый локальный search runtime отсутствует или не читается |
| `SEARCH_RUNTIME_INVALID_UTF8` | Search runtime содержит невалидный UTF-8 |
| `SEARCH_TEXT_INVALID_UTF8` | Видимый текст страницы нельзя безопасно индексировать |
| `SEARCH_COMPONENT_TEXT_PROJECTION_MISSING` | Для нового Smart-компонента не определены индексируемые текстовые props |
| `SEARCH_INDEX_LOCALE_EMPTY` | Поиск включён для locale, но в нём не осталось ни одной индексируемой страницы |
| `SEARCH_DOCUMENT_OUTSIDE_BASE` | URL индексируемой страницы находится вне `base_url` сайта |

Точный код исключения полезнее общего «сборка не работает»: сначала исправьте
вход, затем повторите ту же команду.
