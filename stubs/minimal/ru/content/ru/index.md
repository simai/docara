# Моя документация

Этот сайт создан командой `docara init`. Каждая страница — Markdown-файл в
`content/ru/`, а настройки сайта находятся в `docara.json`.

:::ui.alert
{"type":"info","variant":"default","title":"С чего начать","supporting-text":"Измените этот файл, content/ru/index.md, и снова соберите сайт."}
:::

## Что есть в заготовке

- [Начало работы](getting-started/first-page/): первый урок.
- [Инструкции](how-to/publish-the-site/): шаги одной задачи.
- [Справочник](reference/page-types/): сведения для поиска.
- [Объяснения](explanation/page-types/): причины и ограничения.
- [Как писать страницы](writing/): Markdown, блоки кода, таблицы и живой пример.
- [Компоненты](components/): готовые компоненты SIMAI Framework.

Первые четыре раздела показывают четыре типа страниц. Каждая страница в них —
короткий пример своего типа: замените его своим текстом. Тот же стандарт для
ИИ-помощников описан в `AGENTS.md`.

Меню, поисковый индекс, содержание страницы и переходы между страницами
строятся из этих файлов. Добавьте Markdown-файл в `content/ru/`, и он появится
в меню после следующей сборки.

## Сборка и проверка

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --no-build
```

Готовый сайт — каталог `build_production/`. Его можно разместить на любом
статическом хостинге.
