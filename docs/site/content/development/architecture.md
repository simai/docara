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
3. Преобразовать Markdown и Smart-вызовы в типизированный `DocumentAst`.
4. Разрешить `Layout -> Region -> Section -> Block -> Smart`.
5. Построить маршруты, каноническую топологию и asset plan.
6. Получить из одной топологии видимое меню, breadcrumbs и previous/next.
7. Отрендерить regions через trusted templates и immutable view models.
8. Построить эффективный каталог и сгенерировать его index/detail страницы.
9. Собрать полный документ через зарегистрированный publisher template.
10. Записать кандидатный HTML, общие shell assets, search index, receipts и plans.
11. Только после полного успеха транзакционно заменить действующий `build_*`.

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

Техническая canonical-запись компонента хранит стабильный ID, контракт и
lifecycle. Переводимые названия, описания и подписи находятся в language packs,
а исполняемые exact fixtures — в каталоге примеров. Отдельные ручные страницы
для каждого компонента не нужны: generated catalog объединяет эти источники.

Файлы являются источником истины; база данных, runtime CRUD, роли и workflow не
входят в standalone Docara.

Полный документ находится в `resources/publisher/templates/page.php`;
продуктовые Smart-шаблоны — в `resources/smart`; CSS/JS оболочки —
в `resources/portable`. Builder не содержит HTML, CSS или client runtime.

Пользовательская сборка остаётся PHP-only. Browser JavaScript в готовом
статическом сайте не означает, что автору нужен Node.js; Vite используется
только maintainer-контуром исходных ассетов темы.
