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
- Ссылки на локальные фрагменты проверяются вместе с файлами. Повторяющийся
  HTML `id`, отсутствующий якорь и небезопасная percent-кодировка делают
  статическую проверку неуспешной.

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
| `FRAMEWORK_DIRECTIVE_LIMIT_EXCEEDED` | Общий лимит 64 строк typed/Smart-директив превышен строкой Smart-директивы |
| `FRAMEWORK_PLACEHOLDER_CARDINALITY_INVALID` | Placeholder компонента неоднозначен после Markdown-render |
| `MARKDOWN_BLOCK_UNCLOSED` | Композиционный Markdown-блок не закрыт |
| `MARKDOWN_BLOCK_INDENTATION_UNSUPPORTED` | Композиционный Markdown-блок начинается с отступа внутри контейнера |
| `MARKDOWN_BLOCK_LIMIT_EXCEEDED` | Общий лимит 64 строк typed/Smart-директив превышен строкой typed-блока |
| `MARKDOWN_BLOCK_PLACEHOLDER_CARDINALITY_INVALID` | Placeholder блока неоднозначен после Markdown-render |
| `MARKDOWN_STEPS_ORDERED_LIST_REQUIRED` | Steps не содержит один ordered list |
| `MARKDOWN_CTA_LINK_REQUIRED` | CTA не содержит ровно одну Markdown-ссылку с текстом |
| `MARKDOWN_CTA_LINK_UNSAFE` | CTA использует небезопасный протокол ссылки |
| `MARKDOWN_FEATURES_UNORDERED_LIST_REQUIRED` | Features не содержит один плоский unordered list |
| `MARKDOWN_FEATURES_ITEM_COUNT_INVALID` | Features содержит меньше двух или больше шести пунктов |
| `MARKDOWN_FEATURES_ITEM_CONTENT_INVALID` | Пункт Features содержит не один обычный Markdown-абзац или неподдерживаемый inline-элемент |
| `MARKDOWN_FEATURES_ITEM_TEXT_REQUIRED` | Один из пунктов Features не содержит видимого текста |
| `SEARCH_RUNTIME_MISSING` | Закреплённый локальный search runtime отсутствует или не читается |
| `SEARCH_RUNTIME_INVALID_UTF8` | Search runtime содержит невалидный UTF-8 |
| `SEARCH_TEXT_INVALID_UTF8` | Видимый текст страницы нельзя безопасно индексировать |
| `SEARCH_COMPONENT_TEXT_PROJECTION_MISSING` | Для нового Smart-компонента не определены индексируемые текстовые props |
| `SEARCH_INDEX_LOCALE_EMPTY` | Поиск включён для locale, но в нём не осталось ни одной индексируемой страницы |
| `SEARCH_DOCUMENT_OUTSIDE_BASE` | URL индексируемой страницы находится вне `base_url` сайта |
| `DOCUMENT_OUTLINE_DEPTH_INVALID` | Глубина оглавления находится вне диапазона 2–6 |
| `DOCUMENT_OUTLINE_INVALID_UTF8` | Заголовок нельзя безопасно нормализовать в Unicode-якорь |
| `DOCUMENT_OUTLINE_HEADING_TEXT_REQUIRED` | Заголовок не содержит текста или `alt`, пригодного для ссылки оглавления |

Static verifier дополнительно возвращает диагностические маркеры, а не коды
исключений builder:

| Маркер | Причина |
| --- | --- |
| `@duplicate-html-id` | Повторяющийся `id` в одном HTML |
| `@missing-fragment` | Ссылка ведёт на отсутствующий локальный якорь |
| `@unsafe-fragment-encoding` | Невалидная percent-кодировка или UTF-8 fragment |

Точный код исключения полезнее общего «сборка не работает»: сначала исправьте
вход, затем повторите ту же команду.
