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

Поставляемый starter затем задаёт свои project-owned значения в
`docara.json`: preset `docs`, русский locale, корневой `base_url`, бренд,
контейнер `7`, системную тему, версию документации, файл redirects и включённый
интерфейс поиска. Там же starter включает боковую панель читательских настроек
с полем темы. Это не скрытые defaults — их можно увидеть и изменить в файле
проекта.

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
    "modal_blur": "large"
  },
  "reader_preferences": {
    "enabled": true,
    "view": "side-panel",
    "groups": [
      {
        "id": "appearance",
        "fields": ["appearance.theme", "appearance.modal_blur"]
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
| `reader_preferences` | ✓ | — | — |
| `framework_lock`, `content_root`, `base_url`, `default_locale` | ✓ | — | — |
| `documentation_version`, `redirects_file` | ✓ | — | — |
| `description`, `slug` | — | — | ✓ |

`schema` обязателен на каждом уровне. `version` необязателен и, если указан,
равен `1`. Это версия JSON schema, а не версия документации.

`default_locale` выбирает язык, на который ведёт корень, но одна сборка может содержать любое
число локалей из `locales`. Для каждой задаются отдельное Markdown-дерево,
URL-префикс, направление `ltr/rtl` и явные fallback для UI-подписей. Подробный
контракт описан в разделе [Языки и локали](/authoring/localization/).

`locales.missing_page_policy` имеет два значения: `skip` публикует только
фактически существующие Markdown-owner, `error` требует одинаковый route set у
всех локалей и останавливает сборку с `LOCALE_PAGE_MISSING`. Ни один режим не
копирует редакторский текст из другой локали.

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

- `branding`: `title`, необязательный `label`, `mode`, `size`, обычный/тёмный
  logo и favicon;
- `layout.key`: зарегистрированный макет `docara.docs`;
- `layout.container.max`: целое число `1..8`, соответствующее
  `max-container-1..8` SIMAI Framework;
- `layout.content.gap`: расстояние между соседними блоками статьи по шкале
  `gap-0..gap-8` SIMAI Framework. Для обычной документации используйте `0`:
  заголовки, абзацы, списки и другие элементы уже имеют собственный ритм.
  Значения `1..8` нужны только для страниц, где блоки намеренно собираются в
  свободный вертикальный стек, например в лендинге;
- `layout.scrollbar.preset`: внешний вид полос прокрутки в левом меню и
  содержании страницы — `overlay`, `persistent`, `standard` или `hidden`;
- `layout.regions`: включение и состав областей макета;
- `settings.theme`: `system`, `light`, `dark`;
- `settings.modal_blur`: `none`, `small`, `medium`, `large`; по умолчанию
  используется максимальное размытие `large`;
- `reader_preferences`: включение и состав зарегистрированных настроек
  читателя; ветка разрешена только в `docara.json`;
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
  "layout": { "container": { "max": 6 } },
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
зарегистрированного layout contract. `layout.container` после reset принимает
встроенное значение `{"max": 7}`, если рядом не задано новое.

## Полосы прокрутки

Docara не рисует собственные полосы прокрутки. Левое дерево документации и
правое содержание страницы используют компонент `sf-scrollbar` из
зафиксированной пары SIMAI Framework.

```json
{
  "layout": {
    "scrollbar": {
      "preset": "overlay"
    }
  }
}
```

Доступны четыре режима:

| Preset | Поведение |
| --- | --- |
| `overlay` | По умолчанию. Тонкая полоса появляется при прокрутке, расширяется при наведении и скрывается после паузы |
| `persistent` | Та же компактная полоса остаётся видимой постоянно |
| `standard` | Используется стандартная полоса браузера и операционной системы |
| `hidden` | Полоса скрыта визуально, но содержимое по-прежнему прокручивается |

Если ветка `layout.scrollbar` отсутствует, используется `overlay`. Настройку
можно унаследовать на уровне раздела или заменить для отдельной страницы.
Цвет, ширина, hover, drag, таймер скрытия, клавиатурная прокрутка и
направление `ltr/rtl` принадлежат Framework и не переопределяются CSS-кодом
проекта.

Подробные примеры: [наследование настроек](/authoring/inheritance/).

## Как узнать, откуда пришло значение

После сборки откройте:

```text
build_<environment>/.docara/resolved-page-plans.json
```

Найдите страницу по `output` или `url`, затем:

1. посмотрите итог в `resolved_page_plan.configuration`;
2. возьмите JSON Pointer поля, например `/layout/container/max`;
3. найдите тот же pointer в `provenance`;
4. проверьте указанный source в `trace` и его SHA-256.

`@defaults` означает встроенное значение. `docara.json`, `section.json` или
page sidecar показывают точный владеющий файл.

Полный перечень и ограничения: [справочник schemas](/reference/schemas/).
Практический контракт областей: [области макета](/authoring/regions/).
Пользовательская панель: [настройки чтения](/authoring/reader-settings/).
