---
description: Как Docara собирает настройки сайта, раздела и страницы, как работают наследование и сброс и откуда пришло значение.
tags: [настройки, конфигурация, наследование, reset, docara-json, section-json, page-json]
---

# Сайт, раздел и страница

Docara читает настройки из JSON-файлов трёх уровней:

| Уровень | Файл | Область |
| --- | --- | --- |
| Сайт | `docara.json` | Все страницы |
| Раздел | `section.json` | Каталог и все вложенные страницы |
| Страница | `<page>.page.json` | Одна Markdown-страница |

Каждый файл содержит поле `schema`. `version` необязателен и, если указан,
равен `1` — это версия формата файла, а не версия документации. Неизвестное
поле, неправильный тип, пустая ветка оформления и небезопасный путь
останавливают сборку. Описание каждого поля — в справочнике:
[docara.json](/ru/reference/docara-json/) и
[section.json и page.json](/ru/reference/section-and-page/).

## Что можно задавать на каждом уровне

| Поле | Сайт | Раздел | Страница |
| --- | :---: | :---: | :---: |
| `preset`, `title`, `locale` | ✓ | ✓ | ✓ |
| `branding`, `layout`, `settings`, `examples`, `code` | ✓ | ✓ | ✓ |
| `navigation`, `header_navigation`, `search`, `reading` | ✓ | ✓ | ✓ |
| `reader_preferences` | ✓ | — | — |
| `framework_lock`, `content_root`, `base_url`, `site_url`, `default_locale`, `locales`, `locale_routing`, `translation_tracking`, `documentation_tracking` | ✓ | — | — |
| `documentation_version`, `redirects_file`, `page_transforms`, `not_found`, `discovery`, `portal`, `theme`, `effects`, `smart` | ✓ | — | — |
| `description`, `slug` | — | — | ✓ |

`section.json` действует на соседнюю страницу-обзор и всех потомков каталога.
Он не владеет Framework lock, корнем контента, базовым адресом, реестром
языков, версией документации, перенаправлениями и пространством имён
проекта. Если ветке не нужны собственные настройки, файл не создают.

Файл страницы тоже необязателен: `install.md` соберётся без
`install.page.json`, унаследовав сайт и родительские разделы. Основной текст
остаётся в `<route>.md`; `title`, `description`, `tags`, `draft` и
`translation_key` удобнее задавать в
[метаданных Markdown](/ru/authoring/markdown/#метаданные-страницы), а файл
страницы нужен для `slug`, вида страницы, меню, макета и другого оформления.
Переименование страницы требует полной сборки и
[перенаправления](/ru/authoring/redirects/) со старого адреса.

## Встроенные значения и заготовка

Docara начинает с небольшого встроенного набора:

| Поле | Встроенное значение |
| --- | --- |
| `content_root` | `"content"` |
| `layout.key` | `"docara.docs"` |
| `layout.container.max` | `7` |
| `layout.content.gap` | `0` |
| `layout.scrollbar.preset` | `"overlay"` |
| `layout.regions.header.enabled` | `true` |
| `layout.regions.sidebar.enabled` | `true` |
| `layout.regions.main.enabled` | `true` |
| `layout.regions.outline.enabled` | `true` |
| `layout.regions.footer.enabled` | `false` |
| `search.enabled` | `false` |
| `search.indexed` | `true` |
| `reading.breadcrumbs` | `true` |
| `reading.toc` | `true` |
| `reading.mobile_toc` | `"auto"` |
| `reading.toc_depth` | `3` |
| `reading.previous_next` | `true` |
| `examples.fullscreen` | `true` |
| `examples.wrap` | `true` |
| `code.wrap` | `true` |

Заготовка `init` затем задаёт свои значения в `docara.json`: вид `docs`,
язык, корневой `base_url`, бренд, контейнер `7`, системную тему, версию
документации, файл перенаправлений, включённый поиск и панель читателя с полем
темы. Это не скрытые значения по умолчанию — их видно в файле проекта, и их
можно изменить.

## Пример docara.json

```json
{
  "schema": "docara.site.v1",
  "title": "Мой проект",
  "preset": "docs",
  "content_root": "content/ru",
  "framework_lock": "simai-framework.lock.json",
  "default_locale": "ru",
  "locales": {
    "missing_page_policy": "skip",
    "ru": {
      "label": "Русский",
      "direction": "ltr",
      "content_root": "content/ru",
      "public_prefix": "ru",
      "fallbacks": []
    }
  },
  "locale_routing": {
    "strategy": "prefixed",
    "root": "redirect",
    "detect_browser_language": false,
    "legacy_unprefixed_redirects": true
  },
  "documentation_version": "current",
  "redirects_file": "redirects.json",
  "base_url": "/",
  "branding": {
    "title": "Мой проект",
    "mode": "compact",
    "size": "medium",
    "logo": "assets/logo.svg",
    "favicon": "assets/favicon.ico"
  },
  "layout": {
    "key": "docara.docs",
    "container": { "max": 7 },
    "content": { "gap": 0 },
    "scrollbar": { "preset": "overlay" }
  },
  "settings": {
    "theme": "system",
    "modal_blur": "none",
    "ui_radius": "default"
  },
  "examples": {
    "fullscreen": true,
    "wrap": true
  },
  "code": {
    "wrap": true
  },
  "reader_preferences": {
    "enabled": true,
    "view": "side-panel",
    "groups": [
      {
        "id": "appearance",
        "fields": ["appearance.theme", "appearance.font_size", "appearance.content_width"]
      }
    ]
  },
  "search": { "enabled": true, "indexed": true },
  "reading": {
    "breadcrumbs": true,
    "toc": true,
    "mobile_toc": "auto",
    "toc_depth": 3,
    "previous_next": true
  }
}
```

Раздел, например `content/ru/guides/section.json`:

```json
{
  "schema": "docara.section.v1",
  "title": "Руководства",
  "branding": { "label": "Руководство пользователя" },
  "layout": { "container": { "max": 6 } },
  "search": { "indexed": true },
  "reading": { "toc_depth": 4 }
}
```

Страница, например `content/ru/guides/install.page.json`:

```json
{
  "schema": "docara.page.v1",
  "title": "Установка",
  "description": "Как установить проект.",
  "slug": "guides/install",
  "navigation": { "order": 20 },
  "reading": { "previous_next": false }
}
```

## Порядок наследования

Для `content/guides/install.md` слои применяются в одном порядке:

:::steps
1. Встроенные значения Docara.
2. `docara.json`.
3. Необязательный корневой `section.json`.
4. `content/section.json`.
5. `content/guides/section.json`.
6. `content/guides/install.page.json`.
7. Markdown как содержание.

:::

Поздний слой заменяет ранний только там, где явно содержит значение; более
близкий уровень переопределяет только разрешённое поле, и объединение
объектов не делает неизвестные ключи допустимыми.

| Вход | Результат |
| --- | --- |
| Поле отсутствует | Унаследованное значение сохраняется |
| Объект с частью ключей | Объекты объединяются рекурсивно |
| Массив | Предыдущий массив заменяется целиком |
| Скаляр | Предыдущее значение заменяется |
| `{}` или `[]` в ветке оформления | Ошибка схемы |
| `{"$reset": true}` | Ветка очищается до пустого объекта |
| Reset вместе с другими ключами | Ветка очищается и заполняется заново |

Чтобы вернуть значение уровня выше, удалите поле на более близком уровне.
Не копируйте вниз весь итоговый объект: это скрывает, откуда пришло значение,
и мешает будущим обновлениям значений по умолчанию.

Исключение — структурная часть `layout`: после очистки Docara возвращает
зарегистрированные `layout.key` и полную карту `layout.regions`, иначе
страница не имела бы проверяемого каркаса. `layout.container` после сброса
принимает встроенное значение `{"max": 7}`, если рядом не задано новое.
Авторские значения layout при этом удаляются.

Та же иерархия действует для `examples.fullscreen`, `examples.wrap` и
`code.wrap`. Параметр `fullscreen` включает режим проверки адаптивности у
изолированного примера; его имя сохранено для совместимости. Параметры
`fullscreen` и `wrap` конкретного `:::example` применяются после итоговой
конфигурации и управляют только этим блоком.

## Примеры наследования и сброса

### Частичное переопределение

Корневой бренд может содержать title, label и logo. Раздел меняет только label:

```json
{
  "schema": "docara.section.v1",
  "branding": { "label": "API" }
}
```

Title и logo продолжают наследоваться.

### Сброс с новыми значениями

Чтобы удалить унаследованные файлы бренда и оставить текстовое название:

```json
{
  "schema": "docara.page.v1",
  "branding": {
    "$reset": true,
    "title": "Печатная версия"
  }
}
```

У итоговой страницы больше нет унаследованных `logo`, `logo_dark`, `favicon`
и `label`.

### Сброс без значений

```json
{
  "schema": "docara.page.v1",
  "navigation": { "$reset": true }
}
```

В итоговом плане ветка `navigation` станет пустым JSON-объектом, а
происхождение самой ветки укажет файл страницы. Значения из ранних слоёв
исчезнут. При отрисовке отсутствие конкретного значения означает безопасное
поведение: страница не скрыта, явного порядка нет. Это отличается от обычного
отсутствия всей ветки в файле страницы, при котором ранние значения
продолжают наследоваться.

### Поиск

Раздел исключает страницы из индекса:

```json
{
  "schema": "docara.section.v1",
  "search": { "indexed": false }
}
```

Одна страница очищает эту ветку и задаёт полный новый результат:

```json
{
  "schema": "docara.page.v1",
  "search": {
    "$reset": true,
    "enabled": true,
    "indexed": true
  }
}
```

Если написать только `{"indexed": true}` без сброса, `enabled` продолжит
наследоваться.

### Контекст чтения

```json
{
  "schema": "docara.page.v1",
  "reading": {
    "$reset": true,
    "breadcrumbs": true,
    "toc": true,
    "toc_depth": 2,
    "previous_next": false
  }
}
```

Сброс затрагивает только `reading`; branding, layout, search и navigation
наследуются независимо.

### Области макета

Раздел отключает правое оглавление для всех потомков:

```json
{
  "schema": "docara.section.v1",
  "layout": {
    "regions": {
      "outline": { "enabled": false }
    }
  }
}
```

Одна страница может снова включить его:

```json
{
  "schema": "docara.page.v1",
  "layout": {
    "regions": {
      "outline": { "enabled": true }
    }
  }
}
```

При этом унаследованный список `sections` сохраняется. Если `sections` указан
явно, новый массив заменяет предыдущую композицию целиком. Подробно — в
разделе [«Области макета»](/ru/settings/layout-and-regions/).

[Живой пример композиции сайта, раздела и страницы](/ru/examples/composition-inheritance/)
показывает унаследованный бренд, заменённый sidebar, отключённый разделом
aside, его сброс на уровне страницы и footer, включённый только для одной
страницы.

## Откуда пришло значение

После сборки откройте квитанцию рядом с результатом:

```text
build_<environment>.docara/resolved-page-plans.json
```

Найдите страницу по `output` или `url`, затем:

1. посмотрите итог в `resolved_page_plan.configuration`;
2. возьмите JSON Pointer поля, например `/layout/container/max`;
3. найдите тот же pointer в `provenance`;
4. проверьте указанный источник в `trace` и его SHA-256.

`@defaults` означает встроенное значение. `docara.json`, `section.json` или
файл страницы показывают точный владеющий файл. Если pointer отсутствует после
сброса, значение действительно удалено; pointer самой очищенной ветки остаётся
и показывает файл, выполнивший сброс. Поля квитанции описаны в справочнике
[«Схемы JSON»](/ru/reference/schemas/#план-страницы-после-сборки).

Для одной страницы то же самое показывает команда:

```bash
docara inspect page /ru/components/alert/ --json
docara build production --page=/ru/components/alert/
```

## Когда нужна полная сборка

Изменение языков, адресов, меню, общих подписей `lang.json`, схемы или
`section.json` требует полной сборки: меню, поиск, переходы и несколько
страниц-потомков меняются одновременно. Обычная правка текста или настройки
одной страницы допускает пересборку одной страницы (`build --page`) при
сохранённой квитанции полной сборки.

## См. также

- [docara.json](/ru/reference/docara-json/) — все поля сайта
- [section.json и page.json](/ru/reference/section-and-page/) — поля раздела и страницы
- [Бренд и тема](/ru/settings/branding-and-theme/)
