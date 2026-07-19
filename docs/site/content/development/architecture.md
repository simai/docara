# Архитектура

Основной пакет `simai/docara` владеет CLI, JSON schemas, разрешением
наследования, Markdown renderer, Framework adapter, starter и статической
сборкой.

Portable режим определяется наличием `docara.json` со schema
`docara.site.v1`. Он изолирован от legacy RuleLoader и `.settings.php` pipeline,
поэтому существующий Blade/Jigsaw-проект продолжает использовать старый путь.

Поток portable-сборки:

:::steps
1. Загрузить и проверить site, section, page и Framework lock.
2. Разрешить наследование и provenance каждой страницы.
3. Проверить Markdown-директивы и props по manifest.
4. Отрендерить Markdown и назначить детерминированные якоря до Smart hydration.
5. Построить маршруты, каноническую топологию и asset plan.
6. Получить из одной топологии видимое меню, breadcrumbs и previous/next.
7. Построить эффективный каталог и сгенерировать его index/detail страницы.
8. Записать HTML, ассеты, search index, catalogue receipt и resolved page plans.

:::

Каноническая топология содержит все страницы, включая скрытые. Проекция меню
отбрасывает скрытые листья, но сохраняет видимые дочерние ветви. Breadcrumbs и
соседние страницы используют каноническую модель, поэтому UI-поверхности не
расходятся по пути или порядку.

Outline builder работает с безопасным HTML Markdown до подстановки
Smart-компонентов. Он назначает H1–H6 уникальные Unicode `id`, а renderer
выводит H2–H6 только до `reading.toc_depth`.

## Источники и производные поверхности

- `resources/component-catalog/native`, `typed`, `smart` и `requirements`
  содержат owner-записи возможностей;
- exact Framework lock и manifests ограничивают Smart admission;
- `EffectiveComponentCatalogBuilder` создаёт один проверяемый каталог;
- `PortableComponentCatalogProjector` использует один общий index/detail shape;
- `_docara/component-catalog.json`, generated HTML и page receipt являются
  производными результатами, а не вторым источником истины.

Локализованный текст и exact fixture находятся рядом с canonical записью.
Отдельные ручные страницы для каждого компонента не нужны.

Файлы являются источником истины; база данных, runtime CRUD, роли и workflow не
входят в standalone Docara.

Пользовательская сборка остаётся PHP-only. Browser JavaScript в готовом
статическом сайте не означает, что автору нужен Node.js; Vite используется
только maintainer-контуром исходных ассетов темы.
