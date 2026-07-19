# Схемы JSON

Portable Docara использует JSON Schema Draft 2020-12.

| Schema ID | Файл | Назначение |
| --- | --- | --- |
| `docara.site.v1` | `docara.json` | Сайт и общие defaults |
| `docara.section.v1` | `_section.json` | Наследуемые настройки раздела |
| `docara.page.v1` | `<page>.page.json` | Настройки одной страницы |
| `docara.component_call.v1` | Нормализованный вызов | Разрешённый компонент и props |
| `docara.framework_lock.v1` | `simai-framework.lock.json` | Exact runtime, manifests и assets |
| `docara.search_index.v1` | `_docara/search-index.json` | Детерминированный локальный поисковый индекс |

## Общие presentation-поля

- `preset`: `docs` или `landing`;
- `branding.title`, `branding.label`;
- `branding.logo`, `branding.logo_dark`, `branding.favicon`: безопасные пути
  от корня проекта; `logo_dark` требует основной `logo`;
- `layout.max_width`: `compact`, `normal`, `wide`, `full`;
- `settings.theme`: `system`, `light`, `dark`;
- `navigation.hidden`: boolean;
- `navigation.order`: целое число от 0 до 2147483647; отсутствие значения означает
  «после всех страниц с явно заданным порядком».
- `search.enabled`: boolean, выводить локальный поиск в текущей области;
- `search.indexed`: boolean, включать страницу в поисковый индекс.
- `reading.breadcrumbs`: boolean, показывать путь до текущей страницы;
- `reading.toc`: boolean, показывать оглавление страницы;
- `reading.toc_depth`: целое число от 2 до 6, последний уровень заголовка в
  оглавлении;
- `reading.previous_next`: boolean, показывать соседние страницы документации.

Значения `reading` по умолчанию: `breadcrumbs: true`, `toc: true`,
`toc_depth: 3`, `previous_next: true`. Допустимая глубина — только целое число
2–6. Значения `1`, `7`, строка `"3"`, строка вместо boolean, пустой объект `{}`
и неизвестное поле отклоняются schema до записи результата.

```json
{
  "schema": "docara.page.v1",
  "reading": { "toc_depth": 7 }
}
```

Этот пример намеренно невалиден: Docara не исправляет опечатки или диапазоны
молча.

Page schema дополнительно поддерживает `description` и безопасный `slug`.
Ветка `branding`, как `layout`, `settings`, `navigation`, `search` и `reading`, поддерживает
только непустой объект настроек: пустые `{}` и `[]` отклоняются как no-op.
Для очистки наследуемой ветки используйте `{"$reset": true}`; reset можно
совместить с новыми значениями той же ветки. Внешние файлы branding
дополнительно проверяются во время сборки: тип, размер, существование, symlink
и зарезервированный путь.
`additionalProperties: false` означает, что опечатка не становится скрытым
no-op: сборка завершается ошибкой.
