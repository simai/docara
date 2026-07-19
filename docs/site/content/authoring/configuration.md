# Конфигурация

Docara читает три вида JSON:

| Уровень | Файл | Область |
| --- | --- | --- |
| Сайт | `docara.json` | Все страницы |
| Раздел | `_section.json` | Каталог и все потомки |
| Страница | `<page>.page.json` | Одна Markdown-страница |

Каждый файл содержит поле `schema`. Неизвестное поле, неправильный тип, пустая
presentation-ветка и невалидный путь останавливают сборку.

## Встроенные значения и starter — не одно и то же

Resolver начинает с небольшого встроенного набора:

| Поле | Встроенное значение |
| --- | --- |
| `content_root` | `"content"` |
| `search.enabled` | `false` |
| `search.indexed` | `true` |
| `reading.breadcrumbs` | `true` |
| `reading.toc` | `true` |
| `reading.toc_depth` | `3` |
| `reading.previous_next` | `true` |

Поставляемый starter затем задаёт свои project-owned значения в
`docara.json`: preset `docs`, русский locale, корневой `base_url`, бренд,
широкий макет, системную тему, версию документации, файл redirects и включённый
интерфейс поиска. Это не скрытые defaults — их можно увидеть и изменить в
файле проекта.

## Пример `docara.json`

```json
{
  "schema": "docara.site.v1",
  "title": "Мой проект",
  "preset": "docs",
  "content_root": "content",
  "framework_lock": "simai-framework.lock.json",
  "default_locale": "ru",
  "documentation_version": "current",
  "redirects_file": "redirects.json",
  "base_url": "/",
  "branding": {
    "title": "Мой проект",
    "label": "Документация",
    "logo": "assets/logo.svg",
    "logo_dark": "assets/logo-dark.svg",
    "favicon": "assets/favicon.svg"
  },
  "layout": { "max_width": "wide" },
  "settings": { "theme": "system" },
  "search": { "enabled": true, "indexed": true },
  "reading": {
    "breadcrumbs": true,
    "toc": true,
    "toc_depth": 3,
    "previous_next": true
  }
}
```

`framework_lock` и `content_root` разрешены только на уровне сайта.
`base_url` задавайте до production-сборки: например, `"/"` для корня домена или
`"/docs/"` для публикации в подкаталоге.

## Что можно задавать на каждом уровне

| Поле | Site | Section | Page |
| --- | :---: | :---: | :---: |
| `preset`, `title`, `locale` | ✓ | ✓ | ✓ |
| `branding`, `layout`, `settings` | ✓ | ✓ | ✓ |
| `navigation`, `search`, `reading` | ✓ | ✓ | ✓ |
| `framework_lock`, `content_root`, `base_url`, `default_locale` | ✓ | — | — |
| `documentation_version`, `redirects_file` | ✓ | — | — |
| `description`, `slug` | — | — | ✓ |

`schema` обязателен на каждом уровне. `version` необязателен и, если указан,
равен `1`. Это версия JSON schema, а не версия документации.

Одна сборка использует ровно один `default_locale`. Section/page могут
повторить это же значение в `locale`, но другое значение завершает build
ошибкой. Для другого языка или `documentation_version` создайте отдельный site
variant, output и `base_url`; автоматического fallback или switcher нет.

## Перенаправления

`redirects_file` указывает на JSON относительно корня проекта:

```json
{
  "schema": "docara.redirects.v1",
  "version": 1,
  "redirects": [
    {
      "from": "old/installation",
      "to": "start"
    }
  ]
}
```

Маршруты записываются без начального и конечного `/`; `base_url` добавляет
builder. `to` обязан быть существующей сгенерированной страницей. External
URL, query, fragment, self redirect, chain, cycle, collision со страницей или
ассетом и небезопасный путь отклоняются до очистки старого output.

## Presentation-ветки

- `branding`: title, label, обычный/тёмный logo и favicon;
- `layout.max_width`: `compact`, `normal`, `wide`, `full`;
- `settings.theme`: `system`, `light`, `dark`;
- `navigation.hidden`: убрать страницу из меню;
- `navigation.order`: неотрицательный порядок среди siblings;
- `search.enabled`: показать локальный search UI;
- `search.indexed`: включить страницу в индекс;
- `reading`: breadcrumbs, outline depth и previous/next.

Brand assets задаются безопасными путями от корня проекта. Допустимы SVG, PNG,
JPG, WebP и ICO до 2 МиБ. Symlink, reserved/build path и тёмный logo без
основного logo отклоняются.

`navigation.hidden` и `search.indexed: false` не закрывают доступ к HTML.
Секретное содержание нельзя помещать в source или output.

## Раздел

```json
{
  "schema": "docara.section.v1",
  "title": "Руководства",
  "branding": { "label": "Руководство пользователя" },
  "layout": { "max_width": "normal" },
  "search": { "indexed": true },
  "reading": { "toc_depth": 4 }
}
```

## Страница

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

## Отсутствие, пустой объект и reset

- отсутствующее поле продолжает наследоваться;
- `{}` и `[]` недопустимы для presentation-ветки;
- `{"$reset": true}` очищает всю унаследованную ветку;
- reset с соседними полями сначала очищает ветку, затем задаёт новые значения.

Подробные примеры: [наследование настроек](/authoring/inheritance/).

## Как узнать, откуда пришло значение

После сборки откройте:

```text
build_<environment>/.docara/resolved-page-plans.json
```

Найдите страницу по `output` или `url`, затем:

1. посмотрите итог в `resolved_page_plan.configuration`;
2. возьмите JSON Pointer поля, например `/layout/max_width`;
3. найдите тот же pointer в `provenance`;
4. проверьте указанный source в `trace` и его SHA-256.

`@defaults` означает встроенное значение. `docara.json`, `_section.json` или
page sidecar показывают точный владеющий файл.

Полный перечень и ограничения: [справочник schemas](/reference/schemas/).
