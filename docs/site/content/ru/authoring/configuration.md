# Конфигурация

Docara читает три вида JSON:

| Уровень | Файл | Область |
| --- | --- | --- |
| Сайт | `docara.json` | Все страницы |
| Раздел | `section.json` | Каталог и все потомки |
| Страница | `<page>.page.json` | Одна Markdown-страница |

Каждый файл содержит поле `schema`. Неизвестное поле, неправильный тип, пустая
presentation-ветка и невалидный путь останавливают сборку.

## Встроенные значения и starter — не одно и то же

Resolver начинает с небольшого встроенного набора:

| Поле | Встроенное значение |
| --- | --- |
| `content_root` | `"content"` |
| `layout.key` | `"docara.docs"` |
| `layout.regions.header.enabled` | `true` |
| `layout.regions.sidebar.enabled` | `true` |
| `layout.regions.main.enabled` | `true` |
| `layout.regions.outline.enabled` | `true` |
| `layout.regions.footer.enabled` | `false` |
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
  "content_root": "content/ru",
  "framework_lock": "simai-framework.lock.json",
  "default_locale": "ru",
  "locales": {
    "ru": {
      "label": "Русский",
      "direction": "ltr",
      "content_root": "content/ru",
      "language_pack": "@docara/ru",
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
    "label": "Документация",
    "logo": "assets/logo.svg",
    "logo_dark": "assets/logo-dark.svg",
    "favicon": "assets/favicon.svg"
  },
  "layout": {
    "key": "docara.docs",
    "max_width": "wide"
  },
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

`framework_lock`, совместимый `content_root` и реестр `locales` разрешены только
на уровне сайта.
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

`default_locale` выбирает язык, на который ведёт корень, но одна сборка может содержать любое
число локалей из `locales`. Для каждой задаются отдельное Markdown-дерево,
language pack, URL-префикс, направление `ltr/rtl` и явные fallback. Подробный
контракт описан в разделе [Языки и локали](/authoring/localization/).

`locale_routing` задаётся только на уровне сайта. Новый starter использует
симметричную стратегию `prefixed`; прежняя стратегия
`default_unprefixed` поддерживается для существующих сайтов.

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
- `layout.key`: зарегистрированный макет `docara.docs`;
- `layout.max_width`: `compact`, `normal`, `wide`, `full`;
- `layout.regions`: включение и состав областей макета;
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

Для `layout` структурные `key` и `regions` после reset восстанавливаются из
зарегистрированного layout contract. Авторские значения вроде `max_width`
очищаются обычным образом.

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

`@defaults` означает встроенное значение. `docara.json`, `section.json` или
page sidecar показывают точный владеющий файл.

Полный перечень и ограничения: [справочник schemas](/reference/schemas/).
Практический контракт областей: [области макета](/authoring/regions/).
