# Переход с legacy-сайта

Legacy Docara хранит контент в `source/<DOCS_DIR>`, настройки в `config.php` и
`.settings.php`, а шаблоны и исходные ассеты — в `source/_core`. Portable режим
использует другую явную файловую границу и не мигрирует старый проект сам.

## Перед началом

Сохраните exact revision legacy-проекта, его готовую сборку, список URL и
копию каталога, который сейчас обслуживает сайт. Проверяйте перенос в новом
каталоге: `init --portable` не является конвертером legacy-конфигурации.

## Перенесите проект

:::steps
1. Зафиксируйте чистую legacy-сборку и список публичных маршрутов.
2. Создайте отдельный пустой portable-каталог.
3. Перенесите Markdown без Blade и raw HTML.
4. Преобразуйте поддерживаемые site/section/page настройки в JSON.
5. Замените custom tags только на подтверждённые Markdown или Framework-компоненты.
6. Соберите оба сайта и сравните содержание, ссылки и ассеты.
7. Переключайте hosting только после browser-приёмки и готового rollback.

:::

Collections, Blade, PHP callbacks, Azure translation и произвольные custom tags
не имеют автоматического эквивалента в portable v1. Их нужно оценивать
отдельно, а не переносить как скрытые no-op поля.

## Бренд и навигация

| Legacy `config.php` | Portable JSON |
| --- | --- |
| `siteName` | `title` как совместимый текстовый fallback |
| `brand.title` | `branding.title` |
| `brand.logoSvg` | сохраните SVG отдельным файлом и укажите `branding.logo` |
| `brand.favicon` | `branding.favicon` |
| отдельный тёмный логотип | `branding.logo_dark` вместе с обязательным `branding.logo` |
| callback `getMenu` | дерево автоматически строится из структуры Markdown |

Сначала сохраните inline `logoSvg` в корневом `assets/logo.svg`; произвольный
SVG-код больше не вставляется в шаблон. Старое рекурсивное меню переносить не
нужно: создайте соответствующие каталоги, страницы-разделы и `_section.json`
с `title`/`navigation.order`.

Legacy `layout.base.header.blocks.search.enabled` заменяется portable-полем
`search.enabled`. Старые `search-index_<lang>.json`, Fuse/Bitrix runtime,
внешний endpoint и ключи не переносятся: Docara пересобирает новый
`_docara/search-index.json`. Поле `search.indexed` — новое явное управление без
прямого legacy-аналога.

Правый TOC, breadcrumbs и previous/next переносятся в наследуемую ветку
`reading`. Не копируйте старые callback-функции или готовый HTML навигации:
Docara строит путь, оглавление и соседей из структуры Markdown самостоятельно.
Сначала сохраните порядок через `navigation.order`, затем настройте
`reading.toc_depth` и при необходимости отключите отдельные поверхности.
Автоматический якорь зависит от текста и порядка одинаковых заголовков, поэтому
после переноса проверьте старые ссылки с `#fragment` и фактический порядок
переходов «Предыдущая»/«Следующая».

Locale/version switch и `socialImage` пока не имеют принятых portable-полей.
Их нельзя переносить как неработающие настройки: они входят в следующие
продуктовые вертикали.

## Проверьте результат

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=localhost --port=8000 --no-build
```

Сравните старый и новый сайты по сохранённому списку URL. Отдельно проверьте
поиск, четыре уровня меню, активный путь, breadcrumbs, TOC, previous/next,
светлую и тёмную темы и viewport 390 px. Завершите preview через `Ctrl+C`.

Не переключайте hosting, пока старый каталог не сохранён как rollback, а
обязательные старые URL либо существуют, либо имеют отдельные проверенные
redirects. Список выведенных ручных маршрутов компонентов находится на
[странице миграции](/migration/).
