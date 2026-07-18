# Переход с legacy-сайта

Legacy Docara хранит контент в `source/<DOCS_DIR>`, настройки в `config.php` и
`.settings.php`, а шаблоны и исходные ассеты — в `source/_core`. Portable режим
использует другую явную файловую границу и не мигрирует старый проект сам.

## Безопасный порядок

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

Search, правый TOC, breadcrumbs, previous/next, locale/version switch и
`socialImage` пока не имеют принятых portable-полей. Их нельзя переносить как
неработающие настройки: они входят в следующие продуктовые вертикали.
