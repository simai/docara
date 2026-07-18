# Конфигурация

Все JSON-файлы имеют поле `schema`. Неизвестные поля и значения неправильного
типа завершают сборку ошибкой.

## `docara.json`

```json
{
  "schema": "docara.site.v1",
  "title": "Мой проект",
  "preset": "docs",
  "content_root": "content",
  "framework_lock": "simai-framework.lock.json",
  "default_locale": "ru",
  "base_url": "/",
  "layout": { "max_width": "wide" },
  "settings": { "theme": "system" }
}
```

## `_section.json`

```json
{
  "schema": "docara.section.v1",
  "title": "Руководства",
  "layout": { "max_width": "normal" }
}
```

## Page sidecar

```json
{
  "schema": "docara.page.v1",
  "title": "Установка",
  "description": "Как установить проект.",
  "slug": "guides/install",
  "navigation": { "order": 20 }
}
```

Полный перечень допустимых полей смотрите в [справочнике схем](/reference/schemas/).
