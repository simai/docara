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

`branding.title` показывается в шапке. `branding.label` — необязательная короткая
подпись. `logo`, `logo_dark` и `favicon` — пути от корня проекта. Допустимы
SVG, PNG, JPG, WebP и ICO размером не более 2 МиБ. Сборка проверяет, что файл
существует, не является символической ссылкой и не находится в `build*` или
служебном `_docara`. `logo_dark` заменяет основной логотип только в тёмной теме,
поэтому его нельзя задавать без `logo`.

Брендовые файлы копируются в `_docara/brand` под именем по SHA-256. Одинаковые
байты публикуются один раз, а `base_url` автоматически добавляется к ссылкам.

`settings.theme` задаёт унаследованную тему первого посещения. Читатель может
выбрать системную, светлую или тёмную тему в шапке и затем
[вернуть настройку сайта](/authoring/reader-settings/).

`search.enabled` выводит кнопку и локальный поисковый диалог.
`search.indexed` управляет включением страниц текущей области в индекс. Эти
настройки наследуются независимо от `navigation.hidden`: скрытую из меню
страницу можно оставить доступной через поиск. Полный контракт и примеры — в
[руководстве по поиску](/authoring/search/).

`reading` управляет контекстом чтения страниц preset `docs`:

- `breadcrumbs` — путь от главной до текущей страницы;
- `toc` — оглавление по заголовкам страницы;
- `toc_depth` — последний включаемый уровень заголовка от 2 до 6;
- `previous_next` — переходы к соседним страницам в порядке дерева.

Это наследуемая ветка. Подробные правила, адаптивное поведение и примеры
отключения находятся в [руководстве по контексту чтения](/authoring/reading-context/).

## `_section.json`

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

## Page sidecar

```json
{
  "schema": "docara.page.v1",
  "title": "Установка",
  "description": "Как установить проект.",
  "slug": "guides/install",
  "navigation": { "order": 20 },
  "search": { "indexed": true },
  "reading": { "previous_next": false }
}
```

Полный перечень допустимых полей смотрите в [справочнике схем](/reference/schemas/).
Неизвестное поле не игнорируется: сборка останавливается до очистки предыдущего
результата.
