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
  "branding": {
    "title": "Мой проект",
    "label": "Документация",
    "logo": "assets/logo.svg",
    "logo_dark": "assets/logo-dark.svg",
    "favicon": "assets/favicon.svg"
  },
  "layout": { "max_width": "wide" },
  "settings": { "theme": "system" }
}
```

`branding.title` показывается в шапке. `branding.label` — необязательная короткая
подпись. `logo`, `logo_dark` и `favicon` — пути от корня проекта. Допустимы
SVG, PNG, JPG, WebP и ICO размером не более 2 МиБ. Сборка проверяет, что файл
существует, не является символической ссылкой и не находится в `build*` или
служебном `_docara`.

Брендовые файлы копируются в `_docara/brand` под именем по SHA-256. Одинаковые
байты публикуются один раз, а `base_url` автоматически добавляется к ссылкам.

## `_section.json`

```json
{
  "schema": "docara.section.v1",
  "title": "Руководства",
  "branding": { "label": "Руководство пользователя" },
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
Неизвестное поле не игнорируется: сборка останавливается до очистки предыдущего
результата.
